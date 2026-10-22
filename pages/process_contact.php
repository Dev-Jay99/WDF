<?php
/**
 * StudentHub Portal - Contact Form Processor
 * Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON Storage
 */

require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/form_helpers.php';

// Verify POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: contact.php');
    exit;
}

// Validate CSRF Token
$submittedToken = $_POST['csrf_token'] ?? '';
if (!validate_csrf_token($submittedToken)) {
    $_SESSION['contact_errors'] = [
        'general' => 'Security token invalid (CSRF check failed). Please re-submit the form.'
    ];
    header('Location: contact.php');
    exit;
}

// Sanitize inputs
$name    = sanitize_text($_POST['userName'] ?? '');
$email   = sanitize_email_input($_POST['userEmail'] ?? '');
$subject = sanitize_text($_POST['userSubject'] ?? '');
$message = sanitize_text($_POST['userMessage'] ?? '');

$formData = [
    'userName'    => $name,
    'userEmail'   => $email,
    'userSubject' => $subject,
    'userMessage' => $message,
];

// Server-side validation
$errors = [];

if (empty($name)) {
    $errors['userName'] = 'Name is required.';
} elseif (!preg_match("/^[a-zA-Z\s\.\']{3,60}$/", $name)) {
    $errors['userName'] = 'Name must be 3-60 characters and contain only letters and spaces.';
}

if (empty($email)) {
    $errors['userEmail'] = 'University Email is required.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['userEmail'] = 'Please enter a valid university email address.';
}

if (empty($subject)) {
    $errors['userSubject'] = 'Subject is required.';
} elseif (strlen($subject) < 3 || strlen($subject) > 120) {
    $errors['userSubject'] = 'Subject must be between 3 and 120 characters.';
}

if (empty($message)) {
    $errors['userMessage'] = 'Message is required.';
} elseif (strlen($message) < 10) {
    $errors['userMessage'] = 'Message must be at least 10 characters long.';
}

if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_data']   = $formData;
    header('Location: contact.php');
    exit;
}

// Safe storage
$contactId = 'MSG-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
$timestamp = date('Y-m-d H:i:s');

$csvPath  = __DIR__ . '/../data/contacts.csv';
$jsonPath = __DIR__ . '/../data/contacts.json';

$headers = ['Message ID', 'Timestamp', 'Name', 'Email', 'Subject', 'Message'];
$row     = [$contactId, $timestamp, $name, $email, $subject, $message];

$record = [
    'message_id' => $contactId,
    'timestamp'  => $timestamp,
    'name'       => $name,
    'email'      => $email,
    'subject'    => $subject,
    'message'    => $message
];

append_to_csv_safe($csvPath, $headers, $row);
append_to_json_safe($jsonPath, $record);

unset($_SESSION['contact_errors'], $_SESSION['contact_data'], $_SESSION['csrf_token']);

$_SESSION['contact_success'] = "Thank you, $name! Your message has been safely received and logged (ID: $contactId).";
header('Location: contact.php?status=success');
exit;
