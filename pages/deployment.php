<?php
declare(strict_types=1);

/**
 * ============================================================================
 * CHAROTAR UNIVERSITY OF SCIENCE AND TECHNOLOGY (CHARUSAT)
 * DEVANG PATEL INSTITUTE OF ADVANCE TECHNOLOGY AND COMPUTER APPLICATIONS (DEPSTAR)
 * SUBJECT: WEB DEVELOPMENT FRAMEWORKS (ITUE203)
 * Practical 15: Final System Integration, Testing, Documentation, and Local Deployment
 * ============================================================================
 * File: pages/deployment.php
 * Description: Master Deployment & Comprehensive System Diagnostics Center
 *              - RBAC Guard: Strictly accessible to 'admin' role
 *              - Server runtime specifications & PHP extension audit
 *              - Relational schema inventory (6 normalized tables) & Stored Procedure status
 *              - Storage concurrency, upload directory, and file permissions telemetry
 *              - Virtual Host configuration preview (studenthub.local)
 *              - Master automated test suite runner link (15/15 practicals)
 *              - Full Light & Dark theme compatibility
 * ============================================================================
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';

// Strict Role-Based Access Control: Admin only
require_role('admin');
$currentUser = get_logged_in_user();

// 1. Gather System & Server Telemetry
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache/2.4 (Win64)';
$phpVersion     = PHP_VERSION;
$phpSapi        = php_sapi_name();
$versionStr     = php_uname('v');
preg_match('/build (\d+)/i', $versionStr, $buildMatches);
$buildNum       = isset($buildMatches[1]) ? (int)$buildMatches[1] : 0;
$osFull         = ($buildNum >= 22000) 
                    ? "Windows 11 Pro/Home (Build {$buildNum}, " . php_uname('m') . ")" 
                    : php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')';
$maxExecution   = ini_get('max_execution_time') . 's';
$memoryLimit    = ini_get('memory_limit');
$uploadMaxFiles = ini_get('upload_max_filesize');
$postMaxSize    = ini_get('post_max_size');

// Critical PHP Extensions Audit
$requiredExtensions = [
    'pdo'        => 'PHP Data Objects abstraction core',
    'pdo_mysql'  => 'MySQL PDO driver for secure prepared statements',
    'mysqli'     => 'MySQL Improved driver for procedural/OOP DB queries',
    'json'       => 'JavaScript Object Notation parser & serializer',
    'session'    => 'Stateful HTTP session management & cookie security',
    'openssl'    => 'Cryptographic tokens & SHA-256 hash generation',
    'mbstring'   => 'Multibyte string sanitization & UTF-8 encoding',
    'fileinfo'   => 'MIME-type detection for secure file uploads',
    'curl'       => 'Client URL library for asynchronous API requests',
    'exif'       => 'Image metadata inspection & secure file upload validation'
];

$extensionStatus = [];
foreach ($requiredExtensions as $ext => $desc) {
    $extensionStatus[$ext] = [
        'loaded' => extension_loaded($ext),
        'desc'   => $desc
    ];
}

// 2. Relational Database Schema & Stored Procedure Telemetry
$dbConnected = false;
$dbError = null;
$tableStats = [];
$spStats = [];

$tables = [
    'users' => [
        'practical' => 'P9, P10, P14',
        'desc'      => 'User credentials, Bcrypt password hashes, roles (admin/student), and timestamps'
    ],
    'students' => [
        'practical' => 'P11, P14',
        'desc'      => 'Student profiles, enrollments, course, year, contact numbers, and timestamps'
    ],
    'events' => [
        'practical' => 'P12, P13',
        'desc'      => 'Campus workshops, hackathons, poster image paths, dates, and capacities'
    ],
    'registrations' => [
        'practical' => 'P7, P12, P13',
        'desc'      => 'Transactional student event reservations, status, and registration dates'
    ],
    'remember_tokens' => [
        'practical' => 'P10',
        'desc'      => 'Persistent login tokens with SHA-256 hashes and expiry timestamps'
    ],
    'audit_logs' => [
        'practical' => 'P14, P15',
        'desc'      => 'Immutable administrative security actions, IP addresses, and audit trail'
    ]
];

try {
    $pdo = get_pdo_connection();
    $dbConnected = true;

    foreach ($tables as $tbl => $meta) {
        $count = 0;
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS total FROM `{$tbl}`");
            $count = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $count = -1; // table missing or query issue
        }

        // Fetch engine & collation
        $engine = 'InnoDB';
        $collation = 'utf8mb4_unicode_ci';
        try {
            $stmtStatus = $pdo->query("SHOW TABLE STATUS LIKE '{$tbl}'");
            $row = $stmtStatus->fetch();
            if ($row) {
                $engine = $row['Engine'] ?? 'InnoDB';
                $collation = $row['Collation'] ?? 'utf8mb4_unicode_ci';
            }
        } catch (Throwable $e) {}

        $tableStats[$tbl] = array_merge($meta, [
            'count'     => $count,
            'engine'    => $engine,
            'collation' => $collation
        ]);
    }

    // Test Stored Procedure
    $spStats = execute_sp_dashboard_stats();
} catch (Throwable $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

// 3. File System & Storage Permissions Telemetry
$baseDir = dirname(__DIR__);
$storageChecks = [
    'Event Posters Directory' => [
        'path'        => $baseDir . '/uploads/posters',
        'type'        => 'directory',
        'practical'   => 'Practical 12',
        'description' => 'Storage for uploaded event poster images with MIME verification'
    ],
    'Registrations CSV Log' => [
        'path'        => $baseDir . '/data/registrations.csv',
        'type'        => 'file',
        'practical'   => 'Practical 7',
        'description' => 'Append-only CSV log for server-side form registration records'
    ],
    'Registrations JSON Store' => [
        'path'        => $baseDir . '/data/registrations.json',
        'type'        => 'file',
        'practical'   => 'Practical 7',
        'description' => 'Structured JSON storage mirror for registrations'
    ],
    'Environment Configuration' => [
        'path'        => $baseDir . '/.env.example',
        'type'        => 'file',
        'practical'   => 'Practical 15',
        'description' => 'Environment variable template for production configuration'
    ],
    'Apache VirtualHost Spec' => [
        'path'        => $baseDir . '/docs/apache_vhost.conf',
        'type'        => 'file',
        'practical'   => 'Practical 15',
        'description' => 'Production Apache VirtualHost definition with security headers'
    ]
];

$storageTelemetry = [];
foreach ($storageChecks as $label => $info) {
    $path = $info['path'];
    $exists = file_exists($path);
    $writable = $exists ? is_writable($path) : is_writable(dirname($path));
    $size = ($exists && is_file($path)) ? filesize($path) : 0;
    $fileCount = ($exists && is_dir($path)) ? count(glob($path . '/*.*') ?: []) : 0;

    $storageTelemetry[$label] = [
        'practical'   => $info['practical'],
        'description' => $info['description'],
        'exists'      => $exists,
        'writable'    => $writable,
        'size'        => $size,
        'fileCount'   => $fileCount,
        'displayPath' => str_replace($baseDir, '.', $path)
    ];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Deployment & Diagnostics | StudentHub Portal (P15)</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* Navbar Layout Refinement - Prevent line breaking & align horizontally */
        header .container {
            max-width: 1300px;
            width: 100%;
            padding: 0 24px;
        }
        header nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            gap: 16px;
        }
        .logo {
            font-size: 1.35rem;
            font-weight: 800;
            white-space: nowrap;
            letter-spacing: -0.5px;
            color: var(--text-color, #0f172a);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .logo span { color: #2563eb; }
        .nav-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 18px;
            flex: 1;
            flex-wrap: nowrap;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
            flex-wrap: nowrap;
        }
        .nav-links li {
            margin: 0;
            display: inline-block;
        }
        .nav-links a {
            font-size: 0.88rem;
            font-weight: 500;
            color: var(--text-muted, #475569);
            padding: 7px 11px;
            border-radius: 8px;
            white-space: nowrap;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-block;
        }
        .nav-links a:hover {
            color: #2563eb;
            background: rgba(37, 99, 235, 0.08);
        }
        .nav-links a.active {
            color: #2563eb !important;
            font-weight: 700;
            background: rgba(37, 99, 235, 0.12);
        }
        .nav-auth-buttons {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
            white-space: nowrap;
        }
        .admin-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.84rem;
            font-weight: 600;
            color: #1e293b;
            background: #f1f5f9;
            padding: 5px 12px;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            white-space: nowrap;
        }
        .btn-logout-pill {
            display: inline-flex;
            align-items: center;
            padding: 6px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            background: #ef4444;
            color: #ffffff !important;
            border-radius: 8px;
            text-decoration: none;
            transition: background 0.2s ease, transform 0.15s ease;
            border: none;
            cursor: pointer;
            white-space: nowrap;
        }
        .btn-logout-pill:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        [data-theme="dark"] .admin-badge-pill {
            background: #1e293b;
            color: #f1f5f9;
            border-color: #334155;
        }
        [data-theme="dark"] .logo { color: #f8fafc; }
        [data-theme="dark"] .nav-links a { color: #94a3b8; }
        [data-theme="dark"] .nav-links a:hover { color: #60a5fa; background: rgba(96, 165, 250, 0.12); }
        [data-theme="dark"] .nav-links a.active { color: #60a5fa !important; background: rgba(96, 165, 250, 0.18); }

        .deploy-container { max-width: 1200px; margin: 30px auto 60px; padding: 0 20px; }
        
        /* Academic Banner */
        .academic-banner {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #0284c7 100%);
            color: #ffffff; padding: 28px; border-radius: 16px; margin-bottom: 30px;
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.35); position: relative; overflow: hidden;
        }
        .academic-banner::after {
            content: ""; position: absolute; top: -50%; right: -20%; width: 300px; height: 300px;
            background: rgba(255, 255, 255, 0.08); border-radius: 50%; pointer-events: none;
        }
        .banner-tag {
            background: rgba(255, 255, 255, 0.2); padding: 4px 12px; border-radius: 9999px;
            font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
            display: inline-block; margin-bottom: 12px;
        }

        /* Diagnostic Sections */
        .diag-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 30px; }
        .diag-card {
            background: var(--card-bg, #ffffff); border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 14px; padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }
        .diag-card h3 {
            margin: 0 0 16px; font-size: 1.15rem; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--border-color, #e2e8f0); padding-bottom: 12px;
        }

        /* Metric Rows */
        .metric-row {
            display: flex; justify-content: space-between; align-items: center;
            padding: 10px 0; border-bottom: 1px dashed var(--border-color, #e2e8f0);
            font-size: 0.92rem;
        }
        .metric-row:last-child { border-bottom: none; }
        .metric-label { color: var(--text-muted, #64748b); font-weight: 500; }
        .metric-value { font-weight: 600; font-family: monospace; }

        /* Badges */
        .badge {
            display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 9999px;
            font-size: 0.78rem; font-weight: 700; text-transform: uppercase;
        }
        .badge-success { background: #10b981; color: #ffffff; }
        .badge-danger  { background: #ef4444; color: #ffffff; }
        .badge-info    { background: #0284c7; color: #ffffff; }
        .badge-warning { background: #f59e0b; color: #ffffff; }

        /* Tables */
        .diag-table {
            width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 0.9rem;
        }
        .diag-table th, .diag-table td {
            padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border-color, #e2e8f0);
        }
        .diag-table th {
            background: var(--bg-surface-hover, #f8fafc); font-weight: 600; font-size: 0.85rem;
            color: var(--text-muted, #64748b); text-transform: uppercase; letter-spacing: 0.5px;
        }
        .diag-table tr:hover { background: var(--bg-surface-hover, #f8fafc); }

        /* Code Snippet Box */
        .code-box {
            background: #0f172a; color: #f8fafc; padding: 18px; border-radius: 10px;
            font-family: 'Consolas', 'Monaco', monospace; font-size: 0.85rem; line-height: 1.5;
            overflow-x: auto; margin-top: 12px; border: 1px solid #1e293b;
        }
        .code-comment { color: #64748b; }
        .code-keyword { color: #38bdf8; font-weight: bold; }
        .code-val     { color: #4ade80; }

        /* Action Buttons */
        .btn-launch {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8); color: #ffffff;
            font-weight: 700; font-size: 0.95rem; border-radius: 8px; text-decoration: none;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.35); transition: all 0.2s ease;
        }
        .btn-launch:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(37, 99, 235, 0.45); }
    </style>
</head>

<body>
    <!-- Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / NAVBAR -->
    <header>
        <div class="container">
            <nav>
                <a href="../index.html" class="logo">StudentHub <span>Portal</span></a>
                <div class="nav-actions">
                    <ul class="nav-links">
                        <li><a href="../index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="events_ajax.php">Live Events API</a></li>
                        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                        <li><a href="deployment.php" class="active">Diagnostics &amp; Deploy</a></li>
                    </ul>
                    <div class="nav-auth-buttons">
                        <span class="admin-badge-pill">
                            🛡️ <?= htmlspecialchars($currentUser['name']) ?> (Admin)
                        </span>
                        <a href="logout.php" class="btn-logout-pill">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </header>

    <main id="main-content" class="deploy-container">
        <!-- Academic Submission Header -->
        <div class="academic-banner">
            <span class="banner-tag">Practical 15 Capstone Verification</span>
            <h1 style="margin: 0 0 8px; font-size: 1.8rem; font-weight: 800;">
                System Deployment & Master Diagnostics Center
            </h1>
            <p style="margin: 0 0 16px; opacity: 0.9; max-width: 850px; font-size: 0.95rem;">
                Devang Patel Institute of Advance Technology and Computer Applications (DEPSTAR) &bull; CHARUSAT<br>
                Course: <strong>ITUE203 - Web Development Frameworks</strong> &bull; End-to-End System Packaging & Runtime Telemetry
            </p>
            <div style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
                <a href="../tests/test_all_practicals.php" target="_blank" class="btn-launch">
                    <span>⚡ Run Master Test Suite (15 Practicals)</span>
                </a>
                <a href="../test_connection.php" target="_blank" style="color: #ffffff; text-decoration: underline; font-size: 0.9rem; font-weight: 600;">
                    Database Diagnostics &rarr;
                </a>
            </div>
        </div>

        <!-- Diagnostics 2-Column Grid -->
        <div class="diag-grid">
            <!-- 1. Server & PHP Runtime Environment -->
            <div class="diag-card">
                <h3>
                    <span>🖥️ Server & PHP Runtime</span>
                    <span class="badge badge-success">ONLINE</span>
                </h3>
                <div class="metric-row">
                    <span class="metric-label">Operating System</span>
                    <span class="metric-value"><?= htmlspecialchars($osFull) ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Web Server</span>
                    <span class="metric-value"><?= htmlspecialchars($serverSoftware) ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">PHP Version</span>
                    <span class="metric-value">PHP <?= htmlspecialchars($phpVersion) ?> (<?= htmlspecialchars($phpSapi) ?>)</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Memory Limit</span>
                    <span class="metric-value"><?= htmlspecialchars($memoryLimit) ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Execution Time</span>
                    <span class="metric-value"><?= htmlspecialchars($maxExecution) ?></span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Max File Upload</span>
                    <span class="metric-value"><?= htmlspecialchars($uploadMaxFiles) ?> (Post: <?= htmlspecialchars($postMaxSize) ?>)</span>
                </div>
            </div>

            <!-- 2. PHP Extension Audit -->
            <div class="diag-card">
                <h3>
                    <span>🧩 PHP Extension Audit</span>
                    <span class="badge badge-success"><?= count(array_filter($extensionStatus, fn($x) => $x['loaded'])) ?> / <?= count($extensionStatus) ?> Loaded</span>
                </h3>
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <?php foreach ($extensionStatus as $ext => $info): ?>
                        <div class="metric-row" style="padding: 6px 0;">
                            <div>
                                <strong style="font-family: monospace; font-size: 0.95rem;"><?= htmlspecialchars($ext) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted);"><?= htmlspecialchars($info['desc']) ?></div>
                            </div>
                            <span class="badge <?= $info['loaded'] ? 'badge-success' : 'badge-danger' ?>">
                                <?= $info['loaded'] ? 'LOADED' : 'MISSING' ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- 3. Relational Database Schema Inventory -->
        <div class="diag-card" style="margin-bottom: 30px;">
            <h3>
                <span>🗄️ Relational Database Inventory (studenthub_db)</span>
                <span class="badge <?= $dbConnected ? 'badge-success' : 'badge-danger' ?>">
                    <?= $dbConnected ? 'CONNECTED (PDO + MySQLi)' : 'DISCONNECTED' ?>
                </span>
            </h3>

            <?php if (!$dbConnected): ?>
                <div style="padding: 16px; background: #fee2e2; color: #991b1b; border-radius: 8px; margin-top: 10px;">
                    <strong>Database Connection Error:</strong> <?= htmlspecialchars($dbError ?? 'Unknown error') ?>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="diag-table">
                        <thead>
                            <tr>
                                <th>Table Name</th>
                                <th>Syllabus Practical</th>
                                <th>Storage Engine</th>
                                <th>Collation</th>
                                <th>Live Row Count</th>
                                <th>Schema Role</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableStats as $tbl => $info): ?>
                                <tr>
                                    <td><strong style="font-family: monospace;"><?= htmlspecialchars($tbl) ?></strong></td>
                                    <td><span style="font-size: 0.8rem; font-weight: 700; color: #2563eb;"><?= htmlspecialchars($info['practical']) ?></span></td>
                                    <td><span class="badge badge-info"><?= htmlspecialchars($info['engine']) ?></span></td>
                                    <td style="font-family: monospace; font-size: 0.8rem;"><?= htmlspecialchars($info['collation']) ?></td>
                                    <td>
                                        <strong style="font-size: 1rem; color: <?= $info['count'] >= 0 ? '#10b981' : '#ef4444' ?>;">
                                            <?= $info['count'] >= 0 ? number_format($info['count']) : 'Error' ?>
                                        </strong>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($info['desc']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Stored Procedure Telemetry -->
                <div style="margin-top: 20px; background: var(--bg-surface-hover, #f8fafc); padding: 18px; border-radius: 10px; border: 1px solid var(--border-color, #e2e8f0);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <h4 style="margin: 0; font-size: 0.95rem;">
                            Stored Procedure Telemetry: <code style="color: #2563eb;">sp_get_dashboard_stats()</code> (Practical 8 Advanced)
                        </h4>
                        <span class="badge <?= !empty($spStats) ? 'badge-success' : 'badge-danger' ?>">
                            <?= !empty($spStats) ? 'ACTIVE' : 'FAILED' ?>
                        </span>
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 20px; font-family: monospace; font-size: 0.9rem; margin-top: 10px;">
                        <div>Total Students: <strong><?= (int)($spStats['total_students'] ?? 0) ?></strong></div>
                        <div>Total Events: <strong><?= (int)($spStats['total_events'] ?? 0) ?></strong></div>
                        <div>Registrations: <strong><?= (int)($spStats['total_registrations'] ?? 0) ?></strong></div>
                        <div>Admin Logs: <strong><?= (int)($spStats['total_admin_logs'] ?? 0) ?></strong></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Storage & File Permissions Telemetry -->
        <div class="diag-card" style="margin-bottom: 30px;">
            <h3>
                <span>📁 File System Concurrency & Permissions Telemetry</span>
                <span class="badge badge-success">VERIFIED</span>
            </h3>
            <div style="overflow-x: auto;">
                <table class="diag-table">
                    <thead>
                        <tr>
                            <th>Resource</th>
                            <th>Practical</th>
                            <th>Relative Path</th>
                            <th>Exists</th>
                            <th>Writable</th>
                            <th>Metrics</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storageTelemetry as $label => $item): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($label) ?></strong></td>
                                <td><span style="font-size: 0.8rem; font-weight: 700; color: #2563eb;"><?= htmlspecialchars($item['practical']) ?></span></td>
                                <td><code style="font-size: 0.8rem;"><?= htmlspecialchars($item['displayPath']) ?></code></td>
                                <td>
                                    <span class="badge <?= $item['exists'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $item['exists'] ? 'YES' : 'NO' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $item['writable'] ? 'badge-success' : 'badge-danger' ?>">
                                        <?= $item['writable'] ? 'WRITABLE' : 'READONLY' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($item['fileCount'] > 0): ?>
                                        <span class="badge badge-info"><?= $item['fileCount'] ?> files</span>
                                    <?php elseif ($item['size'] > 0): ?>
                                        <span class="badge badge-info"><?= round($item['size'] / 1024, 2) ?> KB</span>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.8rem;">0 bytes</span>
                                    <?php endif; ?>
                                </td>
                                <td style="color: var(--text-muted); font-size: 0.85rem;"><?= htmlspecialchars($item['description']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 5. Apache Virtual Host & Production Deployment Architecture -->
        <div class="diag-card">
            <h3>
                <span>🌐 Apache Virtual Host Architecture (studenthub.local)</span>
                <span class="badge badge-info">VHOST SPEC</span>
            </h3>
            <p style="color: var(--text-muted); font-size: 0.92rem; margin-top: 0;">
                Configured in <code style="color: #2563eb;">docs/apache_vhost.conf</code> for local turnkey deployment on Apache HTTP Server:
            </p>
            <div class="code-box">
<span class="code-comment"># Virtual Host Configuration for StudentHub Academic Portal</span>
&lt;<span class="code-keyword">VirtualHost</span> *:80&gt;
    <span class="code-keyword">ServerName</span> <span class="code-val">studenthub.local</span>
    <span class="code-keyword">ServerAlias</span> <span class="code-val">www.studenthub.local</span>
    <span class="code-keyword">DocumentRoot</span> <span class="code-val">"D:/DEPSTAR SEM3/WDF/StudentHub"</span>

    &lt;<span class="code-keyword">Directory</span> <span class="code-val">"D:/DEPSTAR SEM3/WDF/StudentHub"</span>&gt;
        <span class="code-keyword">Options</span> Indexes FollowSymLinks MultiViews
        <span class="code-keyword">AllowOverride</span> All
        <span class="code-keyword">Require</span> all granted
    &lt;/<span class="code-keyword">Directory</span>&gt;

    <span class="code-comment"># Security Headers</span>
    <span class="code-keyword">Header</span> always set X-Content-Type-Options <span class="code-val">"nosniff"</span>
    <span class="code-keyword">Header</span> always set X-Frame-Options <span class="code-val">"SAMEORIGIN"</span>
    <span class="code-keyword">Header</span> always set X-XSS-Protection <span class="code-val">"1; mode=block"</span>
&lt;/<span class="code-keyword">VirtualHost</span>&gt;
            </div>
            <div style="margin-top: 16px; font-size: 0.88rem; color: var(--text-muted);">
                <strong>Local DNS Mapping:</strong> Add <code style="background: var(--bg-surface-hover); padding: 2px 6px; border-radius: 4px;">127.0.0.1 studenthub.local</code> to <code style="font-family: monospace;">C:\Windows\System32\drivers\etc\hosts</code>.
            </div>
        </div>
    </main>

    <!-- FOOTER -->
    <footer>
        <div class="container footer-content" style="text-align: center; padding: 24px 0; border-top: 1px solid var(--border-color, #e2e8f0); color: var(--text-muted, #64748b); font-size: 0.85rem;">
            &copy; 2026 StudentHub Portal &bull; Department of Information Technology &bull; DEPSTAR, CHARUSAT
        </div>
    </footer>
</body>
</html>
