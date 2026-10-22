<?php
/**
 * StudentHub Portal - Stored Records Viewer
 * Practical 7: Intermediate Extension - Display stored CSV/JSON records on a webpage
 */
require_once __DIR__ . '/../includes/form_helpers.php';

$csvPath  = __DIR__ . '/../data/registrations.csv';
$jsonPath = __DIR__ . '/../data/registrations.json';

// Handle Direct File Download
if (isset($_GET['download'])) {
    $format = $_GET['download'];
    if ($format === 'csv' && file_exists($csvPath)) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="studenthub_registrations_' . date('Ymd_His') . '.csv"');
        readfile($csvPath);
        exit;
    } elseif ($format === 'json' && file_exists($jsonPath)) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="studenthub_registrations_' . date('Ymd_His') . '.json"');
        readfile($jsonPath);
        exit;
    }
}

// Fetch stored records
$csvRecords  = read_csv_records($csvPath);
$jsonRecords = read_json_records($jsonPath);

// Selected view tab (csv or json)
$viewMode = $_GET['view'] ?? 'csv';
$activeRecords = ($viewMode === 'json') ? $jsonRecords : $csvRecords;

// Metrics aggregation
$totalCount = count($csvRecords);
$ceCount  = 0;
$itCount  = 0;
$cseCount = 0;

foreach ($csvRecords as $rec) {
    $c = $rec['Course'] ?? $rec['course'] ?? '';
    if (strpos($c, 'CE') !== false)  $ceCount++;
    if (strpos($c, 'IT') !== false)  $itCount++;
    if (strpos($c, 'CSE') !== false) $cseCount++;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="View all submitted student registrations stored in CSV and JSON file formats">
    <title>Stored Records Viewer | StudentHub Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .records-container {
            padding: 32px 0 60px;
        }
        .records-header {
            margin-bottom: 28px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            flex-wrap: wrap;
            gap: 16px;
        }
        .records-header h2 {
            font-size: 1.85rem;
            font-weight: 700;
            color: var(--bs-dark);
            margin: 0 0 6px;
        }
        .records-header p {
            color: var(--bs-secondary);
            margin: 0;
            font-size: 0.95rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--card-shadow-hover);
        }
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(37, 99, 235, 0.1);
            color: var(--bs-primary);
        }
        .stat-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--bs-success);
        }
        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--bs-warning);
        }
        .stat-icon.info {
            background: rgba(6, 182, 212, 0.1);
            color: var(--bs-info);
        }
        .stat-data h3 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0;
            color: var(--bs-dark);
        }
        .stat-data p {
            margin: 2px 0 0;
            font-size: 0.85rem;
            color: var(--bs-secondary);
        }
        .controls-card {
            background: var(--bg-card);
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            padding: 20px;
            box-shadow: var(--card-shadow);
            margin-bottom: 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }
        .view-switcher {
            display: inline-flex;
            background: var(--toggle-bg);
            padding: 4px;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color);
        }
        .view-tab {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.88rem;
            font-weight: 600;
            color: var(--bs-secondary);
            transition: all 0.2s ease;
        }
        .view-tab.active {
            background: var(--bs-primary);
            color: #ffffff;
            box-shadow: 0 2px 4px rgba(37, 99, 235, 0.25);
        }
        .search-box {
            position: relative;
            min-width: 280px;
            flex-grow: 1;
            max-width: 400px;
        }
        .search-box input {
            width: 100%;
            padding: 10px 14px 10px 38px;
            border-radius: 8px;
            border: 1px solid var(--bs-border-color);
            background: var(--bg-input);
            color: var(--bs-body-color);
            font-size: 0.9rem;
            outline: none;
        }
        .search-box input:focus {
            border-color: var(--bs-primary);
        }
        .search-box-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--bs-secondary);
            pointer-events: none;
        }
        .actions-btn-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 14px;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .btn-outline {
            border: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
            background: var(--bg-card);
        }
        .btn-outline:hover {
            border-color: var(--bs-primary);
            color: var(--bs-primary);
        }
        .table-responsive {
            background: var(--bg-card);
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            overflow-x: auto;
            box-shadow: var(--card-shadow);
        }
        .records-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            text-align: left;
        }
        .records-table th {
            background: var(--table-th-bg);
            padding: 14px 18px;
            color: var(--bs-dark);
            font-weight: 600;
            border-bottom: 2px solid var(--bs-border-color);
            white-space: nowrap;
        }
        .records-table td {
            padding: 14px 18px;
            border-bottom: 1px solid var(--bs-border-color);
            color: var(--bs-body-color);
            vertical-align: middle;
        }
        .records-table tr:hover td {
            background-color: rgba(37, 99, 235, 0.03);
        }
        .badge-dept {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(37, 99, 235, 0.1);
            color: var(--bs-primary);
        }
        .badge-id {
            font-family: monospace;
            background: rgba(100, 116, 139, 0.1);
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.82rem;
            color: var(--bs-dark);
            font-weight: 600;
        }
        .badge-hash {
            font-family: monospace;
            font-size: 0.72rem;
            color: var(--bs-secondary);
            max-width: 120px;
            display: inline-block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .empty-state {
            padding: 50px 20px;
            text-align: center;
            color: var(--bs-secondary);
        }
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 12px;
        }
    </style>
