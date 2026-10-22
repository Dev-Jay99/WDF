<?php
/**
 * StudentHub Portal - Student Registration Page
 * Practical 7: PHP Form Processing with Server-Side Validation & CSV/JSON Storage
 */
require_once __DIR__ . '/../includes/csrf.php';

$errors    = $_SESSION['form_errors'] ?? [];
$formData  = $_SESSION['form_data'] ?? [];
$success   = $_SESSION['registration_success'] ?? null;

// Clear flash data after reading
unset($_SESSION['form_errors'], $_SESSION['form_data'], $_SESSION['registration_success']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="StudentHub Portal - Student Registration with Server-Side Validation, CSRF Protection, and Safe CSV/JSON File Storage">
    <title>Student Registration | StudentHub Portal</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/register.css">
    <style>
        .alert-banner {
            padding: 18px 22px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 0.95rem;
            line-height: 1.5;
            display: flex;
            align-items: flex-start;
            gap: 14px;
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
            font-size: 1.4rem;
            line-height: 1;
            flex-shrink: 0;
        }
        .alert-content h4 {
            margin: 0 0 4px 0;
            font-size: 1.05rem;
            font-weight: 700;
        }
        .alert-content p {
            margin: 0 0 8px 0;
        }
        .alert-content ul {
            margin: 6px 0 0 18px;
            padding: 0;
        }
        .view-records-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.85rem;
            margin-top: 8px;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            transition: all 0.2s ease;
        }
        .view-records-badge:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
            color: #ffffff;
        }
    </style>
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
                        <li><a href="login.php" class="btn-login">Login</a></li>
                        <li><a href="register.php" class="btn-register active">Register</a></li>
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
                    <li class="breadcrumb-item active" aria-current="page">Student Registration</li>
                </ol>
            </nav>

            <div class="auth-container">
                <div class="auth-card register-card">

                    <div class="auth-header">
                        <h2>Register to StudentHub</h2>
                        <p>Create your student account to access portal services and events.</p>
                    </div>

                    <!-- Flash Success Alert -->
                    <?php if ($success): 
                        $successName = $success['full_name'] ?? $success['fullName'] ?? 'Student';
                        $successId   = $success['enrollmentNo'] ?? $success['student_id'] ?? $success['enrollment_no'] ?? 'N/A';
                        $successTime = $success['timestamp'] ?? date('Y-m-d H:i:s');
                    ?>
                        <div class="alert-banner alert-success" role="alert">
                            <div class="alert-icon">&#x2714;&#xFE0F;</div>
                            <div class="alert-content">
                                <h4>Account Created Successfully!</h4>
                                <p>Welcome, <strong><?php echo htmlspecialchars($successName); ?></strong>! Your record has been verified and stored in both CSV and JSON formats.</p>
                                <p style="font-size: 0.85rem; color: var(--bs-secondary); margin-bottom: 8px;">
                                    <strong>Assigned Student ID / Enrollment:</strong> <code><?php echo htmlspecialchars($successId); ?></code> |
                                    <strong>Registered At:</strong> <?php echo htmlspecialchars($successTime); ?>
                                </p>
                                <a href="view_records.php" class="view-records-badge">
                                    &#x1F4C2; View Stored CSV / JSON Records &rarr;
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Flash Validation Errors Banner -->
                    <?php if (!empty($errors)): ?>
                        <div class="alert-banner alert-danger" role="alert">
                            <div class="alert-icon">&#x26A0;&#xFE0F;</div>
                            <div class="alert-content">
                                <h4>Server-Side Validation Encountered Errors</h4>
                                <p>Please correct the highlighted fields below to proceed:</p>
                                <ul>
                                    <?php foreach ($errors as $fieldKey => $errMessage): ?>
                                        <li><?php echo htmlspecialchars($errMessage); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- PHP Registration Form -->
                    <form id="registerForm" action="process_register.php" method="POST" novalidate>
                        <!-- Practical 7 Advanced Extension: CSRF Token Hidden Input -->
                        <?php echo csrf_field(); ?>

                        <div class="form-grid">

                            <!-- Full Name -->
                            <div class="form-group">
                                <label for="fullName">Full Name <span style="color: #ef4444;">*</span></label>
                                <input type="text" id="fullName" name="fullName"
                                    class="form-control <?php echo isset($errors['fullName']) ? 'input-error' : ''; ?>"
                                    placeholder="Jay Bhensdadiya"
                                    value="<?php echo htmlspecialchars($formData['fullName'] ?? ''); ?>" required>
                                <?php if (isset($errors['fullName'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['fullName']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="fullNameError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Email -->
                            <div class="form-group">
                                <label for="email">University Email <span style="color: #ef4444;">*</span></label>
                                <input type="email" id="email" name="email"
                                    class="form-control <?php echo isset($errors['email']) ? 'input-error' : ''; ?>"
                                    placeholder="jay@charusat.edu.in"
                                    value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>" required>
                                <?php if (isset($errors['email'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['email']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="emailError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Mobile Phone -->
                            <div class="form-group">
                                <label for="phone">Mobile Number <span style="color: #ef4444;">*</span></label>
                                <input type="tel" id="phone" name="phone"
                                    class="form-control <?php echo isset($errors['phone']) ? 'input-error' : ''; ?>"
                                    placeholder="+91 9876543210"
                                    value="<?php echo htmlspecialchars($formData['phone'] ?? ''); ?>" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['phone']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="phoneError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Course -->
                            <div class="form-group">
                                <label for="course">Course / Branch <span style="color: #ef4444;">*</span></label>
                                <select id="course" name="course"
                                    class="form-select <?php echo isset($errors['course']) ? 'input-error' : ''; ?>" required>
                                    <option value="" disabled <?php echo empty($formData['course']) ? 'selected' : ''; ?>>Select Course</option>
                                    <option value="B.Tech CE" <?php echo (($formData['course'] ?? '') === 'B.Tech CE') ? 'selected' : ''; ?>>B.Tech Computer Engineering</option>
                                    <option value="B.Tech IT" <?php echo (($formData['course'] ?? '') === 'B.Tech IT') ? 'selected' : ''; ?>>B.Tech Information Technology</option>
                                    <option value="B.Tech CSE" <?php echo (($formData['course'] ?? '') === 'B.Tech CSE') ? 'selected' : ''; ?>>B.Tech Computer Science &amp; Engg</option>
                                </select>
                                <?php if (isset($errors['course'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['course']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="courseError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Year -->
                            <div class="form-group">
                                <label for="year">Academic Year <span style="color: #ef4444;">*</span></label>
                                <select id="year" name="year"
                                    class="form-select <?php echo isset($errors['year']) ? 'input-error' : ''; ?>" required>
                                    <option value="" disabled <?php echo empty($formData['year']) ? 'selected' : ''; ?>>Select Year</option>
                                    <option value="1st Year" <?php echo (($formData['year'] ?? '') === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?php echo (($formData['year'] ?? '') === '2nd Year') ? 'selected' : ''; ?>>2nd Year (3rd Sem)</option>
                                    <option value="3rd Year" <?php echo (($formData['year'] ?? '') === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?php echo (($formData['year'] ?? '') === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                                <?php if (isset($errors['year'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['year']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="yearError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Gender -->
                            <div class="form-group gender-group">
                                <label>Gender <span style="color: #ef4444;">*</span></label>
                                <div class="gender-options">
                                    <label><input type="radio" name="gender" value="Male" <?php echo (($formData['gender'] ?? '') === 'Male') ? 'checked' : ''; ?>> Male</label>
                                    <label><input type="radio" name="gender" value="Female" <?php echo (($formData['gender'] ?? '') === 'Female') ? 'checked' : ''; ?>> Female</label>
                                    <label><input type="radio" name="gender" value="Other" <?php echo (($formData['gender'] ?? '') === 'Other') ? 'checked' : ''; ?>> Other</label>
                                </div>
                                <?php if (isset($errors['gender'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['gender']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="genderError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Dependent Dropdowns (Country > State > City) -->
                            <div class="form-group form-group-full location-group">
                                <label>Residential Location (Country &gt; State &gt; City) <span style="color: #ef4444;">*</span></label>
                                <div class="dependent-location-grid">
                                    <div>
                                        <select id="country" name="country" class="form-select" required>
                                            <option value="<?php echo htmlspecialchars($formData['country'] ?? ''); ?>" selected>
                                                <?php echo !empty($formData['country']) ? htmlspecialchars($formData['country']) : 'Select Country'; ?>
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="state" name="state" class="form-select" required>
                                            <option value="<?php echo htmlspecialchars($formData['state'] ?? ''); ?>" selected>
                                                <?php echo !empty($formData['state']) ? htmlspecialchars($formData['state']) : 'Select State / Province'; ?>
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="city" name="city" class="form-select" required>
                                            <option value="<?php echo htmlspecialchars($formData['city'] ?? ''); ?>" selected>
                                                <?php echo !empty($formData['city']) ? htmlspecialchars($formData['city']) : 'Select City'; ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <?php if (isset($errors['country']) || isset($errors['state']) || isset($errors['city'])): ?>
                                    <small class="server-error">Complete residential location (Country, State, City) is required.</small>
                                <?php endif; ?>
                            </div>

                            <!-- Password -->
                            <div class="form-group">
                                <label for="password">Password <span style="color: #ef4444;">*</span></label>
                                <input type="password" id="password" name="password"
                                    class="form-control <?php echo isset($errors['password']) ? 'input-error' : ''; ?>"
                                    placeholder="••••••••" required>
                                <?php if (isset($errors['password'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['password']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="passwordError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password -->
                            <div class="form-group">
                                <label for="confirmPassword">Confirm Password <span style="color: #ef4444;">*</span></label>
                                <input type="password" id="confirmPassword" name="confirmPassword"
                                    class="form-control <?php echo isset($errors['confirmPassword']) ? 'input-error' : ''; ?>"
                                    placeholder="••••••••" required>
                                <?php if (isset($errors['confirmPassword'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['confirmPassword']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="confirmPasswordError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- HTML5 Canvas Custom CAPTCHA (Frontend Integration) -->
                            <div class="form-group captcha-group">
                                <label for="captchaInput">Security Verification (Canvas CAPTCHA)</label>
                                <div class="captcha-container">
                                    <canvas id="captchaCanvas" width="140" height="40" aria-label="CAPTCHA image verification"></canvas>
                                    <button type="button" id="refreshCaptchaBtn" class="btn-refresh-captcha" title="Regenerate CAPTCHA" aria-label="Regenerate CAPTCHA">&#x21bb;</button>
                                    <input type="text" id="captchaInput" name="captchaInput" class="form-control" placeholder="Code" maxlength="4" autocomplete="off" style="text-transform: uppercase;">
                                </div>
                                <small class="error-message" id="captchaError"></small>
                            </div>

                            <!-- Terms & Privacy Agreement -->
                            <div class="form-group form-group-full" style="margin-top: 4px; margin-bottom: 6px;">
                                <label class="terms-group" style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-size: 0.84rem;">
                                    <input type="checkbox" id="terms" name="terms" value="1" required checked style="width: 16px; height: 16px; accent-color: #2563eb;">
                                    <span>I agree to the <a href="#" style="color: #3b82f6;">Terms of Service</a> &amp; <a href="#" style="color: #3b82f6;">Privacy Policy</a>.</span>
                                </label>
                                <?php if (isset($errors['terms'])): ?>
                                    <small class="server-error"><?php echo htmlspecialchars($errors['terms']); ?></small>
                                <?php else: ?>
                                    <small class="error-message" id="termsError"></small>
                                <?php endif; ?>
                            </div>

                            <!-- Full Width Submit Button -->
                            <div class="form-group form-group-full" style="margin-bottom: 0;">
                                <button type="submit" class="btn btn-primary btn-full">Register Account</button>
                            </div>

                        </div>
                    </form>

                    <div class="auth-footer">
                        <span>Already have an account? <a href="login.php">Login here</a></span>
                        <div style="margin-top: 6px;">
                            <a href="view_records.php" style="font-size: 0.8rem; color: var(--text-muted); text-decoration: none;">View Stored Records &rarr;</a>
                        </div>
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

    <!-- Load script with cache-busting timestamp so browser never runs stale code -->
    <script src="../js/script.js?v=<?= time() ?>"></script>
    <script>
        // Dedicated 4-Character Plain CAPTCHA (Guaranteed No Cache Collision)
        (function() {
            function setupCleanCaptcha() {
                var canvas = document.getElementById("captchaCanvas");
                var input = document.getElementById("captchaInput");
                var refreshBtn = document.getElementById("refreshCaptchaBtn");
                var error = document.getElementById("captchaError");
                if (!canvas || !input) return;

                // Ensure exactly 4 chars allowed
                input.setAttribute("maxlength", "4");

                // Generate fresh 4-character code
                if (typeof generateCaptcha === "function") {
                    generateCaptcha();
                }
            }

            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", setupCleanCaptcha);
            } else {
                setupCleanCaptcha();
            }
        })();
    </script>
</body>
</html>
