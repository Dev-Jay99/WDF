# Practical 7: PHP Form Processing with Server-Side Validation and CSV/JSON File Storage

**Subject:** Web Development Frameworks (ITUE203)  
**Institution:** Devang Patel Institute of Advance Technology and Research (DEPSTAR)  
**University:** Charotar University of Science and Technology (CHARUSAT)  
**Semester:** 3rd Semester, 2026-27 (Odd)  
**Author:** Jay Bhensdadiya  
**Date:** September 30, 2026  

---

## 1. Objective & Problem Definition
To process submitted student registration and contact form data using PHP. Implement robust server-side input validation and sanitization, secure the form with anti-CSRF token verification, and store submitted records safely in both **CSV** and **JSON** file formats using exclusive advisory file locks (`flock(LOCK_EX)`). Provide clear user feedback messages and a dedicated administrative table view for recorded data.

---

## 2. Key Questions & Architecture Answers

| # | Practical Evaluation Question | Implementation Strategy in StudentHub Portal |
|---|-------------------------------|----------------------------------------------|
| 1 | **Is the form submitted using POST?** | Yes, both `register.php` and `contact.php` enforce `<form method="POST">`. Backend scripts `process_register.php` and `process_contact.php` verify `$_SERVER['REQUEST_METHOD'] === 'POST'`, rejecting any non-POST attempts. |
| 2 | **Are inputs validated and sanitized server-side?** | Yes. All text fields undergo `trim()`, `stripslashes()`, and `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Emails are validated via `filter_var(..., FILTER_VALIDATE_EMAIL)` and domain regex. Names, phones, whitelisted courses, academic years, and passwords are checked against regular expressions. |
| 3 | **Is file writing handled safely?** | Yes. File writes utilize PHP `flock($fp, LOCK_EX)` for advisory locking before calling `fputcsv()`. JSON writes use `file_put_contents($path, $json, LOCK_EX)` to ensure atomic updates under concurrency. |
| 4 | **Are success and error responses displayed clearly?** | Yes. Flash session states (`$_SESSION['form_errors']` and `$_SESSION['registration_success']`) render responsive alert banners and per-field inline feedback without losing user-entered text. |

---

## 3. Implemented File Architecture

```text
StudentHub/
├── includes/
│   ├── csrf.php             # Cryptographic CSRF token generator & validator (Advanced Extension)
│   └── form_helpers.php     # Input sanitization, safe CSV/JSON writer with flock(LOCK_EX)
├── pages/
│   ├── register.php         # Student registration form with CSRF field & error feedback
│   ├── process_register.php # Server-side validator, password hasher & file storage handler
│   ├── contact.php          # Contact inquiry form with server validation
│   ├── process_contact.php  # Contact inquiry handler & storage
│   └── view_records.php     # Interactive records viewer table (Intermediate Extension)
└── data/
    ├── registrations.csv    # Comma-separated records archive
    ├── registrations.json   # JSON formatted records archive
    ├── contacts.csv         # Contact message records in CSV
    └── contacts.json        # Contact message records in JSON
