<?php
/**
 * StudentHub Portal - Student Management CRUD Module
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 11: Student Management CRUD with Search, Filter, and MySQLi Statements
 * - Problem Definition: Add, view, edit, delete, search, filter using MySQLi statements
 * - Intermediate Extension: Export student list to CSV
 * - Advanced Extension: Soft delete using status column instead of permanent delete
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

// Authentication guard
require_login('students.php');
$currentUser = get_logged_in_user();
$mysqli = get_mysqli_connection();

// Feedback messages
$flashMessage = $_SESSION['crud_flash_message'] ?? null;
$flashType    = $_SESSION['crud_flash_type'] ?? 'info';
unset($_SESSION['crud_flash_message'], $_SESSION['crud_flash_type']);

// ----------------------------------------------------------------------------
// INTERMEDIATE EXTENSION: Export Student List to CSV
// ----------------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'export_csv') {
    $search = trim($_GET['search'] ?? '');
    $courseFilter = trim($_GET['course'] ?? '');
    $statusFilter = trim($_GET['status'] ?? '');

    $sql = "SELECT enrollment_no, full_name, email, phone, course, year, gender, city, status, created_at 
            FROM students WHERE 1=1";
    $types = "";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
        $types .= "sss";
        $searchTerm = "%{$search}%";
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }
    if ($courseFilter !== '') {
        $sql .= " AND course = ?";
        $types .= "s";
        $params[] = $courseFilter;
    }
    if ($statusFilter !== '') {
        $sql .= " AND status = ?";
        $types .= "s";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY id DESC";
    $stmt = $mysqli->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=StudentHub_Students_' . date('Ymd_His') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Enrollment No', 'Full Name', 'Email', 'Phone', 'Course', 'Year', 'Gender', 'City', 'Status', 'Registration Date']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($out, $row);
    }
    fclose($out);
    $stmt->close();
    exit;
}

// ----------------------------------------------------------------------------
// POST ACTIONS: Create, Update, Soft Delete
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $submittedToken = $_POST['csrf_token'] ?? '';

    if (!validate_csrf_token($submittedToken)) {
        $_SESSION['crud_flash_message'] = 'Security verification failed (Invalid CSRF token).';
        $_SESSION['crud_flash_type'] = 'danger';
        header('Location: students.php');
        exit;
    }

    // 1. ADD NEW STUDENT
    if ($action === 'create') {
        $enrollment = trim($_POST['enrollment_no'] ?? '');
        $name       = trim($_POST['full_name'] ?? '');
        $email      = trim($_POST['email'] ?? '');
        $phone      = trim($_POST['phone'] ?? '');
        $course     = trim($_POST['course'] ?? '');
        $year       = trim($_POST['year'] ?? '');
        $gender     = trim($_POST['gender'] ?? 'Male');
        $city       = trim($_POST['city'] ?? 'Anand');

        if (empty($enrollment) || empty($name) || empty($email) || empty($phone) || empty($course)) {
            $_SESSION['crud_flash_message'] = 'All mandatory student details must be filled.';
            $_SESSION['crud_flash_type'] = 'danger';
        } else {
            // Check duplicate enrollment/email via MySQLi prepared statement
            $checkStmt = $mysqli->prepare("SELECT id FROM students WHERE enrollment_no = ? OR email = ?");
            $checkStmt->bind_param("ss", $enrollment, $email);
            $checkStmt->execute();
            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {
                $_SESSION['crud_flash_message'] = 'Student with this Enrollment No or Email already exists.';
                $_SESSION['crud_flash_type'] = 'danger';
            } else {
                $ins = $mysqli->prepare("
                    INSERT INTO students (enrollment_no, full_name, email, phone, course, year, gender, city, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
                ");
                $ins->bind_param("ssssssss", $enrollment, $name, $email, $phone, $course, $year, $gender, $city);
                if ($ins->execute()) {
                    log_audit_event($currentUser['id'], 'STUDENT_CREATE', 'students', $ins->insert_id, "Added student: {$enrollment}");
                    $_SESSION['crud_flash_message'] = "Student {$name} ({$enrollment}) successfully added.";
                    $_SESSION['crud_flash_type'] = 'success';
                } else {
                    $_SESSION['crud_flash_message'] = "Database error: " . $ins->error;
                    $_SESSION['crud_flash_type'] = 'danger';
                }
                $ins->close();
            }
            $checkStmt->close();
        }
        header('Location: students.php');
        exit;
    }

    // 2. UPDATE EXISTING STUDENT
    if ($action === 'update') {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['full_name'] ?? '');
        $phone  = trim($_POST['phone'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $year   = trim($_POST['year'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($id > 0 && !empty($name) && !empty($course)) {
            $upd = $mysqli->prepare("
                UPDATE students 
                SET full_name = ?, phone = ?, course = ?, year = ?, status = ?
                WHERE id = ?
            ");
            $upd->bind_param("sssssi", $name, $phone, $course, $year, $status, $id);
            if ($upd->execute()) {
                log_audit_event($currentUser['id'], 'STUDENT_UPDATE', 'students', $id, "Updated student record ID: {$id}");
                $_SESSION['crud_flash_message'] = "Student ID #{$id} details successfully updated.";
                $_SESSION['crud_flash_type'] = 'success';
            } else {
                $_SESSION['crud_flash_message'] = "Update failed: " . $upd->error;
                $_SESSION['crud_flash_type'] = 'danger';
            }
            $upd->close();
        }
        header('Location: students.php');
        exit;
    }

    // 3. ADVANCED EXTENSION: SOFT DELETE STUDENT
    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            // Soft delete sets status = 'deleted' instead of permanent DELETE
            $del = $mysqli->prepare("UPDATE students SET status = 'deleted' WHERE id = ?");
            $del->bind_param("i", $id);
            if ($del->execute()) {
                log_audit_event($currentUser['id'], 'STUDENT_SOFT_DELETE', 'students', $id, "Soft-deleted student ID: {$id}");
                $_SESSION['crud_flash_message'] = "Student record #{$id} has been safely marked as deleted (Soft Delete).";
                $_SESSION['crud_flash_type'] = 'success';
            } else {
                $_SESSION['crud_flash_message'] = "Delete failed: " . $del->error;
                $_SESSION['crud_flash_type'] = 'danger';
            }
            $del->close();
        }
        header('Location: students.php');
        exit;
    }

    // 4. RESTORE SOFT-DELETED STUDENT
    if ($action === 'restore') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $res = $mysqli->prepare("UPDATE students SET status = 'active' WHERE id = ?");
            $res->bind_param("i", $id);
            if ($res->execute()) {
                log_audit_event($currentUser['id'], 'STUDENT_RESTORE', 'students', $id, "Restored soft-deleted student ID: {$id}");
                $_SESSION['crud_flash_message'] = "Student record #{$id} restored to Active status.";
                $_SESSION['crud_flash_type'] = 'success';
            }
            $res->close();
        }
        header('Location: students.php');
        exit;
    }
}

// ----------------------------------------------------------------------------
// QUERY STUDENTS: Search, Filter, and Fetch
// ----------------------------------------------------------------------------
$search = trim($_GET['search'] ?? '');
$courseFilter = trim($_GET['course'] ?? '');
$statusFilter = trim($_GET['status'] ?? 'all_active'); // Default shows active and inactive, not deleted

$query = "SELECT * FROM students WHERE 1=1";
$types = "";
$params = [];

if ($search !== '') {
    $query .= " AND (full_name LIKE ? OR email LIKE ? OR enrollment_no LIKE ?)";
    $types .= "sss";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if ($courseFilter !== '') {
    $query .= " AND course = ?";
    $types .= "s";
    $params[] = $courseFilter;
}

if ($statusFilter === 'all_active') {
    $query .= " AND status != 'deleted'";
} elseif ($statusFilter !== '' && $statusFilter !== 'all') {
    $query .= " AND status = ?";
    $types .= "s";
    $params[] = $statusFilter;
}

$query .= " ORDER BY id DESC";

$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management | StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .crud-container {
            padding: 32px 0 60px;
        }
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            justify-content: space-between;
            background: var(--card-bg, #fff);
            padding: 16px 20px;
            border-radius: 10px;
            border: 1px solid var(--border-color, #e2e8f0);
            margin-bottom: 24px;
        }
        .filter-inputs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        .filter-inputs input, .filter-inputs select {
            padding: 8px 12px;
            border: 1px solid var(--border-color, #cbd5e1);
            border-radius: 6px;
            font-size: 0.9rem;
            background: var(--bg-surface, #fff);
            color: inherit;
        }
        .table-responsive {
            overflow-x: auto;
            background: var(--card-bg, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }
        .data-table th, .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .data-table th {
            background: var(--bg-surface, #f8fafc);
            font-weight: 600;
        }
        .badge-active { background: #10b981; color: #fff; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; }
        .badge-inactive { background: #f59e0b; color: #fff; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; }
        .badge-deleted { background: #ef4444; color: #fff; padding: 3px 8px; border-radius: 9999px; font-size: 0.75rem; }
        
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
                        <li><a href="students.php" class="active">Student CRUD</a></li>
                        <li><a href="events_admin.php">Event CRUD</a></li>
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
                    <li class="breadcrumb-item active" aria-current="page">Student Management</li>
                </ol>
            </nav>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h1 style="margin: 0 0 4px 0;">Student Management Module</h1>
                    <p style="margin: 0; color: var(--text-muted, #64748b);">
                        Manage student records using MySQLi prepared statements. Features live search, filtering, CSV export, and soft delete.
                    </p>
                </div>
                <button type="button" class="btn btn-primary" onclick="openAddModal()">+ Add New Student</button>
            </div>

            <!-- Flash alerts -->
            <?php if ($flashMessage): ?>
                <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; background: <?= $flashType === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $flashType === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $flashType === 'success' ? '#10b981' : '#ef4444' ?>;">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Filter and Actions Toolbar -->
            <form method="GET" action="students.php" class="filter-bar">
                <div class="filter-inputs">
                    <input type="text" name="search" placeholder="Search by name, email, enrollment..." value="<?= htmlspecialchars($search) ?>" style="width: 260px;">
                    <select name="course">
                        <option value="">All Courses</option>
                        <option value="B.Tech CE" <?= $courseFilter === 'B.Tech CE' ? 'selected' : '' ?>>B.Tech CE</option>
                        <option value="B.Tech IT" <?= $courseFilter === 'B.Tech IT' ? 'selected' : '' ?>>B.Tech IT</option>
                        <option value="B.Tech CSE" <?= $courseFilter === 'B.Tech CSE' ? 'selected' : '' ?>>B.Tech CSE</option>
                    </select>
                    <select name="status">
                        <option value="all_active" <?= $statusFilter === 'all_active' ? 'selected' : '' ?>>Active &amp; Inactive</option>
                        <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                        <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                        <option value="deleted" <?= $statusFilter === 'deleted' ? 'selected' : '' ?>>Soft Deleted</option>
                        <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All (Including Deleted)</option>
                    </select>
                    <button type="submit" class="btn btn-primary" style="padding: 8px 14px;">Filter</button>
                    <?php if ($search || $courseFilter || $statusFilter !== 'all_active'): ?>
                        <a href="students.php" class="btn btn-outline" style="padding: 8px 12px; text-decoration: none;">Reset</a>
                    <?php endif; ?>
                </div>

                <!-- Intermediate Extension: CSV Export Button -->
                <div>
                    <a href="students.php?action=export_csv&search=<?= urlencode($search) ?>&course=<?= urlencode($courseFilter) ?>&status=<?= urlencode($statusFilter) ?>" 
                       class="btn btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                        📥 Export to CSV
                    </a>
                </div>
            </form>

            <!-- Students Data Table -->
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Enrollment</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Phone</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 32px; color: var(--text-muted, #64748b);">
                                    No student records found matching the specified search or filter criteria.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($s['enrollment_no']) ?></strong></td>
                                    <td><?= htmlspecialchars($s['full_name']) ?></td>
                                    <td><?= htmlspecialchars($s['email']) ?></td>
                                    <td><?= htmlspecialchars($s['phone']) ?></td>
                                    <td><?= htmlspecialchars($s['course']) ?></td>
                                    <td><?= htmlspecialchars($s['year']) ?></td>
                                    <td>
                                        <span class="badge-<?= htmlspecialchars($s['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($s['status'])) ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right; white-space: nowrap;">
                                        <?php if ($s['status'] === 'deleted'): ?>
                                            <!-- Restore Action -->
                                            <form method="POST" action="students.php" style="display: inline-block;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;">Restore</button>
                                            </form>
                                        <?php else: ?>
                                            <!-- Edit Action -->
                                            <button type="button" class="btn btn-outline" style="padding: 4px 8px; font-size: 0.8rem;"
                                                data-student="<?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>"
                                                onclick="openEditModal(this)">Edit</button>

                                            <!-- Soft Delete Action -->
                                            <form method="POST" action="students.php" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to soft delete this student record?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                <button type="submit" class="btn btn-danger" style="padding: 4px 8px; font-size: 0.8rem; background: #ef4444; color: #fff; border: none; border-radius: 4px;">Delete</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 14px; font-size: 0.85rem; color: var(--text-muted, #64748b);">
                Showing <strong><?= count($students) ?></strong> student record(s).
            </div>
        </div>
    </main>

    <!-- ADD STUDENT MODAL -->
    <div id="addModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top: 0;">Add New Student</h3>
            <form method="POST" action="students.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Enrollment No</label>
                    <input type="text" name="enrollment_no" class="form-control" placeholder="23DCE150" required>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Student Name" required>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">University Email</label>
                    <input type="email" name="email" class="form-control" placeholder="student@charusat.edu.in" required>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Phone Number</label>
                    <input type="text" name="phone" class="form-control" placeholder="9876543210" required>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 16px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Course</label>
                        <select name="course" class="form-control" required>
                            <option value="B.Tech CE">B.Tech CE</option>
                            <option value="B.Tech IT">B.Tech IT</option>
                            <option value="B.Tech CSE">B.Tech CSE</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Year</label>
                        <select name="year" class="form-control" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT STUDENT MODAL -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="margin-top: 0;">Edit Student Record</h3>
            <form method="POST" action="students.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Full Name</label>
                    <input type="text" name="full_name" id="edit_name" class="form-control" required>
                </div>
                <div style="margin-bottom: 12px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Phone Number</label>
                    <input type="text" name="phone" id="edit_phone" class="form-control" required>
                </div>
                <div style="display: flex; gap: 10px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Course</label>
                        <select name="course" id="edit_course" class="form-control" required>
                            <option value="B.Tech CE">B.Tech CE</option>
                            <option value="B.Tech IT">B.Tech IT</option>
                            <option value="B.Tech CSE">B.Tech CSE</option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label style="font-size: 0.85rem; font-weight: 600;">Year</label>
                        <select name="year" id="edit_year" class="form-control" required>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 16px;">
                    <label style="font-size: 0.85rem; font-weight: 600;">Status</label>
                    <select name="status" id="edit_status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/script.js"></script>
    <script>
        function openAddModal() {
            var modal = document.getElementById('addModal');
            if (modal) {
                modal.classList.add('active', 'is-open');
            }
        }
        function openEditModal(btnOrData) {
            var student = btnOrData;
            if (btnOrData instanceof HTMLElement) {
                try {
                    student = JSON.parse(btnOrData.getAttribute('data-student'));
                } catch (e) {
                    console.error('Failed to parse student data', e);
                    return;
                }
            }
            if (!student) return;
            document.getElementById('edit_id').value = student.id || '';
            document.getElementById('edit_name').value = student.full_name || '';
            document.getElementById('edit_phone').value = student.phone || '';
            document.getElementById('edit_course').value = student.course || '';
            document.getElementById('edit_year').value = student.year || '';
            document.getElementById('edit_status').value = student.status || 'active';
            var modal = document.getElementById('editModal');
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
