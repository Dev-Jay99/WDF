<?php
/**
 * StudentHub Portal - Contact Form
 * Server-Side Form Processing with Validation and CSV/JSON Storage
 */
require_once __DIR__ . '/../includes/csrf.php';

$errors    = $_SESSION['contact_errors'] ?? [];
$formData  = $_SESSION['contact_data'] ?? [];
$success   = $_SESSION['contact_success'] ?? null;

unset($_SESSION['contact_errors'], $_SESSION['contact_data'], $_SESSION['contact_success']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StudentHub Portal - Contact and Inquiry Support Desk">
    <title>Contact Us - StudentHub Portal</title>
    <link rel="icon" href="../images/logo.png">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/contact.css">
    <style>
        .alert-banner {
            padding: 14px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: fadeIn 0.3s ease-in-out;
        }
        .alert-success {
            background-color: rgba(16, 185, 129, 0.12);
            border: 1px solid #10b981;
            color: #065f46;
        }
        [data-theme="dark"] .alert-success {
            background-color: rgba(16, 185, 129, 0.18);
            border-color: #059669;
            color: #6ee7b7;
        }
        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid #ef4444;
            color: #991b1b;
        }
        [data-theme="dark"] .alert-danger {
            background-color: rgba(239, 68, 68, 0.18);
            border-color: #dc2626;
            color: #fca5a5;
        }
        .alert-icon {
            font-size: 1.3rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .alert-content h4 {
            margin: 0 0 4px 0;
            font-size: 1rem;
            font-weight: 700;
        }
        .alert-content p {
            margin: 0;
            font-size: 0.86rem;
        }
    </style>
</head>

<body>
    <!-- Skip to main content for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- HEADER / NAVBAR (Unified matching all portal pages) -->
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
                        <li><a href="contact.php" class="active">Contact</a></li>
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
        <div class="container">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="breadcrumb-nav">
                <ol class="breadcrumb-list">
                    <li class="breadcrumb-item"><a href="../index.html">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Contact Support</li>
                </ol>
            </nav>

            <div class="auth-container">
                <div class="auth-card contact-card">

                    <div class="auth-header">
                        <h2>Contact Academic Helpdesk</h2>
                        <p>Have inquiries about registration, syllabus, course schedules, or exam results? Send us a message.</p>
                    </div>

                    <!-- Flash Success Alert -->
                    <?php if ($success): ?>
                        <div class="alert-banner alert-success" role="alert">
                            <div class="alert-icon">&#x2714;&#xFE0F;</div>
                            <div class="alert-content">
                                <h4>Message Sent Successfully!</h4>
                                <p><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Flash Validation Errors Banner -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-banner alert-danger" role="alert">
                            <div class="alert-icon">&#x26A0;&#xFE0F;</div>
                            <div class="alert-content">
                                <h4>Please Correct The Errors Below</h4>
                                <p>Ensure all required fields are accurately filled before re-submitting.</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form id="contactForm" action="process_contact.php" method="POST" novalidate>
                        <?php echo csrf_field(); ?>

                        <div class="form-grid">

                            <!-- Full Name -->
                            <div class="form-group">
                                <label for="userName">Your Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="userName" name="userName"
                                    class="form-control <?php echo isset($errors['userName']) ? 'input-error' : ''; ?>"
                                    placeholder="Jay Bhensdadiya"
                                    value="<?php echo htmlspecialchars($formData['userName'] ?? ''); ?>" required>
                                <?php if (isset($errors['userName'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['userName']); ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- University Email -->
                            <div class="form-group">
                                <label for="userEmail">University Email <span style="color: #ef4444;">*</span></label>
                                <input type="email" id="userEmail" name="userEmail"
                                    class="form-control <?php echo isset($errors['userEmail']) ? 'input-error' : ''; ?>"
                                    placeholder="jay@charusat.edu.in"
                                    value="<?php echo htmlspecialchars($formData['userEmail'] ?? ''); ?>" required>
                                <?php if (isset($errors['userEmail'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['userEmail']); ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Subject -->
                            <div class="form-group form-group-full">
                                <label for="userSubject">Subject <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="userSubject" name="userSubject"
                                    class="form-control <?php echo isset($errors['userSubject']) ? 'input-error' : ''; ?>"
                                    placeholder="Inquiry about course enrollment, attendance, or events..."
                                    value="<?php echo htmlspecialchars($formData['userSubject'] ?? ''); ?>" required>
                                <?php if (isset($errors['userSubject'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['userSubject']); ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Message -->
                            <div class="form-group form-group-full">
                                <label for="userMessage">Your Message <span style="color: #ef4444;">*</span></label>
                                <textarea id="userMessage" name="userMessage" rows="4"
                                    class="form-control <?php echo isset($errors['userMessage']) ? 'input-error' : ''; ?>"
                                    placeholder="Write your inquiry or description here in detail..." required><?php echo htmlspecialchars($formData['userMessage'] ?? ''); ?></textarea>
                                <?php if (isset($errors['userMessage'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['userMessage']); ?></small>
                                <?php endif; ?>
                            </div>

                            <!-- Full Width Submit Button -->
                            <div class="form-group form-group-full" style="margin-top: 6px; margin-bottom: 0;">
                                <button type="submit" class="btn btn-primary btn-full">Send Message</button>
                            </div>

                        </div>
                    </form>

                    <!-- Quick Campus Contact Info -->
                    <div class="contact-quick-info">
                        <div class="info-pill">
                            <span class="info-label">📍 Campus:</span>
                            <span>DEPSTAR, CHARUSAT, Changa</span>
                        </div>
                        <div class="info-pill">
                            <span class="info-label">📞 Phone:</span>
                            <span>+91 2697 265011</span>
                        </div>
                        <div class="info-pill">
                            <span class="info-label">✉️ Email:</span>
                            <span>support@studenthub.charusat.ac.in</span>
                        </div>
                        <div class="info-pill">
                            <span class="info-label">⏰ Office Hours:</span>
                            <span>Mon &ndash; Fri: 9:00 AM &ndash; 4:30 PM</span>
                        </div>
                    </div>

                    <div class="auth-footer">
                        <span>Need instant answers? Check our <a href="faq.html">Frequently Asked Questions</a> or submit <a href="feedback.html">Portal Feedback</a>.</span>
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
