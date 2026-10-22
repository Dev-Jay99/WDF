<?php
/**
 * StudentHub Portal - Secure Logout Handler
 * Practical 10: Secure Login/Logout with Sessions and Remember-Me Token Invalidation
 */

require_once __DIR__ . '/../includes/auth.php';

logout_user(true);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['auth_flash_success'] = 'You have been successfully logged out.';

header('Location: login.php');
exit;
