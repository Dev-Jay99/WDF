<?php
/**
 * StudentHub Portal - Event Management CRUD Module (Admin Only)
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 12: Event Management CRUD with Poster Upload and File Validation
 * - Problem Definition: Add, view, update, delete events with poster upload and validation (type, size)
 * - Intermediate Extension: Filter by category and open/closed status
 * - Advanced Extension: Image renaming, upload directory security, and audit log tracking
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

// RBAC: Restricted to Admin users only
require_role('admin');
$currentUser = get_logged_in_user();
$pdo = get_pdo_connection();

$uploadDir = __DIR__ . '/../uploads/posters/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Flash messages
$flashMessage = $_SESSION['event_flash_message'] ?? null;
$flashType    = $_SESSION['event_flash_type'] ?? 'info';
unset($_SESSION['event_flash_message'], $_SESSION['event_flash_type']);

// Allowed categories
$categories = ['Hackathon', 'Workshop', 'Technical', 'Sports', 'Cultural', 'Seminar'];

// ----------------------------------------------------------------------------
// POST ACTIONS: Create, Update, Delete
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($submittedToken)) {
        $_SESSION['event_flash_message'] = 'Security validation failed (CSRF check mismatch).';
        $_SESSION['event_flash_type'] = 'danger';
        header('Location: events_admin.php');
        exit;
    }

    /**
     * File Upload Helper with Type and Size Validation & Safe Renaming
     */
    function handle_poster_upload(array $file, string $uploadDir, ?string &$errorMsg): ?string {
        if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // Optional poster
        }
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = 'File upload failed with error code: ' . $file['error'];
            return null;
        }

        // 1. File size check (Max 2MB = 2 * 1024 * 1024 bytes)
        $maxBytes = 2 * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $errorMsg = 'Uploaded file exceeds the 2MB size limit (' . round($file['size'] / (1024*1024), 2) . 'MB uploaded).';
            return null;
        }

        // 2. MIME type inspection
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp'
        ];

        if (!array_key_exists($mime, $allowedMimes)) {
            $errorMsg = 'Invalid file format (' . htmlspecialchars($mime) . '). Only JPG, PNG, and WebP images are permitted.';
            return null;
        }

        // 3. Advanced Extension: Cryptographic unique file renaming
        $ext = $allowedMimes[$mime];
        $newFilename = 'poster_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $destination = $uploadDir . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $errorMsg = 'Failed to move uploaded file to destination directory.';
            return null;
        }

        return $newFilename;
    }

    // 1. CREATE EVENT
    if ($action === 'create') {
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? 'Technical');
        $description = trim($_POST['description'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $status      = trim($_POST['status'] ?? 'open');

        $uploadError = null;
        $posterFilename = handle_poster_upload($_FILES['poster'] ?? [], $uploadDir, $uploadError);

        if ($uploadError) {
            $_SESSION['event_flash_message'] = $uploadError;
            $_SESSION['event_flash_type'] = 'danger';
        } elseif (empty($title) || empty($eventDate) || empty($venue)) {
            $_SESSION['event_flash_message'] = 'Title, Event Date, and Venue are required fields.';
            $_SESSION['event_flash_type'] = 'danger';
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, category, description, event_date, venue, poster_image, status, created_by)
                VALUES (:title, :cat, :desc, :edate, :venue, :poster, :status, :uid)
            ");
            $stmt->execute([
                ':title'  => $title,
                ':cat'    => $category,
                ':desc'   => $description,
                ':edate'  => $eventDate,
                ':venue'  => $venue,
                ':poster' => $posterFilename,
                ':status' => $status,
                ':uid'    => $currentUser['id']
            ]);
            $newId = (int)$pdo->lastInsertId();

            // Advanced Extension: Audit Log Entry
            log_audit_event($currentUser['id'], 'EVENT_CREATE', 'events', $newId, "Created event: '{$title}' ({$category})");

            $_SESSION['event_flash_message'] = "Event '{$title}' successfully published.";
            $_SESSION['event_flash_type'] = 'success';
        }
        header('Location: events_admin.php');
        exit;
    }

    // 2. UPDATE EVENT
    if ($action === 'update') {
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $category    = trim($_POST['category'] ?? 'Technical');
        $description = trim($_POST['description'] ?? '');
        $eventDate   = trim($_POST['event_date'] ?? '');
        $venue       = trim($_POST['venue'] ?? '');
        $status      = trim($_POST['status'] ?? 'open');

        if ($id > 0 && !empty($title)) {
            $uploadError = null;
            $newPoster = handle_poster_upload($_FILES['poster'] ?? [], $uploadDir, $uploadError);

            if ($uploadError) {
                $_SESSION['event_flash_message'] = $uploadError;
                $_SESSION['event_flash_type'] = 'danger';
            } else {
                if ($newPoster) {
                    // Remove old poster if exists
                    $old = $pdo->prepare("SELECT poster_image FROM events WHERE id = :id");
                    $old->execute([':id' => $id]);
                    $oldPoster = $old->fetchColumn();
                    if ($oldPoster && file_exists($uploadDir . $oldPoster)) {
                        @unlink($uploadDir . $oldPoster);
                    }

                    $upd = $pdo->prepare("
                        UPDATE events 
                        SET title = :t, category = :c, description = :d, event_date = :ed, venue = :v, poster_image = :p, status = :s
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':t' => $title, ':c' => $category, ':d' => $description, 
                        ':ed' => $eventDate, ':v' => $venue, ':p' => $newPoster, ':s' => $status, ':id' => $id
                    ]);
                } else {
                    $upd = $pdo->prepare("
                        UPDATE events 
                        SET title = :t, category = :c, description = :d, event_date = :ed, venue = :v, status = :s
                        WHERE id = :id
                    ");
                    $upd->execute([
                        ':t' => $title, ':c' => $category, ':d' => $description, 
                        ':ed' => $eventDate, ':v' => $venue, ':s' => $status, ':id' => $id
                    ]);
                }

                log_audit_event($currentUser['id'], 'EVENT_UPDATE', 'events', $id, "Updated event ID: {$id} ({$title})");
                $_SESSION['event_flash_message'] = "Event '{$title}' updated successfully.";
                $_SESSION['event_flash_type'] = 'success';
            }
        }
        header('Location: events_admin.php');
        exit;
    }

    // 3. DELETE EVENT
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $pdo->prepare("SELECT title, poster_image FROM events WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $ev = $stmt->fetch();

            if ($ev) {
                if ($ev['poster_image'] && file_exists($uploadDir . $ev['poster_image'])) {
                    @unlink($uploadDir . $ev['poster_image']);
                }
                $del = $pdo->prepare("DELETE FROM events WHERE id = :id");
                $del->execute([':id' => $id]);

                log_audit_event($currentUser['id'], 'EVENT_DELETE', 'events', $id, "Deleted event: '{$ev['title']}'");
                $_SESSION['event_flash_message'] = "Event '{$ev['title']}' has been deleted.";
                $_SESSION['event_flash_type'] = 'success';
            }
        }
        header('Location: events_admin.php');
        exit;
    }
}

