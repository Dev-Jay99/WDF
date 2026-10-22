<?php
/**
 * StudentHub Portal - Authentication, Session Security & RBAC Guard
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me Token
 * - Problem Definition: Sessions, password_verify(), session_regenerate_id(), RBAC, session timeout
 * - Intermediate Extension: Update last_login timestamp on successful authentication
 * - Advanced Extension: Cryptographic Remember-Me token hashed with SHA-256 in DB (avoids custom JWTs)
 */

require_once __DIR__ . '/db.php';

// Configure session cookie security flags before starting
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Session Idle Timeout in seconds (15 minutes default)
defined('SESSION_TIMEOUT_DURATION') || define('SESSION_TIMEOUT_DURATION', 900);

/**
 * Check if the user is currently authenticated
 *
 * @return bool
 */
function is_logged_in(): bool {
    check_session_timeout();
    if (!isset($_SESSION['user_id'])) {
        check_remember_me_cookie();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Retrieve active user data from the session
 *
 * @return array|null
 */
function get_logged_in_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? 'Student',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'student',
    ];
}

/**
 * Check session idle timeout (Practical 10 Problem Definition)
 */
function check_session_timeout(): void {
    if (isset($_SESSION['user_id'])) {
        $now = time();
        if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > SESSION_TIMEOUT_DURATION)) {
            // Idle timeout expired
            $userId = $_SESSION['user_id'];
            logout_user(false);
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['auth_flash_error'] = 'Your session has timed out due to 15 minutes of inactivity. Please log in again.';
            header('Location: login.php?reason=timeout');
            exit;
        }
        $_SESSION['last_activity'] = $now;
    }
}

/**
 * Check and restore user session from Remember-Me cookie (Practical 10 Advanced Extension)
 */