</head>

<body>
    <!-- Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Global Header -->
    <header class="site-header" role="banner">
        <div class="container header-container">
            <a href="../index.html" class="logo" aria-label="StudentHub Portal Home">
                <span class="logo-icon">&#x1F393;</span>
                <span class="logo-text">Student<strong>Hub</strong></span>
            </a>

            <nav class="main-nav" id="mainNav" aria-label="Main Navigation">
                <ul class="nav-list">
                    <li><a href="../index.html">Home</a></li>
                    <li><a href="about.html">About</a></li>
                    <li><a href="events.html">Events</a></li>
                    <li><a href="courses.html">Courses</a></li>
                    <li><a href="attendance.html">Attendance</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="faq.html">FAQ</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>

                <div class="nav-actions">
                    <ul class="auth-links">
                        <li><a href="view_records.php" class="btn-records active" style="font-weight: 600; color: var(--bs-primary); margin-right: 8px;">View Records</a></li>
                        <li><a href="login.php" class="btn-login">Login</a></li>
                        <li><a href="register.php" class="btn-register">Register</a></li>
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
        <div class="container records-container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item"><a href="register.php">Registration</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Stored Records Viewer</li>
                </ol>
            </nav>

            <!-- Page Header -->
            <div class="records-header">
                <div>
                    <h2>Stored Registrations Archive</h2>
                    <p>Records persisted via safe file locking (<code>flock(LOCK_EX)</code>) in <strong>CSV</strong> and <strong>JSON</strong> storage engines.</p>
                </div>
                <div class="actions-btn-group">
                    <a href="register.php" class="btn-action" style="background: var(--bs-primary); color: #fff;">
                        &#x2795; Register New Student
                    </a>
                </div>
            </div>

            <!-- Stats Overview Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">&#x1F465;</div>
                    <div class="stat-data">
                        <h3><?php echo $totalCount; ?></h3>
                        <p>Total Records</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon info">&#x1F4BB;</div>
                    <div class="stat-data">
                        <h3><?php echo $ceCount; ?></h3>
                        <p>B.Tech CE</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon success">&#x1F310;</div>
                    <div class="stat-data">
                        <h3><?php echo $itCount; ?></h3>
                        <p>B.Tech IT</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon warning">&#x2699;&#xFE0F;</div>
                    <div class="stat-data">
                        <h3><?php echo $cseCount; ?></h3>
                        <p>B.Tech CSE</p>
                    </div>
                </div>
            </div>

            <!-- Controls & Storage Switcher Bar -->
            <div class="controls-card">
                <!-- Practical 7 Extension: Storage Format Switcher -->
                <div class="view-switcher" role="tablist">
                    <a href="?view=csv" class="view-tab <?php echo ($viewMode === 'csv') ? 'active' : ''; ?>" role="tab">
                        &#x1F4C4; CSV Storage (<code>registrations.csv</code>)
                    </a>
                    <a href="?view=json" class="view-tab <?php echo ($viewMode === 'json') ? 'active' : ''; ?>" role="tab">
                        &#x1F5C4;&#xFE0F; JSON Storage (<code>registrations.json</code>)
                    </a>
                </div>

                <!-- Instant Search Filter -->
                <div class="search-box">
                    <span class="search-box-icon">&#x1F50D;</span>
                    <input type="text" id="recordSearchInput" placeholder="Filter by Name, ID, Email, Course, or City..." onkeyup="filterRecordsTable()">
                </div>

                <!-- Direct File Downloads -->
                <div class="actions-btn-group">
                    <a href="?download=csv" class="btn-action btn-outline" title="Download raw CSV file">
                        &#x2B07;&#xFE0F; Download CSV
                    </a>
                    <a href="?download=json" class="btn-action btn-outline" title="Download formatted JSON file">
                        &#x2B07;&#xFE0F; Download JSON
                    </a>
                </div>
            </div>

            <!-- Stored Records Table -->
            <div class="table-responsive">
                <table class="records-table" id="recordsTable">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Mobile</th>
                            <th>Course</th>
                            <th>Year</th>
                            <th>Gender</th>
                            <th>Location</th>
                            <th>Timestamp</th>
                            <th>Password Hash (Bcrypt)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($activeRecords)): ?>
                            <tr>
                                <td colspan="10">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">&#x1F4DA;</div>
                                        <h4>No registration records found yet</h4>
                                        <p>Submit the registration form to create the first record in CSV/JSON format.</p>
                                        <a href="register.php" class="btn-action" style="background: var(--bs-primary); color: #fff; margin-top: 10px;">Go to Registration Form</a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($activeRecords as $row): 
                                $id       = $row['Student ID'] ?? $row['student_id'] ?? $row['Enrollment'] ?? $row['enrollment_no'] ?? '-';
                                $time     = $row['Timestamp'] ?? $row['timestamp'] ?? '-';
                                $name     = $row['Full Name'] ?? $row['full_name'] ?? $row['fullName'] ?? '-';
                                $email    = $row['Email'] ?? $row['email'] ?? '-';
                                $phone    = $row['Phone'] ?? $row['phone'] ?? '-';
                                $course   = $row['Course'] ?? $row['course'] ?? '-';
                                $year     = $row['Year'] ?? $row['year'] ?? '-';
                                $gender   = $row['Gender'] ?? $row['gender'] ?? '-';
                                $country  = $row['Country'] ?? $row['country'] ?? '';
                                $state    = $row['State'] ?? $row['state'] ?? '';
                                $city     = $row['City'] ?? $row['city'] ?? '';
                                $location = trim("$city, $state, $country", ', ');
                                $hash     = $row['Password Hash'] ?? $row['password_hash'] ?? '';
                            ?>
                                <tr>
                                    <td><span class="badge-id"><?php echo htmlspecialchars($id); ?></span></td>
                                    <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                                    <td><a href="mailto:<?php echo htmlspecialchars($email); ?>"><?php echo htmlspecialchars($email); ?></a></td>
                                    <td><?php echo htmlspecialchars($phone); ?></td>
                                    <td><span class="badge-dept"><?php echo htmlspecialchars($course); ?></span></td>
                                    <td><?php echo htmlspecialchars($year); ?></td>
                                    <td><?php echo htmlspecialchars($gender); ?></td>
                                    <td><?php echo htmlspecialchars($location ?: '-'); ?></td>
                                    <td style="font-size: 0.82rem; color: var(--bs-secondary);"><?php echo htmlspecialchars($time); ?></td>
                                    <td><span class="badge-hash" title="<?php echo htmlspecialchars($hash); ?>"><?php echo htmlspecialchars($hash); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <footer class="site-footer" role="contentinfo">
        <div class="container">
            <p>&copy; 2026-27 StudentHub Portal - Web Development Frameworks (ITUE203) | DEPSTAR, CHARUSAT</p>
        </div>
    </footer>

    <script src="../js/script.js"></script>
    <script>
        // Client-side quick filter for the records table
        function filterRecordsTable() {
            var input = document.getElementById("recordSearchInput");
            var filter = input.value.toLowerCase();
            var table = document.getElementById("recordsTable");
            var trs = table.getElementsByTagName("tr");

            // Loop starting from row 1 (skipping header)
            for (var i = 1; i < trs.length; i++) {
                var rowText = trs[i].textContent || trs[i].innerText;
                if (rowText.toLowerCase().indexOf(filter) > -1) {
                    trs[i].style.display = "";
                } else {
                    trs[i].style.display = "none";
                }
            }
        }
    </script>
</body>
</html>
