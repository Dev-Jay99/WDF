<?php
/**
 * StudentHub Portal - Registration Form Processor
 * 
 * Subject: Web Development Frameworks (ITUE203)
 * Practical 7: PHP Form Processing with Server-Side Validation & CSV/JSON Storage
 * Practical 9: Secure User Registration with Database Insert, Duplicate Email Check, and Password Hashing
 * - Problem Definition: MySQLi insert, duplicate check, password_hash(), clear messages
 * - Intermediate Extension: Prepared statements for all queries
 * - Advanced Extension: Anti-CSRF verification and Audit log entry for registration
 */

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/form_helpers.php';
require_once __DIR__ . '/../includes/db.php';

// 1. Verify that request is submitted via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// 2. Practical 7 & 9 Advanced: Validate Anti-CSRF Token
$submittedToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($submittedToken)) {
    $_SESSION['form_errors'] = [
        'general' => 'Security token expired or invalid (CSRF check failed). Please re-submit the form.'
    ];
    header('Location: register.php');
    exit;
}

// 3. Collect and sanitize input fields
$fullName        = sanitize_text($_POST['fullName'] ?? '');
$email           = sanitize_email_input($_POST['email'] ?? '');
$phone           = sanitize_text($_POST['phone'] ?? '');
$course          = sanitize_text($_POST['course'] ?? '');
$year            = sanitize_text($_POST['year'] ?? '');
$gender          = sanitize_text($_POST['gender'] ?? '');
$country         = sanitize_text($_POST['country'] ?? '');
$state           = sanitize_text($_POST['state'] ?? '');
$city            = sanitize_text($_POST['city'] ?? '');
$password        = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirmPassword'] ?? '';
$terms           = isset($_POST['terms']);

// Generate enrollment number if not provided (Format: 23DCE + random 3-digit)
$enrollmentNo    = sanitize_text($_POST['enrollmentNo'] ?? ('23DCE' . str_pad((string)mt_rand(100, 999), 3, '0', STR_PAD_LEFT)));

// Store sanitized data for repopulation (excluding sensitive passwords)
$formData = [
    'fullName'     => $fullName,
    'email'        => $email,
    'phone'        => $phone,
    'course'       => $course,
    'year'         => $year,
    'gender'       => $gender,
    'country'      => $country,
    'state'        => $state,
    'city'         => $city,
    'enrollmentNo' => $enrollmentNo,
];

// 4. Server-Side Validation Rules using Regular Expressions
$errors = [];

// Full Name: 3-60 chars, letters and spaces only
if (empty($fullName)) {
    $errors['fullName'] = 'Full Name is required.';
} elseif (!preg_match("/^[a-zA-Z\s\.\']{3,60}$/", $fullName)) {
    $errors['fullName'] = 'Full Name must be 3-60 characters and contain only letters, dots, and spaces.';
}

// Email: Valid email format & University domain preferred
if (empty($email)) {
    $errors['email'] = 'Email address is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Please enter a valid email address.';
}

// Mobile Number: 10 digits starting with 6, 7, 8, or 9
if (empty($phone)) {
    $errors['phone'] = 'Mobile number is required.';
} elseif (!preg_match("/^[6-9]\d{9}$/", $phone)) {
    $errors['phone'] = 'Enter a valid 10-digit Indian mobile number starting with 6-9.';
}

// Course selection
$allowedCourses = [
    'B.Tech CE', 'B.Tech IT', 'B.Tech CSE', 'B.Tech EC', 'B.Tech ME', 'B.Tech CL',
    'BCA', 'B.Sc IT', 'MCA', 'M.Tech CSE'
];
if (empty($course) || !in_array($course, $allowedCourses, true)) {
    $errors['course'] = 'Please select a valid academic degree course.';
}

// Year selection
$allowedYears = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
if (empty($year) || !in_array($year, $allowedYears, true)) {
    $errors['year'] = 'Please select your current study year.';
}

// Gender selection
$allowedGenders = ['Male', 'Female', 'Other'];
if (empty($gender) || !in_array($gender, $allowedGenders, true)) {
    $errors['gender'] = 'Please select your gender.';
}

// Country, State, City
if (empty($country)) {
    $errors['country'] = 'Country selection is required.';
}
if (empty($state)) {
    $errors['state'] = 'State selection is required.';
}
if (empty($city)) {
    $errors['city'] = 'City selection is required.';
}

// Password strength validation: min 8 chars, 1 uppercase, 1 lowercase, 1 digit, 1 special char
if (empty($password)) {
    $errors['password'] = 'Password is required.';
} elseif (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters long.';
} elseif (!preg_match("/[A-Z]/", $password)) {
    $errors['password'] = 'Password must contain at least one uppercase letter (A-Z).';
} elseif (!preg_match("/[a-z]/", $password)) {
    $errors['password'] = 'Password must contain at least one lowercase letter (a-z).';
} elseif (!preg_match("/\d/", $password)) {
    $errors['password'] = 'Password must contain at least one number (0-9).';
} elseif (!preg_match("/[!@#$%^&*()_+\-=\[\]{};':\"\\|,.<>\/?]/", $password)) {
    $errors['password'] = 'Password must contain at least one special character (!@#$%^&*...).';
}