function check_remember_me_cookie(): void {
    if (isset($_SESSION['user_id']) || empty($_COOKIE['studenthub_remember'])) {
        return;
    }

    $rawToken = $_COOKIE['studenthub_remember'];
    $tokenHash = hash('sha256', $rawToken);

    try {
        $pdo = get_pdo_connection();
        $stmt = $pdo->prepare("
            SELECT u.id, u.name, u.email, u.role, u.status 
            FROM remember_tokens rt
            JOIN users u ON rt.user_id = u.id
            WHERE rt.token_hash = :hash AND rt.expires_at > NOW() AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([':hash' => $tokenHash]);
        $user = $stmt->fetch();

        if ($user) {
            // Restore session
            session_regenerate_id(true);
            $_SESSION['user_id']       = (int)$user['id'];
            $_SESSION['user_name']     = $user['name'];
            $_SESSION['user_email']    = $user['email'];
            $_SESSION['user_role']     = $user['role'];
            $_SESSION['last_activity'] = time();

            // Record audit log entry
            log_audit_event((int)$user['id'], 'LOGIN_REMEMBER_ME', 'users', (int)$user['id'], 'Session restored via Remember-Me token');
        } else {
            // Invalid or expired token: clear cookie
            setcookie('studenthub_remember', '', time() - 3600, '/', '', false, true);
        }
    } catch (Exception $e) {
        error_log("Remember-Me check error: " . $e->getMessage());
    }
}

/**
 * Log in a user securely with session regeneration and remember-me option
 *
 * @param array $user User database row
 * @param bool $remember Whether Remember-Me was checked
 */
function login_user(array $user, bool $remember = false): void {
    // Regenerate session ID to mitigate session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']       = (int)$user['id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['user_email']    = $user['email'];
    $_SESSION['user_role']     = $user['role'];
    $_SESSION['last_activity'] = time();

    $now = date('Y-m-d H:i:s');
    $userId = (int)$user['id'];

    // Practical 10 Intermediate Extension: Update last_login timestamp
    try {
        $pdo = get_pdo_connection();
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = :now WHERE id = :id");
        $updateStmt->execute([':now' => $now, ':id' => $userId]);

        // Practical 10 Advanced Extension: Store Remember-Me random token as SHA-256 hash in DB
        if ($remember) {
            $rawToken = bin2hex(random_bytes(32)); // 64 hex characters of entropy
            $tokenHash = hash('sha256', $rawToken);
            $expiresAt = date('Y-m-d H:i:s', time() + (86400 * 30)); // 30 days validity

            $tokenStmt = $pdo->prepare("
                INSERT INTO remember_tokens (user_id, token_hash, expires_at)
                VALUES (:user_id, :token_hash, :expires_at)
            ");
            $tokenStmt->execute([
                ':user_id'    => $userId,
                ':token_hash' => $tokenHash,
                ':expires_at' => $expiresAt
            ]);

            // Set secure, HTTP-only cookie
            setcookie('studenthub_remember', $rawToken, time() + (86400 * 30), '/', '', false, true);
        }

        // Audit log
        log_audit_event($userId, 'LOGIN_SUCCESS', 'users', $userId, 'User authenticated successfully');
    } catch (Exception $e) {
        error_log("Login post-processing error: " . $e->getMessage());
    }
}

/**
 * Log out user: destroy session and remove remember-me token
 *
 * @param bool $recordAudit Whether to log logout in audit table
 */
function logout_user(bool $recordAudit = true): void {
    if (isset($_SESSION['user_id']) && $recordAudit) {
        log_audit_event((int)$_SESSION['user_id'], 'LOGOUT', 'users', (int)$_SESSION['user_id'], 'User logged out');
    }

    // Clear Remember-Me token from database and browser
    if (!empty($_COOKIE['studenthub_remember'])) {
        $rawToken = $_COOKIE['studenthub_remember'];
        $tokenHash = hash('sha256', $rawToken);
        try {
            $pdo = get_pdo_connection();
            $stmt = $pdo->prepare("DELETE FROM remember_tokens WHERE token_hash = :hash");
            $stmt->execute([':hash' => $tokenHash]);
        } catch (Exception $e) {
            error_log("Error removing remember token: " . $e->getMessage());
        }
        setcookie('studenthub_remember', '', time() - 3600, '/', '', false, true);
    }

    // Unset all session variables and destroy session
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

/**
 * Restrict page access to logged-in users only
 *
 * @param string|null $redirectAfter Path to return after login
 */
function require_login(?string $redirectAfter = null): void {
    if (!is_logged_in()) {
        $loginUrl = 'login.php';
        if ($redirectAfter) {
            $loginUrl .= '?redirect=' . urlencode($redirectAfter);
        }
        header('Location: ' . $loginUrl);
        exit;
    }
}

/**
 * Restrict page access to specific user role (Practical 10 & 14 RBAC)
 *
 * @param string $requiredRole 'admin' or 'student'
 */
function require_role(string $requiredRole): void {
    require_login();
    if ($_SESSION['user_role'] !== $requiredRole) {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>403 Forbidden | Access Denied - StudentHub</title>
            <link rel="stylesheet" href="../css/style.css">
        </head>
        <body style="display:flex;align-items:center;justify-content:center;height:100vh;margin:0;font-family:sans-serif;background:#f8fafc;">
            <div style="background:#fff;padding:40px;border-radius:12px;border:1px solid #e2e8f0;text-align:center;max-width:480px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);">
                <h1 style="color:#ef4444;margin:0 0 10px 0;">403 - Access Denied</h1>
                <p style="color:#64748b;margin-bottom:24px;">This area requires administrative privileges. Your current role is <strong><?= htmlspecialchars($_SESSION['user_role'] ?? 'Guest') ?></strong>.</p>
                <a href="dashboard.php" class="btn btn-primary" style="display:inline-block;padding:10px 20px;border-radius:6px;text-decoration:none;background:#2563eb;color:#fff;">Return to My Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

/**
 * Helper to record audit log actions (Practicals 9, 12, 14)
 */
function log_audit_event(?int $userId, string $action, string $entity, ?int $entityId, ?string $details): void {
    try {
        $pdo = get_pdo_connection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address)
            VALUES (:user_id, :action, :entity, :entity_id, :details, :ip)
        ");
        $stmt->execute([
            ':user_id'   => $userId,
            ':action'    => $action,
            ':entity'    => $entity,
            ':entity_id' => $entityId,
            ':details'   => $details,
            ':ip'        => $ip
        ]);
    } catch (Exception $e) {
        error_log("Audit log recording failed: " . $e->getMessage());
    }
}
