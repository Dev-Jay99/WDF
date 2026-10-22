<?php
/**
 * StudentHub Portal - Central Database Connection Handler
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 8: MySQL Schema Design, ER Model, PDO Connectivity, and Prepared Statements
 * - Problem Definition: PHP database connection using MySQL statements
 * - Intermediate Extension: PHP PDO and Prepared Statements
 * - Advanced Extension: Stored procedure execution support
 */

// Database Configuration Constants
defined('DB_HOST') || define('DB_HOST', '127.0.0.1');
defined('DB_USER') || define('DB_USER', 'root');
defined('DB_PASS') || define('DB_PASS', '');
defined('DB_NAME') || define('DB_NAME', 'studenthub_db');
defined('DB_PORT') || define('DB_PORT', 3306);
defined('DB_CHARSET') || define('DB_CHARSET', 'utf8mb4');

/**
 * Get MySQLi connection instance (Practical 8 Problem Definition & Practical 9, 11)
 *
 * @return mysqli
 * @throws Exception
 */
function get_mysqli_connection(): mysqli {
    static $mysqli = null;

    if ($mysqli !== null && @$mysqli->ping()) {
        return $mysqli;
    }

    // Enable reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, (int)DB_PORT);
        $mysqli->set_charset(DB_CHARSET);
        return $mysqli;
    } catch (mysqli_sql_exception $e) {
        error_log("StudentHub MySQLi Connection Error: " . $e->getMessage());
        throw new Exception("Database connection failed. Please ensure MySQL server is running in XAMPP: " . $e->getMessage());
    }
}

/**
 * Get PDO connection instance with Prepared Statement safety (Practical 8 Intermediate Extension)
 *
 * @return PDO
 * @throws PDOException
 */
function get_pdo_connection(): PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false, // Native prepared statements
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log("StudentHub PDO Connection Error: " . $e->getMessage());
        throw new PDOException("Database connection failed. Please verify MySQL service in XAMPP: " . $e->getMessage());
    }
}

/**
 * Execute Stored Procedure: sp_get_dashboard_stats (Practical 8 Advanced Extension)
 *
 * @return array Associative array of aggregated metrics
 */
function execute_sp_dashboard_stats(): array {
    $pdo = get_pdo_connection();
    $stmt = $pdo->prepare("CALL sp_get_dashboard_stats()");
    $stmt->execute();
    $stats = $stmt->fetch() ?: [];
    $stmt->closeCursor();
    return $stats;
}

/**
 * Diagnostic helper to test both MySQLi and PDO connectivity
 *
 * @return array Diagnostic test results
 */
function test_db_connections(): array {
    $result = [
        'mysqli' => ['status' => false, 'message' => ''],
        'pdo'    => ['status' => false, 'message' => ''],
        'sp'     => ['status' => false, 'message' => '']
    ];

    // 1. Test MySQLi
    try {
        $mysqli = get_mysqli_connection();
        $result['mysqli']['status'] = true;
        $result['mysqli']['message'] = "MySQLi connected successfully (Server: " . $mysqli->server_info . ")";
    } catch (Exception $e) {
        $result['mysqli']['message'] = $e->getMessage();
    }

    // 2. Test PDO
    try {
        $pdo = get_pdo_connection();
        $result['pdo']['status'] = true;
        $result['pdo']['message'] = "PDO connected successfully (Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . ")";
    } catch (Exception $e) {
        $result['pdo']['message'] = $e->getMessage();
    }

    // 3. Test Stored Procedure
    if ($result['pdo']['status']) {
        try {
            $stats = execute_sp_dashboard_stats();
            $result['sp']['status'] = true;
            $result['sp']['message'] = "Stored Procedure executed successfully (" . json_encode($stats) . ")";
        } catch (Exception $e) {
            $result['sp']['message'] = "Stored Procedure error: " . $e->getMessage();
        }
    }

    return $result;
}
