<?php
/**
 * StudentHub Portal - Administrative Control Dashboard
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 14: Admin Dashboard with Role-Based Access Control, Dynamic Menu, and Audit Log
 * - Problem Definition: Restrict to admin, dynamic menu, user stats, student/event counts, latest registrations
 * - Intermediate Extension: Active/Inactive user status toggle
 * - Advanced Extension: Analytics chart for event registrations by month
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/csrf.php';

// RBAC: Restricted to Admin users only
require_role('admin');
$currentUser = get_logged_in_user();
$pdo = get_pdo_connection();

// Flash messages
$flashMessage = $_SESSION['admin_flash_message'] ?? null;
$flashType    = $_SESSION['admin_flash_type'] ?? 'info';
unset($_SESSION['admin_flash_message'], $_SESSION['admin_flash_type']);

// ----------------------------------------------------------------------------
// INTERMEDIATE EXTENSION: Toggle Active/Inactive User Status
// ----------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_user_status') {
    $submittedToken = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($submittedToken)) {
        $_SESSION['admin_flash_message'] = 'Security validation failed (CSRF mismatch).';
        $_SESSION['admin_flash_type'] = 'danger';
        header('Location: admin_dashboard.php');
        exit;
    }

    $targetUserId = (int)($_POST['user_id'] ?? 0);
    if ($targetUserId > 0) {
        $userStmt = $pdo->prepare("SELECT id, name, status, role FROM users WHERE id = :id");
        $userStmt->execute([':id' => $targetUserId]);
        $targetUser = $userStmt->fetch();

        if ($targetUser) {
            // Prevent admin self-deactivation
            if ($targetUser['id'] === $currentUser['id']) {
                $_SESSION['admin_flash_message'] = 'Administrators cannot deactivate their own account.';
                $_SESSION['admin_flash_type'] = 'danger';
            } else {
                $newStatus = ($targetUser['status'] === 'active') ? 'inactive' : 'active';
                $upd = $pdo->prepare("UPDATE users SET status = :st WHERE id = :id");
                $upd->execute([':st' => $newStatus, ':id' => $targetUserId]);

                log_audit_event($currentUser['id'], 'USER_STATUS_TOGGLE', 'users', $targetUserId, "Toggled status of {$targetUser['name']} to {$newStatus}");
                $_SESSION['admin_flash_message'] = "User '{$targetUser['name']}' status updated to " . strtoupper($newStatus) . ".";
                $_SESSION['admin_flash_type'] = 'success';
            }
        }
    }
    header('Location: admin_dashboard.php');
    exit;
}

// ----------------------------------------------------------------------------
// METRICS QUERIES: KPI Counts from Database
// ----------------------------------------------------------------------------
$totalStudents = (int)$pdo->query("SELECT COUNT(*) FROM students WHERE status != 'deleted'")->fetchColumn();
$totalEvents   = (int)$pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$openEvents    = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status = 'open'")->fetchColumn();
$totalRegs     = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$totalUsers    = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeUsers   = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();

// Latest Registrations
$latestRegsStmt = $pdo->query("
    SELECT r.id, r.registered_at, r.status, s.full_name AS student_name, s.enrollment_no, e.title AS event_title
    FROM registrations r
    JOIN students s ON r.student_id = s.id
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registered_at DESC
    LIMIT 6
");
$latestRegistrations = $latestRegsStmt->fetchAll();

// Users List for Status Toggle
$usersStmt = $pdo->query("SELECT id, name, email, role, status, last_login FROM users ORDER BY id ASC LIMIT 10");
$usersList = $usersStmt->fetchAll();

// ----------------------------------------------------------------------------
// ADVANCED EXTENSION: Analytics Query for Event Registrations by Month
// ----------------------------------------------------------------------------
$analyticsStmt = $pdo->query("
    SELECT 
        DATE_FORMAT(registered_at, '%b') AS month_name,
        MONTH(registered_at) AS month_num,
        COUNT(*) AS total_registrations
    FROM registrations
    GROUP BY month_num, month_name
    ORDER BY month_num ASC
");
$analyticsRaw = $analyticsStmt->fetchAll();

// Format for Canvas Chart
$monthsLabels = [];
$regsData     = [];
foreach ($analyticsRaw as $row) {
    $monthsLabels[] = $row['month_name'];
    $regsData[]     = (int)$row['total_registrations'];
}

// Recent Audit Log entries
$auditStmt = $pdo->query("
    SELECT a.*, u.name as user_name 
    FROM audit_logs a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.id DESC 
    LIMIT 8
");
$auditLogs = $auditStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard &amp; Analytics | StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <style>
        .admin-container { padding: 32px 0 60px; }
        .stats-grid-4 {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px; margin-bottom: 28px;
        }
        .stat-card-kpi {
            background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 16px;
        }
        .kpi-icon {
            width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center;
            justify-content: center; font-size: 1.5rem; background: rgba(37, 99, 235, 0.1);
        }
        .dashboard-split {
            display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 32px;
        }
        @media (max-width: 992px) {
            .dashboard-split { grid-template-columns: 1fr; }
        }
        .chart-card {
            background: var(--card-bg, #fff); border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px; padding: 22px;
        }
        .badge-active { background: #10b981; color: #fff; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; }
        .badge-inactive { background: #ef4444; color: #fff; padding: 2px 8px; border-radius: 9999px; font-size: 0.75rem; }
        .badge-admin { background: #8b5cf6; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }
        .badge-student { background: #3b82f6; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; }

        /* Fix dense navbar spacing and table list padding for admin pages */
        header nav {
            flex-wrap: wrap;
            gap: 12px;
        }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .nav-links li {
            margin: 0;
        }
        .nav-links a {
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 6px;
            white-space: nowrap;
        }
        .chart-card table th,
        .chart-card table td {
            padding: 10px 12px;
            line-height: 1.5;
        }
    </style>
