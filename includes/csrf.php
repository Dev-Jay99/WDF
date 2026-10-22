<?php
/**
 * StudentHub Portal - CSRF Protection Utility
 * Practical 7: Advanced Extension - CSRF Token Validation
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate or retrieve the current CSRF token from the session.
 * @return string 64-character hex token
 */
function get_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Output an HTML hidden input tag with the CSRF token.
 * @return string HTML hidden input
 */
function csrf_field(): string {
    $token = htmlspecialchars(get_csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">';
}

/**
 * Validate the submitted CSRF token against the session token.
 * Uses hash_equals for timing attack resistance.
 * @param string|null $submittedToken
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token(?string $submittedToken): bool {
    if (empty($submittedToken) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $submittedToken);
}
