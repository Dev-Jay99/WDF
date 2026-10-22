<?php
/**
 * StudentHub Portal - Secure Login Page
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 10: Secure Login/Logout with Sessions, Role-Based Access, Timeout, and Remember-Me Token
 */

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// If already authenticated, redirect to appropriate role dashboard
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'student';
    header('Location: ' . ($role === 'admin' ? 'admin_dashboard.php' : 'dashboard.php'));
    exit;
}

$flashError = $_SESSION['auth_flash_error'] ?? null;
$flashSuccess = $_SESSION['auth_flash_success'] ?? null;
unset($_SESSION['auth_flash_error'], $_SESSION['auth_flash_success']);

$redirectTarget = $_GET['redirect'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure StudentHub Authentication with Session Management, Role-Based Access, Timeout, and Cryptographic Remember-Me">
    <title>Login - StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/login.css">
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
                        <li><a href="../index.html">Home</a></li>
                        <li><a href="about.html">About</a></li>
                        <li><a href="events.html">Events</a></li>
                        <li><a href="faq.html">FAQ</a></li>
                        <li><a href="feedback.html">Feedback</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="login.php" class="btn-login active">Login</a></li>
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
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Student Login</li>
                </ol>
            </nav>

            <div class="auth-container">
                <div class="auth-card">
                    <div class="auth-header">
                        <h2>Login to StudentHub</h2>
                        <p>Welcome back! Authenticate with your registered account credentials.</p>
                    </div>

                    <!-- Flash messages -->
                    <?php if ($flashError): ?>
                        <div style="background: rgba(239, 68, 68, 0.12); border: 1px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                            <strong>Authentication Notice:</strong> <?= htmlspecialchars($flashError) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($flashSuccess): ?>
                        <div style="background: rgba(16, 185, 129, 0.12); border: 1px solid #10b981; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;">
                            <?= htmlspecialchars($flashSuccess) ?>
                        </div>
                    <?php endif; ?>

                    <form id="loginForm" action="process_login.php" method="POST" novalidate>
                        <?= csrf_field() ?>
                        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">

                        <div class="form-group">
                            <label for="email">University Email</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="student@charusat.edu.in" required autocomplete="username">
                            <small class="error-message" id="loginEmailError"></small>
                        </div>

                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required autocomplete="current-password">
                            <small class="error-message" id="loginPasswordError"></small>
                        </div>

                        <div class="form-actions-row">
                            <label class="remember-me">
                                <input type="checkbox" id="remember" name="remember" value="1"> Remember me (30 days)
                            </label>
                            <a href="#" class="forgot-link">Forgot Password?</a>
                        </div>

                        <button type="submit" class="btn btn-primary btn-full">Login</button>

                        <div class="demo-credentials-box">
                            <strong>Demo Accounts:</strong><br>
                            • <strong>Admin:</strong> <code>admin@charusat.edu.in</code> | Password: <code>Admin@123</code><br>
                            • <strong>Student:</strong> <code>student@charusat.edu.in</code> | Password: <code>Student@123</code>
                        </div>
                    </form>

                    <div class="auth-footer">
                        <p>Don't have an account? <a href="register.php">Register here</a></p>
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
