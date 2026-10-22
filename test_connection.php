<?php
/**
 * StudentHub Portal - Practical 8 Database Connection Verification
 * Tests MySQLi, PDO, and Stored Procedure Execution
 */
require_once __DIR__ . '/includes/db.php';
$tests = test_db_connections();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connectivity Test - StudentHub</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-card {
            background: var(--card-bg, #ffffff);
            border: 1px solid var(--border-color, #e2e8f0);
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .badge-success {
            background: #10b981;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-danger {
            background: #ef4444;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
        <h1 style="margin-bottom: 8px;">Database Connectivity Verification</h1>
        <p style="color: var(--text-muted, #64748b); margin-bottom: 28px;">
            Verifies MySQL schema design, MySQLi connectivity, PHP PDO with Prepared Statements, and Stored Procedure execution for the StudentHub portal.
        </p>

        <!-- 1. MySQLi Test -->
        <div class="test-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">1. MySQLi Connection</h3>
                <span class="<?= $tests['mysqli']['status'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $tests['mysqli']['status'] ? 'PASSED' : 'DISCONNECTED' ?>
                </span>
            </div>
            <p style="margin: 0; font-family: monospace; font-size: 0.95rem;">
                <?= htmlspecialchars($tests['mysqli']['message']) ?>
            </p>
        </div>

        <!-- 2. PDO Test -->
        <div class="test-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">2. PHP PDO & Prepared Statements</h3>
                <span class="<?= $tests['pdo']['status'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $tests['pdo']['status'] ? 'PASSED' : 'DISCONNECTED' ?>
                </span>
            </div>
            <p style="margin: 0; font-family: monospace; font-size: 0.95rem;">
                <?= htmlspecialchars($tests['pdo']['message']) ?>
            </p>
        </div>

        <!-- 3. Stored Procedure Test -->
        <div class="test-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                <h3 style="margin: 0;">3. Stored Procedure Concept: sp_get_dashboard_stats</h3>
                <span class="<?= $tests['sp']['status'] ? 'badge-success' : 'badge-danger' ?>">
                    <?= $tests['sp']['status'] ? 'PASSED' : 'FAILED / OFF' ?>
                </span>
            </div>
            <p style="margin: 0; font-family: monospace; font-size: 0.95rem;">
                <?= htmlspecialchars($tests['sp']['message']) ?>
            </p>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="index.html" class="btn btn-primary" style="display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none;">Return to Home</a>
        </div>
    </div>
</body>
</html>