</head>

<body>
    <!-- Skip link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / ROLE-BASED DYNAMIC NAVBAR (Practical 14 Problem Definition) -->
    <header>
        <div class="container">
            <nav>
                <a href="../index.html" class="logo">StudentHub <span style="font-size: 0.75rem; background: #8b5cf6; color: #fff; padding: 2px 6px; border-radius: 4px; vertical-align: middle; margin-left: 6px;">Admin</span></a>
                <div class="nav-actions">
                    <ul class="nav-links">
                        <li><a href="admin_dashboard.php" class="active">Admin Dashboard</a></li>
                        <li><a href="students.php">Students</a></li>
                        <li><a href="events_admin.php">Event CRUD</a></li>
                        <li><a href="events_ajax.php">AJAX API</a></li>
                        <li><a href="deployment.php" style="color: #2563eb; font-weight: 600;">Deploy &amp; Diagnostics</a></li>
                        <li><a href="../test_connection.php" style="color: #10b981; font-weight: 600;">Database Status</a></li>
                        <li><a href="dashboard.php">Student View</a></li>
                        <li><a href="logout.php" class="btn-logout" style="color: #ef4444;">Logout</a></li>
                    </ul>
                    <button type="button" class="theme-toggle-btn" aria-label="Toggle theme">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"></circle></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path></svg>
                    </button>
                    <button type="button" class="hamburger-btn" aria-label="Toggle navigation"><span></span><span></span><span></span></button>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="admin-container">
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Admin Control Center</li>
                </ol>
            </nav>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <div>
                    <h1 style="margin: 0 0 4px 0;">Administrator Control Center</h1>
                    <p style="margin: 0; color: var(--text-muted, #64748b);">
                        Role-Based Access Control, real-time database aggregations, status moderation, and monthly event analytics.
                    </p>
                </div>
                <div style="font-size: 0.85rem; color: var(--text-muted, #64748b);">
                    Authenticated as: <strong><?= htmlspecialchars($currentUser['name']) ?></strong> (Admin)
                </div>
            </div>

            <!-- Flash alerts -->
            <?php if ($flashMessage): ?>
                <div style="padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 0.95rem; background: <?= $flashType === 'success' ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)' ?>; color: <?= $flashType === 'success' ? '#065f46' : '#991b1b' ?>; border: 1px solid <?= $flashType === 'success' ? '#10b981' : '#ef4444' ?>;">
                    <?= htmlspecialchars($flashMessage) ?>
                </div>
            <?php endif; ?>

            <!-- KPI Metric Cards (Problem Definition: Aggregated Stats) -->
            <div class="stats-grid-4">
                <div class="stat-card-kpi">
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.12); color: #2563eb;">🎓</div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted, #64748b); text-transform: uppercase; font-weight: 600;">Active Students</div>
                        <h2 style="margin: 4px 0 0 0; font-size: 1.8rem;"><?= $totalStudents ?></h2>
                    </div>
                </div>

                <div class="stat-card-kpi">
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.12); color: #10b981;">🎪</div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted, #64748b); text-transform: uppercase; font-weight: 600;">Campus Events</div>
                        <h2 style="margin: 4px 0 0 0; font-size: 1.8rem;"><?= $totalEvents ?> <span style="font-size: 0.85rem; font-weight: 400; color: #10b981;">(<?= $openEvents ?> Open)</span></h2>
                    </div>
                </div>

                <div class="stat-card-kpi">
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">📝</div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted, #64748b); text-transform: uppercase; font-weight: 600;">Registrations</div>
                        <h2 style="margin: 4px 0 0 0; font-size: 1.8rem;"><?= $totalRegs ?></h2>
                    </div>
                </div>

                <div class="stat-card-kpi">
                    <div class="kpi-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6;">👥</div>
                    <div>
                        <div style="font-size: 0.8rem; color: var(--text-muted, #64748b); text-transform: uppercase; font-weight: 600;">System Users</div>
                        <h2 style="margin: 4px 0 0 0; font-size: 1.8rem;"><?= $totalUsers ?> <span style="font-size: 0.85rem; font-weight: 400; color: #8b5cf6;">(<?= $activeUsers ?> Active)</span></h2>
                    </div>
                </div>
            </div>

            <!-- Two-Column Section: Analytics Chart & Latest Registrations -->
            <div class="dashboard-split">
                <!-- Advanced Extension: Analytics Chart for Event Registrations by Month -->
                <div class="chart-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="margin: 0; font-size: 1.1rem;">📊 Monthly Event Registrations (Analytics)</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted, #64748b);">Aggregated Trend</span>
                    </div>
                    <div style="position: relative; height: 260px; width: 100%;">
                        <canvas id="monthlyRegistrationsChart" width="500" height="260"></canvas>
                    </div>
                </div>

                <!-- Problem Definition: Latest Event Registrations -->
                <div class="chart-card">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="margin: 0; font-size: 1.1rem;">📌 Recent Event Registrations</h3>
                        <span style="font-size: 0.8rem; color: var(--text-muted, #64748b);">Live Database Stream</span>
                    </div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                            <thead>
                                <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0); text-align: left; color: var(--text-muted, #64748b);">
                                    <th style="padding: 8px 6px;">Student</th>
                                    <th style="padding: 8px 6px;">Event</th>
                                    <th style="padding: 8px 6px;">Date</th>
                                    <th style="padding: 8px 6px; text-align: right;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($latestRegistrations)): ?>
                                    <tr><td colspan="4" style="text-align: center; padding: 20px;">No registrations recorded yet.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($latestRegistrations as $reg): ?>
                                        <tr style="border-bottom: 1px solid var(--border-color, #f1f5f9);">
                                            <td style="padding: 8px 6px;">
                                                <strong><?= htmlspecialchars($reg['student_name']) ?></strong><br>
                                                <small style="color: var(--text-muted, #64748b);"><?= htmlspecialchars($reg['enrollment_no']) ?></small>
                                            </td>
                                            <td style="padding: 8px 6px;"><?= htmlspecialchars($reg['event_title']) ?></td>
                                            <td style="padding: 8px 6px; color: var(--text-muted, #64748b);"><?= date('M d, H:i', strtotime($reg['registered_at'])) ?></td>
                                            <td style="padding: 8px 6px; text-align: right;">
                                                <span class="badge-active"><?= ucfirst($reg['status']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Intermediate Extension: User Moderation & Status Toggle -->
            <div class="chart-card" style="margin-bottom: 32px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 1.1rem;">👥 User Moderation</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #64748b);">
                            Toggle user access status between Active and Inactive. Inactive users are blocked from logging into the portal.
                        </p>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0); text-align: left; background: var(--bg-surface, #f8fafc);">
                                <th style="padding: 10px 14px;">Name</th>
                                <th style="padding: 10px 14px;">Email</th>
                                <th style="padding: 10px 14px;">Role</th>
                                <th style="padding: 10px 14px;">Status</th>
                                <th style="padding: 10px 14px;">Last Login</th>
                                <th style="padding: 10px 14px; text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): ?>
                                <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                                    <td style="padding: 10px 14px;"><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                                    <td style="padding: 10px 14px;"><?= htmlspecialchars($u['email']) ?></td>
                                    <td style="padding: 10px 14px;"><span class="badge-<?= htmlspecialchars($u['role']) ?>"><?= ucfirst(htmlspecialchars($u['role'])) ?></span></td>
                                    <td style="padding: 10px 14px;"><span class="badge-<?= htmlspecialchars($u['status']) ?>"><?= ucfirst(htmlspecialchars($u['status'])) ?></span></td>
                                    <td style="padding: 10px 14px; font-size: 0.8rem; color: var(--text-muted, #64748b);">
                                        <?= $u['last_login'] ? date('M d, Y H:i', strtotime($u['last_login'])) : 'Never' ?>
                                    </td>
                                    <td style="padding: 10px 14px; text-align: right;">
                                        <?php if ($u['id'] === $currentUser['id']): ?>
                                            <span style="font-size: 0.8rem; color: var(--text-muted, #94a3b8);">(Self)</span>
                                        <?php else: ?>
                                            <form method="POST" action="admin_dashboard.php" style="display: inline-block;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="toggle_user_status">
                                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                <button type="submit" class="btn <?= $u['status'] === 'active' ? 'btn-outline' : 'btn-primary' ?>" style="padding: 4px 10px; font-size: 0.8rem;">
                                                    <?= $u['status'] === 'active' ? 'Deactivate' : 'Activate' ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Traceability: Audit Log Trail (Problem Definition & Practical 14) -->
            <div class="chart-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 1.1rem;">🛡️ System Security &amp; Audit Trail</h3>
                        <p style="margin: 0; font-size: 0.85rem; color: var(--text-muted, #64748b);">
                            Immutable records of administrative and security actions taken across the StudentHub portal.
                        </p>
                    </div>
                </div>
                <div style="overflow-x: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem;">
                        <thead>
                            <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0); text-align: left; background: var(--bg-surface, #f8fafc);">
                                <th style="padding: 8px 12px;">Action</th>
                                <th style="padding: 8px 12px;">Entity</th>
                                <th style="padding: 8px 12px;">User</th>
                                <th style="padding: 8px 12px;">Details</th>
                                <th style="padding: 8px 12px;">IP Address</th>
                                <th style="padding: 8px 12px; text-align: right;">Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auditLogs as $log): ?>
                                <tr style="border-bottom: 1px solid var(--border-color, #e2e8f0);">
                                    <td style="padding: 8px 12px;"><code><?= htmlspecialchars($log['action']) ?></code></td>
                                    <td style="padding: 8px 12px;"><?= htmlspecialchars($log['entity']) ?> #<?= $log['entity_id'] ?></td>
                                    <td style="padding: 8px 12px;"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                    <td style="padding: 8px 12px; color: var(--text-muted, #64748b);"><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                                    <td style="padding: 8px 12px; font-family: monospace;"><?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></td>
                                    <td style="padding: 8px 12px; text-align: right; color: var(--text-muted, #64748b);"><?= date('M d, H:i:s', strtotime($log['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>

    <script src="../js/script.js"></script>
    <script>
        // HTML5 Canvas Analytics Chart (Practical 14 Advanced Extension)
        (function drawAnalyticsChart() {
            const canvas = document.getElementById('monthlyRegistrationsChart');
            if (!canvas) return;
            const ctx = canvas.getContext('2d');

            const labels = <?= json_encode(!empty($monthsLabels) ? $monthsLabels : ['Sep', 'Oct', 'Nov', 'Dec', 'Jan']) ?>;
            const data   = <?= json_encode(!empty($regsData) ? $regsData : [5, 7, 0, 0, 0]) ?>;

            const width = canvas.width;
            const height = canvas.height;
            const padding = { top: 20, right: 30, bottom: 40, left: 40 };

            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;
            const maxVal = Math.max(...data, 10);

            ctx.clearRect(0, 0, width, height);

            // Draw Y-axis grid lines
            ctx.strokeStyle = '#e2e8f0';
            ctx.lineWidth = 1;
            ctx.font = '11px sans-serif';
            ctx.fillStyle = '#94a3b8';

            const steps = 4;
            for (let i = 0; i <= steps; i++) {
                const y = padding.top + (chartHeight / steps) * i;
                const val = Math.round(maxVal - (maxVal / steps) * i);
                ctx.beginPath();
                ctx.moveTo(padding.left, y);
                ctx.lineTo(width - padding.right, y);
                ctx.stroke();
                ctx.fillText(val, 12, y + 4);
            }

            // Draw Bars
            const barWidth = Math.min(45, chartWidth / labels.length - 20);
            const stepX = chartWidth / labels.length;

            labels.forEach((label, i) => {
                const x = padding.left + i * stepX + (stepX - barWidth) / 2;
                const barHeight = (data[i] / maxVal) * chartHeight;
                const y = height - padding.bottom - barHeight;

                // Gradient fill for bars
                const grad = ctx.createLinearGradient(0, y, 0, height - padding.bottom);
                grad.addColorStop(0, '#3b82f6');
                grad.addColorStop(1, '#1d4ed8');
                ctx.fillStyle = grad;

                ctx.beginPath();
                ctx.roundRect ? ctx.roundRect(x, y, barWidth, barHeight, [4, 4, 0, 0]) : ctx.rect(x, y, barWidth, barHeight);
                ctx.fill();

                // Draw Value Label on top of bar
                if (data[i] > 0) {
                    ctx.fillStyle = '#1e293b';
                    ctx.font = 'bold 12px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(data[i], x + barWidth / 2, y - 6);
                }

                // Draw X-axis label
                ctx.fillStyle = '#64748b';
                ctx.font = '12px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(label, x + barWidth / 2, height - padding.bottom + 20);
            });
        })();
    </script>
</body>
</html>
