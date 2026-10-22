<?php
/**
 * StudentHub Portal - Dynamic Student Profile View & Editor
 * Practical 10 & 11: Real-time Profile Information from Database
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

require_login('profile.php');

$currentUser = get_logged_in_user();
$pdo = get_pdo_connection();

$flashMessage = $_SESSION['profile_flash_message'] ?? null;
$flashType = $_SESSION['profile_flash_type'] ?? 'info';
unset($_SESSION['profile_flash_message'], $_SESSION['profile_flash_type']);

// Handle Profile Update POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submittedToken)) {
        $_SESSION['profile_flash_message'] = 'Security validation failed (CSRF mismatch).';
        $_SESSION['profile_flash_type'] = 'danger';
        header('Location: profile.php');
        exit;
    }

    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');

    try {
        $stmt = $pdo->prepare("
            UPDATE students 
            SET phone = :phone, city = :city, state = :state 
            WHERE user_id = :uid
        ");
        $stmt->execute([
            ':phone' => $phone,
            ':city'  => $city,
            ':state' => $state,
            ':uid'   => $currentUser['id']
        ]);

        log_audit_event($currentUser['id'], 'PROFILE_UPDATE', 'students', (int)$currentUser['id'], 'Updated personal profile contact info');
        $_SESSION['profile_flash_message'] = 'Profile details updated successfully!';
        $_SESSION['profile_flash_type'] = 'success';
    } catch (Exception $e) {
        $_SESSION['profile_flash_message'] = 'Failed to update profile: ' . $e->getMessage();
        $_SESSION['profile_flash_type'] = 'danger';
    }

    header('Location: profile.php');
    exit;
}

// Fetch student profile record linked to user_id
$student = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $currentUser['id']]);
    $student = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching profile: " . $e->getMessage());
}

// Fallback values if no row linked yet
$fullName     = $student['full_name'] ?? $currentUser['name'];
$email        = $student['email'] ?? $currentUser['email'];
$phone        = $student['phone'] ?? '+91 9427721097';
$course       = $student['course'] ?? 'B.Tech Computer Engineering';
$year         = $student['year'] ?? '2nd Year (Semester 3)';
$enrollmentNo = $student['enrollment_no'] ?? 'D26DCE128';
$city         = $student['city'] ?? 'Anand';
$state        = $student['state'] ?? 'Gujarat';
$status       = $student['status'] ?? 'active';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Student Hub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/profile.css">
</head>

<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / NAVBAR -->
    <header>
        <div class="container">
            <nav>
                <a href="../index.html" class="logo">StudentHub</a>
                <div class="nav-actions">
                    <ul class="nav-links">
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="courses.html">Courses</a></li>
                        <li><a href="attendance.html">Attendance</a></li>
                        <li><a href="assignment.html">Assignment</a></li>
                        <li><a href="result.html">Result</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="profile.php" class="active">Profile</a></li>
                        <?php if ($currentUser['role'] === 'admin'): ?>
                            <li><a href="admin_dashboard.php" class="btn btn-warning" style="padding: 4px 10px; font-size: 0.85rem;">Admin Panel</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php" class="btn-logout" style="color: #ef4444; font-weight: 600;">Logout</a></li>
                    </ul>
                    <button type="button" class="theme-toggle-btn" aria-label="Toggle light/dark theme" title="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="5"></circle>
                            <line x1="12" y1="1" x2="12" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="23"></line>
                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                            <line x1="1" y1="12" x2="3" y2="12"></line>
                            <line x1="21" y1="12" x2="23" y2="12"></line>
                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                        </svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                        </svg>
                    </button>
                    <button type="button" class="hamburger-btn" aria-label="Toggle navigation" aria-expanded="false">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <div class="container profile-container">
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Student Profile</li>
                </ol>
            </nav>

            <div class="profile-header-row" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1 style="margin: 0;">My Profile</h1>
                <span class="status-badge" style="background: #10b981; color: #fff; padding: 4px 12px; border-radius: 9999px; font-size: 0.85rem; font-weight: 600;">
                    <?= strtoupper(htmlspecialchars($status)) ?> STUDENT
                </span>
            </div>

            <!-- Flash Alert -->
            <?php if ($flashMessage): ?>
                <div class="alert alert-<?= htmlspecialchars($flashType) ?>" style="border-radius: 8px; margin-bottom: 20px;">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- Profile Header Card -->
            <div class="user-card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px; margin-bottom: 24px;">
                <div class="user-info">
                    <h3 style="margin: 0 0 6px; color: var(--bs-dark);"><?= htmlspecialchars($fullName) ?></h3>
                    <p style="margin: 0 0 6px; color: var(--text-muted);">
                        Enrollment: <strong style="color: var(--bs-dark);"><?= htmlspecialchars($enrollmentNo) ?></strong> | Degree: <strong style="color: var(--bs-dark);"><?= htmlspecialchars($course) ?></strong>
                    </p>
                    <small style="color: var(--text-muted);">DEVANG PATEL INSTITUTE OF ADVANCED TECHNOLOGY AND RESEARCH (DEPSTAR)</small>
                </div>
            </div>

            <!-- Profile Details Form -->
            <div class="details-card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 24px;">
                <h4 style="margin: 0 0 18px; color: var(--bs-dark);">Personal &amp; Academic Information</h4>

                <form action="profile.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update_profile">

                    <table class="profile-table" style="width: 100%; border-collapse: collapse;">
                        <tbody>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; width: 30%; color: var(--text-muted);">Full Name</th>
                                <td style="padding: 12px; color: var(--bs-body-color);"><strong style="color: var(--bs-dark);"><?= htmlspecialchars($fullName) ?></strong></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">University Email</th>
                                <td style="padding: 12px;"><code><?= htmlspecialchars($email) ?></code></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">Enrollment No</th>
                                <td style="padding: 12px;"><code><?= htmlspecialchars($enrollmentNo) ?></code></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">Degree / Course</th>
                                <td style="padding: 12px; color: var(--bs-body-color);"><?= htmlspecialchars($course) ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">Current Year &amp; Sem</th>
                                <td style="padding: 12px; color: var(--bs-body-color);"><?= htmlspecialchars($year) ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">Mobile Number</th>
                                <td style="padding: 12px;">
                                    <input type="text" name="phone" value="<?= htmlspecialchars($phone) ?>" class="form-control" style="max-width: 320px;">
                                </td>
                            </tr>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">City</th>
                                <td style="padding: 12px;">
                                    <input type="text" name="city" value="<?= htmlspecialchars($city) ?>" class="form-control" style="max-width: 320px;">
                                </td>
                            </tr>
                            <tr>
                                <th style="padding: 12px; text-align: left; color: var(--text-muted);">State</th>
                                <td style="padding: 12px;">
                                    <input type="text" name="state" value="<?= htmlspecialchars($state) ?>" class="form-control" style="max-width: 320px;">
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div style="margin-top: 24px; display: flex; gap: 12px;">
                        <button type="submit" class="btn btn-primary">Save Profile Changes</button>
                        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <script src="../js/script.js"></script>
</body>
</html>