// Password match check
if ($password !== $confirmPassword) {
    $errors['confirmPassword'] = 'Passwords do not match.';
}

// Terms acceptance
if (!$terms) {
    $errors['terms'] = 'You must agree to the Terms of Service & Privacy Policy.';
}

// 5. Practical 9: Database Duplicate Email / Username Check via MySQLi Prepared Statement
$mysqli = null;
try {
    $mysqli = get_mysqli_connection();
    if (empty($errors['email'])) {
        $checkStmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->bind_param("s", $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            $errors['email'] = 'An account with this email address is already registered. Please login instead.';
        }
        $checkStmt->close();
    }
} catch (Exception $e) {
    // If MySQL is temporarily offline, log and proceed with file-based registration fallback
    error_log("Database connection check error: " . $e->getMessage());
}

// 6. Handle Validation Errors: Redirect back with flash data
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data']   = $formData;
    header('Location: register.php');
    exit;
}

// 7. Secure Password Hashing (Practical 9: password_hash using Bcrypt)
$hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
$timestamp = date('Y-m-d H:i:s');
$clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

// 8. Practical 9: Insert User & Student Records into MySQL using MySQLi Prepared Statements
$dbSuccess = false;
$newUserId = null;
$newStudentId = null;

if ($mysqli) {
    try {
        $mysqli->begin_transaction();

        // Query 1: Insert into users table
        $userStmt = $mysqli->prepare("
            INSERT INTO users (name, email, password_hash, role, status, created_at)
            VALUES (?, ?, ?, 'student', 'active', ?)
        ");
        $userStmt->bind_param("ssss", $fullName, $email, $hashedPassword, $timestamp);
        $userStmt->execute();
        $newUserId = $mysqli->insert_id;
        $userStmt->close();

        // Query 2: Insert into students table
        $studentStmt = $mysqli->prepare("
            INSERT INTO students (user_id, enrollment_no, full_name, email, phone, course, year, gender, country, state, city, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        $studentStmt->bind_param(
            "isssssssssss",
            $newUserId, $enrollmentNo, $fullName, $email, $phone,
            $course, $year, $gender, $country, $state, $city, $timestamp
        );
        $studentStmt->execute();
        $newStudentId = $mysqli->insert_id;
        $studentStmt->close();

        // Practical 9 Advanced: Audit Log Entry for Registration
        $auditAction = 'USER_REGISTRATION';
        $auditEntity = 'users';
        $auditDetails = "New student registered: {$email} (Enrollment: {$enrollmentNo})";
        $auditStmt = $mysqli->prepare("
            INSERT INTO audit_logs (user_id, action, entity, entity_id, details, ip_address, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $auditStmt->bind_param("ississs", $newUserId, $auditAction, $auditEntity, $newUserId, $auditDetails, $clientIp, $timestamp);
        $auditStmt->execute();
        $auditStmt->close();

        $mysqli->commit();
        $dbSuccess = true;
    } catch (Exception $e) {
        $mysqli->rollback();
        error_log("Practical 9 Registration DB Insert Error: " . $e->getMessage());
    }
}

// 9. Practical 7: Dual Safe Storage into CSV and JSON Files using exclusive flock(LOCK_EX)
$csvFilePath  = __DIR__ . '/../data/registrations.csv';
$jsonFilePath = __DIR__ . '/../data/registrations.json';

$recordArray = [
    'student_id'    => $enrollmentNo,
    'enrollment_no' => $enrollmentNo,
    'timestamp'     => $timestamp,
    'full_name'     => $fullName,
    'email'         => $email,
    'phone'         => $phone,
    'course'        => $course,
    'year'          => $year,
    'gender'        => $gender,
    'country'       => $country,
    'state'         => $state,
    'city'          => $city,
    'password_hash' => $hashedPassword
];

$csvHeaders = [
    'Timestamp', 'Enrollment', 'Full Name', 'Email', 'Phone', 'Course',
    'Year', 'Gender', 'Country', 'State', 'City', 'Password Hash'
];

$csvRow = [
    $timestamp, $enrollmentNo, $fullName, $email, $phone, $course,
    $year, $gender, $country, $state, $city, $hashedPassword
];

append_to_csv_safe($csvFilePath, $csvHeaders, $csvRow);
append_to_json_safe($jsonFilePath, $recordArray);

// 10. Practical 7 & 9 Success Notification & Redirect
$_SESSION['registration_success'] = [
    'message'       => 'Registration successful! Your account and student record have been securely created.',
    'full_name'     => $fullName,
    'fullName'      => $fullName,
    'email'         => $email,
    'student_id'    => $enrollmentNo,
    'enrollment_no' => $enrollmentNo,
    'enrollmentNo'  => $enrollmentNo,
    'course'        => $course,
    'year'          => $year,
    'dbStored'      => $dbSuccess,
    'timestamp'     => $timestamp,
];

// Invalidate CSRF token after successful submission
unset($_SESSION['csrf_token']);

header('Location: register.php');
exit;
