<?php
/**
 * StudentHub Portal - Authenticated Student Dashboard
 * Practical 10: Secure Login/Logout with Sessions & Role-Based Access
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Protect dashboard: require active student login
require_login('dashboard.php');

$user = get_logged_in_user();

// If authenticated as admin, redirect to admin dashboard
if (($user['role'] ?? '') === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

// Fetch student profile details if available
$studentInfo = null;
try {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid LIMIT 1");
    $stmt->execute([':uid' => $user['id']]);
    $studentInfo = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching student profile: " . $e->getMessage());
}

$displayName = $studentInfo['full_name'] ?? $user['name'];
$displayCourse = $studentInfo['course'] ?? 'Engineering';
$displayYear = $studentInfo['year'] ?? '3rd Semester';
$enrollmentNo = $studentInfo['enrollment_no'] ?? '23DCE128';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
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
                        <li><a href="dashboard.php" class="active">Dashboard</a></li>
                        <li><a href="courses.html">Courses</a></li>
                        <li><a href="attendance.html">Attendance</a></li>
                        <li><a href="assignment.html">Assignment</a></li>
                        <li><a href="result.html">Result</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <?php if ($user['role'] === 'admin'): ?>
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
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Student Dashboard</li>
                </ol>
            </nav>

            <!-- Welcome Header -->
            <div class="dashboard-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px;">
                <div>
                    <h1 class="dashboard-title" style="margin-bottom: 4px;">Student Dashboard</h1>
                    <p class="dashboard-subtitle" style="color: var(--text-muted, #64748b); margin: 0;">
                        Welcome back, <strong><?= htmlspecialchars($displayName) ?></strong> (<?= htmlspecialchars($displayCourse) ?> - <?= htmlspecialchars($displayYear) ?> | Enrollment: <?= htmlspecialchars($enrollmentNo) ?>)
                    </p>
                </div>
                <div style="display: flex; gap: 10px;">
                    <a href="../test_connection.php" class="btn btn-outline" style="border-color: #10b981; color: #10b981;">Database Status</a>
                    <a href="profile.php" class="btn btn-outline">View Profile</a>
                    <a href="logout.php" class="btn btn-danger" style="background: #ef4444; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px;">Logout</a>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div>
                        <div class="stat-label">CGPA</div>
                        <div class="stat-value">8.85</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">Attendance</div>
                        <div class="stat-value">86.8%</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">Due Assignments</div>
                        <div class="stat-value">2</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div>
                        <div class="stat-label">Enrolled Courses</div>
                        <div class="stat-value">4</div>
                    </div>
                </div>
            </div>

            <!-- Modules & Announcements -->
            <div class="dashboard-layout">
                <div>
                    <h4 class="modules-section-title">Academic Modules</h4>
                    <div class="modules-grid">
                        <div class="module-card">
                            <div>
                                <div class="module-card-header">
                                    <div>
                                        <h5>Attendance Tracker</h5>
                                        <small>4 Subjects Tracked</small>
                                    </div>
                                </div>
                                <p>Check subject-wise lectures attended and eligibility status.</p>
                            </div>
                            <a href="attendance.html" class="btn btn-outline">View Attendance</a>
                        </div>

                        <div class="module-card">
                            <div>
                                <div class="module-card-header">
                                    <div>
                                        <h5>Assignment Portal</h5>
                                        <small>3 Active Submissions</small>
                                    </div>
                                </div>
                                <p>Review assessment guidelines, submission deadlines, and feedback.</p>
                            </div>
                            <a href="assignment.html" class="btn btn-outline">View Assignments</a>
                        </div>

                        <div class="module-card">
                            <div>
                                <div class="module-card-header">
                                    <div>
                                        <h5>Semester Results</h5>
                                        <small>Grade Sheets & SGPA</small>
                                    </div>
                                </div>
                                <p>Access official semester results, transcript downloads, and credits.</p>
                            </div>
                            <a href="result.html" class="btn btn-outline">Check Results</a>
                        </div>

                        <div class="module-card">
                            <div>
                                <div class="module-card-header">
                                    <div>
                                        <h5>Campus Events</h5>
                                        <small>Workshops & Hackathons</small>
                                    </div>
                                </div>
                                <p>Discover upcoming department events, hackathons, and guest sessions.</p>
                            </div>
                            <a href="events.html" class="btn btn-outline">Explore Events</a>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Announcements -->
                <div class="announcements-panel">
                    <h4 class="modules-section-title">Campus Announcements</h4>
                    <div class="announcements-card" style="background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px;">
                        <article style="border-bottom: 1px solid var(--border-color); padding-bottom: 14px; margin-bottom: 14px;">
                            <span style="font-size: 0.75rem; color: #2563eb; font-weight: 600; text-transform: uppercase;">Examination Cell</span>
                            <h5 style="margin: 4px 0 6px 0; font-size: 1rem; color: var(--bs-dark);">Mid-Semester Examination Schedule Released</h5>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Odd semester mid-terms commence from next month. Download syllabus guidelines.</p>
                        </article>
                        <article>
                            <span style="font-size: 0.75rem; color: #10b981; font-weight: 600; text-transform: uppercase;">Hackathon Committee</span>
                            <h5 style="margin: 4px 0 6px 0; font-size: 1rem; color: var(--bs-dark);">HackCHARUSAT 2026 Registrations Open</h5>
                            <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted);">Assemble your teams and register before the November deadline.</p>
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p>&copy; 2026-27 StudentHub Portal - Web Development Frameworks (ITUE203) | DEPSTAR, CHARUSAT</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
</body>
</html>