// ----------------------------------------------------------------------------
// QUERY EVENTS: Intermediate Extension - Category & Open/Closed Filter
// ----------------------------------------------------------------------------
$categoryFilter = trim($_GET['category'] ?? '');
$statusFilter   = trim($_GET['status'] ?? '');
$search         = trim($_GET['search'] ?? '');

$sql = "SELECT e.*, u.name AS organizer_name FROM events e LEFT JOIN users u ON e.created_by = u.id WHERE 1=1";
$params = [];

if ($search !== '') {
    $sql .= " AND (e.title LIKE :s OR e.venue LIKE :s2)";
    $params[':s']  = "%{$search}%";
    $params[':s2'] = "%{$search}%";
}
if ($categoryFilter !== '') {
    $sql .= " AND e.category = :cat";
    $params[':cat'] = $categoryFilter;
}
if ($statusFilter !== '') {
    $sql .= " AND e.status = :status";
    $params[':status'] = $statusFilter;
}

$sql .= " ORDER BY e.event_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management | StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .crud-container { padding: 32px 0 60px; }
        .filter-bar {
            display: flex; flex-wrap: wrap; gap: 12px; align-items: center; justify-content: space-between;
            background: var(--card-bg, #fff); padding: 16px 20px; border-radius: 10px;
            border: 1px solid var(--border-color, #e2e8f0); margin-bottom: 24px;
        }
        .filter-inputs { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
        .filter-inputs input, .filter-inputs select {
            padding: 8px 12px; border: 1px solid var(--border-color, #cbd5e1); border-radius: 6px;
            font-size: 0.9rem; background: var(--bg-surface, #fff); color: inherit;
        }
        .events-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;
        }
        .event-admin-card {
            background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px; overflow: hidden; display: flex; flex-direction: column;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .event-admin-card:hover { transform: translateY(-3px); box-shadow: 0 8px 16px -4px rgba(0,0,0,0.1); }
        .poster-preview {
            width: 100%; height: 160px; object-fit: cover; background: #e2e8f0;
        }
        .event-card-content { padding: 18px; flex: 1; display: flex; flex-direction: column; }
        .badge-open { background: #10b981; color: #fff; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-closed { background: #ef4444; color: #fff; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .category-tag { background: rgba(37, 99, 235, 0.1); color: #2563eb; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.65) !important;
            backdrop-filter: blur(4px);
            z-index: 99999 !important;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            padding: 20px;
            box-sizing: border-box;
        }
        .modal-overlay.active,
        .modal-overlay.is-open {
            display: flex !important;
            opacity: 1 !important;
            visibility: visible !important;
            pointer-events: auto !important;
        }
        .modal-box {
            background: var(--card-bg, #ffffff);
            color: var(--text-main, #0f172a);
            padding: 28px;
            border-radius: 14px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
            border: 1px solid var(--border-color, #e2e8f0);
            position: relative;
            z-index: 100000;
        }
        .modal-box .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 8px;
            font-size: 0.95rem;
            background: var(--bg-input, #fff);
            color: inherit;
            outline: none;
            box-sizing: border-box;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .modal-box .form-control:focus {
            border-color: var(--bs-primary, #2563eb);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
        }
    </style>
</head>

<body>
    <!-- Skip link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / NAVBAR -->
    <header>
        <div class="container">
            <nav>
                <a href="../index.html" class="logo">StudentHub</a>
                <div class="nav-actions">
                    <ul class="nav-links">
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="students.php">Student CRUD</a></li>
                        <li><a href="events_admin.php" class="active">Event CRUD</a></li>
                        <li><a href="events_ajax.php">AJAX API</a></li>
                        <li><a href="admin_dashboard.php">Admin Panel</a></li>
                        <li><a href="logout.php" class="btn-logout" style="color: #ef4444;">Logout</a></li>
                    </ul>
                    <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <button type="button" class="hamburger-btn" aria-label="Toggle menu"><span></span><span></span><span></span></button>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="crud-container">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="admin_dashboard.php">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Event Management</li>
                </ol>
            </nav>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="margin: 0 0 4px 0;">Event Management</h1>
                    <p style="margin: 0; color: var(--text-muted, #64748b);">
                        Create, modify, and publish campus events with validated poster uploads, category filters, and audit trail.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openAddEventModal()">+ Create New Event</button>
            </div>

            <!-- Flash alerts -->
            <?php if ($flashMessage): ?>
                <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; background: <?= $flashType === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $flashType === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $flashType === 'success' ? '#10b981' : '#ef4444' ?>;">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Filter Toolbar (Intermediate Extension: Category & Open/Closed Filter) -->
            <form method="GET" action="events_admin.php" class="filter-bar">
                <div class="filter-inputs">
                    <input type="text" name="search" placeholder="Search event or venue..." value="<?= htmlspecialchars($search) ?>" style="width: 240px;">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat ?>" <?= $categoryFilter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="open" <?= $statusFilter === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="closed" <?= $statusFilter === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
                    <?php if ($search || $categoryFilter || $statusFilter): ?>
                        <a href="events_admin.php" class="btn btn-outline" style="padding: 8px 12px; text-decoration: none;">Reset</a>
                    <?php endif; ?>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted, #64748b);">
                    Showing <strong><?= count($events) ?></strong> event(s)
                </div>
            </form>

            <!-- Events Grid -->
            <div class="events-grid">
                <?php if (empty($events)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 48px; background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0); border-radius: 12px; color: var(--text-muted, #64748b);">
                        No campus events found matching your filter selection.
                    </div>
                <?php else: ?>
                    <?php foreach ($events as $ev): ?>
                        <div class="event-admin-card">
                            <?php 
                                $posterSrc = $ev['poster_image'] ? "../uploads/posters/" . htmlspecialchars($ev['poster_image']) : "../images/logo.png";
                            ?>
                            <img src="<?= $posterSrc ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="poster-preview" onerror="this.src='../images/logo.png';">
                            <div class="event-card-content">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <span class="category-tag"><?= htmlspecialchars($ev['category']) ?></span>
                                    <span class="badge-<?= htmlspecialchars($ev['status']) ?>"><?= ucfirst(htmlspecialchars($ev['status'])) ?></span>
                                </div>
                                <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; line-height: 1.4;"><?= htmlspecialchars($ev['title']) ?></h3>
                                <p style="margin: 0 0 12px 0; font-size: 0.85rem; color: var(--text-muted, #64748b); flex: 1;">
                                    <?= htmlspecialchars(mb_strimwidth($ev['description'], 0, 100, '...')) ?>
                                </p>
                                <div style="font-size: 0.8rem; color: var(--text-muted, #64748b); margin-bottom: 14px;">
                                    <div>📅 <strong>Date:</strong> <?= htmlspecialchars($ev['event_date']) ?></div>
                                    <div>📍 <strong>Venue:</strong> <?= htmlspecialchars($ev['venue']) ?></div>
                                </div>
                                <div style="display: flex; gap: 8px; justify-content: flex-end; border-top: 1px solid var(--border-color, #e2e8f0); padding-top: 12px;">
                                    <button type="button" class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;"
                                        data-event="<?= htmlspecialchars(json_encode($ev), ENT_QUOTES, 'UTF-8') ?>"
                                        onclick="openEditEventModal(this)">Edit</button>
                                    <form method="POST" action="events_admin.php" style="display: inline-block;" onsubmit="return confirm('Permanently delete this event and remove poster?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $ev['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 0.8rem; background: #ef4444; color: #fff; border: none; border-radius: 4px;">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- CREATE EVENT MODAL -->
    <div id="addEventModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top: 0;">Publish New Campus Event</h3>
            <form method="POST" action="events_admin.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Event Title</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. HackCHARUSAT 2026" required>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Category</label>
                        <select name="category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Status</label>
                        <select name="status" class="form-control">
                            <option value="open">Open for Registration</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Event Date</label>
                        <input type="date" name="event_date" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Venue / Location</label>
                        <input type="text" name="venue" class="form-control" placeholder="DEPSTAR Auditorium" required>
                    </div>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Event Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief agenda, rules, or prerequisites..."></textarea>
                </div>
                <!-- Poster Upload with File Validation -->
                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Event Poster Image (Max 2MB: JPG, PNG, WebP)</label>
                    <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Event</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT EVENT MODAL -->
    <div id="editEventModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top: 0;">Edit Event Details</h3>
            <form method="POST" action="events_admin.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_event_id">

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Event Title</label>
                    <input type="text" name="title" id="edit_event_title" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Category</label>
                        <select name="category" id="edit_event_category" class="form-control" required>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>"><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Status</label>
                        <select name="status" id="edit_event_status" class="form-control">
                            <option value="open">Open</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Event Date</label>
                        <input type="date" name="event_date" id="edit_event_date" class="form-control" required>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Venue</label>
                        <input type="text" name="venue" id="edit_event_venue" class="form-control" required>
                    </div>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Description</label>
                    <textarea name="description" id="edit_event_desc" class="form-control" rows="3"></textarea>
                </div>
                <div style="margin-bottom: 18px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Replace Poster (Optional, Max 2MB)</label>
                    <input type="file" name="poster" class="form-control" accept="image/jpeg,image/png,image/webp">
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editEventModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        function openAddEventModal() {
            var modal = document.getElementById('addEventModal');
            if (modal) {
                modal.classList.add('active', 'is-open');
            }
        }
        function openEditEventModal(btnOrData) {
            var ev = btnOrData;
            if (btnOrData instanceof HTMLElement) {
                try {
                    ev = JSON.parse(btnOrData.getAttribute('data-event'));
                } catch (e) {
                    console.error('Failed to parse event data', e);
                    return;
                }
            }
            if (!ev) return;
            document.getElementById('edit_event_id').value = ev.id || '';
            document.getElementById('edit_event_title').value = ev.title || '';
            document.getElementById('edit_event_category').value = ev.category || '';
            document.getElementById('edit_event_status').value = ev.status || 'open';
            document.getElementById('edit_event_date').value = ev.event_date || '';
            document.getElementById('edit_event_venue').value = ev.venue || '';
            document.getElementById('edit_event_desc').value = ev.description || '';
            var modal = document.getElementById('editEventModal');
            if (modal) {
                modal.classList.add('active', 'is-open');
            }
        }
        function closeModal(modalId) {
            var modal = typeof modalId === 'string' ? document.getElementById(modalId) : modalId;
            if (modal) {
                modal.classList.remove('active', 'is-open');
            }
        }

        // Close modal on clicking outside the modal-box
        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal(this);
                }
            });
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay.active, .modal-overlay.is-open').forEach(function(m) {
                    closeModal(m);
                });
            }
        });
    </script>
</body>
</html>