```

---

## 4. Test Cases Matrix

| Test ID | Test Scenario | Input Data | Expected Output | Actual Output | Status |
|---|---|---|---|---|---|
| **TC-01** | Valid Student Registration | Name: `Jay Bhensdadiya`<br>Email: `jay@charusat.edu.in`<br>Phone: `+91 9876543210`<br>Course: `B.Tech CE`<br>Year: `2nd Year`<br>Password: `Charusat@2026`<br>Terms: Checked | Record saved to `registrations.csv` and `registrations.json` with assigned ID `STU-2026-XXXXX`. Success alert displayed. | Form redirects with success banner; record verified in CSV/JSON. | **PASS** |
| **TC-02** | Invalid Email Format | Name: `Test Student`<br>Email: `invalid-email-format`<br>Phone: `9876543210` | Form rejected with server-side error: *"Please enter a valid email address."* | Inline red error message displayed; input values preserved. | **PASS** |
| **TC-03** | Password Length < 8 Chars | Password: `pass`<br>Confirm: `pass` | Form rejected with server-side error: *"Password must be at least 8 characters long."* | Validation caught error; form retained text fields safely. | **PASS** |
| **TC-04** | Password Mismatch | Password: `Charusat@2026`<br>Confirm: `Different@2026` | Form rejected with error: *"Passwords do not match."* | Field-level error displayed under Confirm Password. | **PASS** |
| **TC-05** | Invalid Mobile Number | Phone: `12345` (only 5 digits) | Form rejected with error: *"Please enter a valid 10-digit mobile number."* | Rejected server-side; error displayed. | **PASS** |
| **TC-06** | Missing Terms Acceptance | Checkbox unselected | Form rejected with error: *"You must accept the Terms of Service."* | Field error displayed at terms checkbox. | **PASS** |
| **TC-07** | CSRF Attack Simulation | POST request submitted without valid `csrf_token` | Form rejected with error: *"Security token expired or invalid (CSRF check failed)."* | Request aborted; zero records written to storage. | **PASS** |
| **TC-08** | File Concurrency & Safe Locking | Multiple rapid writes to `append_to_csv_safe` | `flock(LOCK_EX)` locks file, writes row, releases lock. | Zero file corruption; all rows cleanly formatted. | **PASS** |
| **TC-09** | Display Records (Intermediate) | Navigate to `pages/view_records.php` | Display table of all saved CSV and JSON records with search and format toggle. | Rendered active records with search, stat cards, and download buttons. | **PASS** |

---

## 5. Sample Storage Data

### Generated CSV (`data/registrations.csv`)
```csv
Student ID,Timestamp,Full Name,Email,Phone,Course,Year,Gender,Country,State,City,Password Hash
STU-2026-A101,2026-09-28 10:15:22,Aarav Patel,23dce001@charusat.edu.in,+91 9825112233,B.Tech CE,2nd Year,Male,India,Gujarat,Ahmedabad,$2y$10$eW7sH0wH5Z3m0qI5k4x0u.4Rlm7yH6D8f5h2l4j6m8o0q2s4u6w8y
STU-2026-J606,2026-09-30 08:50:33,Jay Bhensdadiya,jay@charusat.edu.in,+91 9876543210,B.Tech CE,2nd Year,Male,India,Gujarat,Ahmedabad,$2y$10$eW7sH0wH5Z3m0qI5k4x0u.4Rlm7yH6D8f5h2l4j6m8o0q2s4u6w8y
```

### Generated JSON (`data/registrations.json`)
```json
[
  {
    "student_id": "STU-2026-J606",
    "timestamp": "2026-09-30 08:50:33",
    "full_name": "Jay Bhensdadiya",
    "email": "jay@charusat.edu.in",
    "phone": "+91 9876543210",
    "course": "B.Tech CE",
    "year": "2nd Year",
    "gender": "Male",
    "country": "India",
    "state": "Gujarat",
    "city": "Ahmedabad",
    "password_hash": "$2y$10$eW7sH0wH5Z3m0qI5k4x0u.4Rlm7yH6D8f5h2l4j6m8o0q2s4u6w8y"
  }
]
```

---

## 6. Viva Questions & Model Answers

1. **Why is server-side validation mandatory even if frontend validation is present?**  
   *Answer:* Client-side JavaScript validation can be easily bypassed (e.g., by disabling JavaScript, modifying DOM inputs in developer tools, or sending raw HTTP POST requests via cURL/Postman). Server-side validation is the ultimate line of defense for data integrity and security.

2. **How does `flock()` prevent race conditions during file writing in PHP?**  
   *Answer:* Under concurrent HTTP requests, two PHP processes attempting to write to the same file simultaneously can interleave lines or corrupt file contents. `flock($fp, LOCK_EX)` places an exclusive lock on the file descriptor, forcing subsequent processes to wait until the current process completes writing and releases the lock with `LOCK_UN`.

3. **What is a CSRF token and how does it prevent attacks?**  
   *Answer:* A Cross-Site Request Forgery (CSRF) attack tricks an authenticated user's browser into submitting unauthorized requests to a trusted site. A CSRF token is a unique, cryptographically random string stored in the user's session and embedded in the HTML form. Because an external attacker cannot read the origin domain's session token, counterfeit requests fail token verification.
