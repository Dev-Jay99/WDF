# StudentHub: Enterprise Academic & Campus Event Management Portal

[![PHP Version](https://img.shields.io/badge/PHP-8.2.12-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MariaDB-10.4.32--InnoDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)](https://mariadb.org/)
[![Database Schema](https://img.shields.io/badge/Schema-Normalized%20(6%20Tables%20%2B%20SP)-success?style=for-the-badge&logo=mysql&logoColor=white)](sql/studenthub.sql)
[![Test Suite](https://img.shields.io/badge/Master%20Tests-15%2F15%20Assertions%20Passed%20(100%25)-brightgreen?style=for-the-badge&logo=checkmarx&logoColor=white)](tests/test_all_practicals.php)
[![Security](https://img.shields.io/badge/Security-RBAC%20%2B%20CSRF%20%2B%20Bcrypt%20%2B%20PDO-critical?style=for-the-badge&logo=auth0&logoColor=white)](includes/auth.php)
[![Academic Institution](https://img.shields.io/badge/CHARUSAT-DEPSTAR%20ITUE203-00529B?style=for-the-badge)](https://www.charusat.ac.in/)

> **Charotar University of Science and Technology (CHARUSAT)**  
> **Devang Patel Institute of Advance Technology and Computer Applications (DEPSTAR)**  
> **Department of Information Technology**  
> **Course:** Web Development Frameworks (`ITUE203`) • **Semester:** 3rd Semester  
> **Author:** Dev Patel ([@Dev-Jay99](https://github.com/Dev-Jay99))  
> **Repository:** [https://github.com/Dev-Jay99/WDF](https://github.com/Dev-Jay99/WDF)

---

## 📖 Table of Contents
1. [Project Overview](#-project-overview)
2. [System Architecture & Security Highlights](#-system-architecture--security-highlights)
3. [Complete Practical Curriculum Matrix (Practicals 1 – 15)](#-complete-practical-curriculum-matrix-practicals-1--15)
4. [Relational Database Schema (studenthub_db)](#-relational-database-schema-studenthub_db)
5. [Turnkey Local Deployment Guide](#-turnkey-local-deployment-guide)
6. [Default Verified Seed Credentials](#-default-verified-seed-credentials)
7. [Master Automated Verification Suite (15/15 Passed)](#-master-automated-verification-suite-1515-passed)
8. [Deployment & Diagnostics Center](#-deployment--diagnostics-center)
9. [Project Directory & File Structure](#-project-directory--file-structure)
10. [Academic Credits & License](#-academic-credits--license)

---

## 🌟 Project Overview

**StudentHub** is an academic management and campus event portal engineered with **PHP 8.2**, **MariaDB/MySQL 10.4**, **Semantic HTML5**, **Modern CSS3 (Grid & Flexbox)**, and **Vanilla JavaScript (ES6+)**. 

Designed to fulfill the entire 15-practical laboratory curriculum of **ITUE203 (Web Development Frameworks)** at CHARUSAT DEPSTAR, the platform delivers secure, asynchronous, role-governed workflows for **Students** and **System Administrators**:
* **Semantic & Accessible Interface**: WCAG 2.1 AA compliant semantic landmarks, skip links, and light/dark theme persistence.
* **Dual Persistence Layer**: Concurrency-safe CSV/JSON storage via `flock(LOCK_EX)` alongside a normalized relational MySQL database.
* **Asynchronous Single Page Operations**: Fetch API with debounced live search (350ms) and standard RESTful HTTP status codes (200, 201, 400, 404, 500).
* **Enterprise Security**: Bcrypt password hashing (`PASSWORD_BCRYPT`), cryptographic anti-CSRF token validation (`hash_equals`), SQL injection immunity via PDO & MySQLi prepared statements, 15-minute session idle timeout, and cryptographic Remember-Me token authentication (SHA-256).

---

## 🛡️ System Architecture & Security Highlights

```mermaid
graph TD
    Client[Web Browser Client] -->|HTTP / HTTPS| VHost[Apache Virtual Host: studenthub.local]
    VHost --> Router[Front Controller & Role-Based Guard]
    
    subgraph Presentation & Client Logic
        HTML5[Semantic HTML5 & Skip Links]
        CSS3[Responsive CSS Grid / Flexbox / Tokens]
        JS[Vanilla JS DOM / Theme Switcher / Debounced Fetch API]
    end

    subgraph Security & Session Management
        RBAC[Role-Based Access Control: Admin / Student]
        CSRF[Anti-CSRF Tokens: hash_equals]
        Bcrypt[Password Hashing: PASSWORD_BCRYPT]
        Session[PHP Session Guard & 15-min Inactivity Timeout]
    end

    subgraph Persistence Layer
        MySQL[(MariaDB / MySQL: studenthub_db)]
        Files[(Atomic Storage: registrations.csv / json)]
        SP[Stored Procedure: sp_get_dashboard_stats]
        Audit[Immutable Security Audit Trail: audit_logs]
    end

    Router --> Presentation & Client Logic
    Presentation & Client Logic --> Security & Session Management
    Security & Session Management --> Persistence Layer
    MySQL --> SP
    MySQL --> Audit
```

---

## 📋 Complete Practical Curriculum Matrix (Practicals 1 – 15)

| Practical | Title & Focus Area | Implementation Files | Syllabus Criteria & Features |
| :--- | :--- | :--- | :--- |
| **Practical 1** | Requirement Analysis, Sitemap & System Specification | [`docs/Requirement_Gathering.md`](docs/Requirement_Gathering.md) | Functional requirements specification, user personas (Student, Admin), ER diagram, sitemap hierarchy, and REST route specifications. |
| **Practical 2** | Semantic HTML5 Pages & Accessibility | [`index.html`](index.html), [`pages/about.html`](pages/about.html) | Semantic landmarks (`header`, `nav`, `main`, `section`, `article`, `footer`), `<a class="skip-link">`, breadcrumbs, and WCAG AA accessibility compliance. |
| **Practical 3** | Responsive UI Design with CSS Grid & Flexbox | [`css/style.css`](css/style.css), [`js/components.js`](js/components.js) | Mobile-first responsive grid layouts across desktop, tablet, and mobile; modular component loader via jQuery layout engine. |
| **Practical 4** | JavaScript DOM Interactivity & Micro-UI | [`js/script.js`](js/script.js) | Light/Dark theme switcher with `localStorage` persistence, mobile hamburger navigation, dismissible alert banner, auto-playing carousel slider, and accordion FAQs. |
| **Practical 5** | Client-Side Form Validation & Canvas CAPTCHA | [`pages/register.html`](pages/register.html), [`docs/Validation_Test_Cases.md`](docs/Validation_Test_Cases.md) | 11-field regex validation, dynamic password strength meter, instant blur/input feedback, inline error UX, and custom HTML5 Canvas CAPTCHA generation. |
| **Practical 6** | External JSON Data Fetching & Array Operations | [`pages/events.html`](pages/events.html), [`data/*.json`](data/) | Asynchronous Fetch API loading (`events.json`, `students.json`, `faqs.json`), live keyword filtering, multi-field sorting, pagination limits, and dependent dropdowns. |
| **Practical 7** | PHP Server-Side Sanitization & Dual Storage | [`pages/process_register.php`](pages/process_register.php), [`pages/view_records.php`](pages/view_records.php) | Server-side sanitization, cryptographic CSRF tokens, atomic thread-safe file locking with `flock(LOCK_EX)` writing to both CSV and JSON archives. |
| **Practical 8** | MySQL Schema Design, PDO & Stored Procedures | [`sql/studenthub.sql`](sql/studenthub.sql), [`includes/db.php`](includes/db.php) | 6 normalized tables in 3NF, dual database connector (MySQLi + PHP PDO with native prepared statements), and Stored Procedure `sp_get_dashboard_stats()`. |
| **Practical 9** | Secure Registration, Duplicate Check & Bcrypt | [`pages/register.php`](pages/register.php), [`pages/process_register.php`](pages/process_register.php) | Prepared database insert into `users` and `students`, duplicate email prevention, `password_hash()` with Bcrypt, and registration audit logging. |
| **Practical 10** | Session Security, Idle Timeout & Remember-Me | [`includes/auth.php`](includes/auth.php), [`pages/login.php`](pages/login.php) | Secure sessions (`session_regenerate_id`), `password_verify()`, 15-minute idle session timeout, role-based redirection, and SHA-256 persistent Remember-Me tokens. |
| **Practical 11** | Student Management CRUD with Search & Filter | [`pages/students.php`](pages/students.php) | Complete administrative student module: Add, View, Edit, and Soft Delete (`status = 'deleted'`), multi-field search, course filtering, and CSV export. |
| **Practical 12** | Event Management CRUD with Poster Upload | [`pages/events_admin.php`](pages/events_admin.php), [`uploads/posters/`](uploads/posters/) | Event management CRUD with secure poster file validation (MIME check, 2 MB limit, unique renaming), category filters, and `.htaccess` script execution prevention. |
| **Practical 13** | RESTful JSON API & Single-Page AJAX CRUD | [`api/events.php`](api/events.php), [`pages/events_ajax.php`](pages/events_ajax.php) | RESTful API endpoints handling GET, POST, PUT, DELETE with HTTP status codes (200, 201, 400, 404, 500); consumed via Fetch API with 350ms debounced live search. |
| **Practical 14** | Admin Dashboard, KPI Analytics & Audit Trail | [`pages/admin_dashboard.php`](pages/admin_dashboard.php) | Role-guarded dashboard with live KPI counters, active/inactive user status toggle, HTML5 Canvas monthly registration analytics chart, and live audit trail. |
| **Practical 15** | System Integration, Diagnostics & Deployment | [`pages/deployment.php`](pages/deployment.php), [`tests/test_all_practicals.php`](tests/test_all_practicals.php) | System Deployment & Diagnostics Center, automated end-to-end test suite (15/15 assertions passed), `.env.example`, and Apache VirtualHost specification (`studenthub.local`). |

---

## 🗄️ Relational Database Schema (`studenthub_db`)

The database is fully normalized in Third Normal Form (3NF) and utilizes the `InnoDB` storage engine with `utf8mb4_unicode_ci` collation:

| Table Name | Engine | Schema Description & Relations |
| :--- | :--- | :--- |
| **`users`** | InnoDB | User authentication credentials, Bcrypt password hashes, account role (`admin`, `student`), and status (`active`, `inactive`). |
| **`students`** | InnoDB | Academic student profiles, enrollment numbers, course, year, contact details, and soft-delete status. |
| **`events`** | InnoDB | Campus workshops and hackathons, poster image paths, event dates, venues, categories, and capacities. |
| **`registrations`** | InnoDB | Transactional student event bookings, foreign-key relationships to `events` and `students`, and timestamps. |
| **`remember_tokens`** | InnoDB | Cryptographic persistent authentication tokens hashed with SHA-256 with expiry timestamps. |
| **`audit_logs`** | InnoDB | Immutable administrative and security activity logs, action identifiers, entity IDs, details, and client IP addresses. |

### Stored Procedure: `sp_get_dashboard_stats()`
Aggregates total active students, events, registrations, and audit activities in a single atomic SQL call:
```sql
CALL sp_get_dashboard_stats();
```

---

## 🚀 Turnkey Local Deployment Guide

### Prerequisites
* **Web Server**: Apache 2.4+ (included with XAMPP)
* **PHP**: Version 8.1 or 8.2 with `pdo`, `pdo_mysql`, `mysqli`, `json`, `session`, `mbstring`, `fileinfo`, `exif`
* **Database**: MySQL 5.7+ or MariaDB 10.4+

### Step-by-Step Installation

1. **Clone the Repository**:
   Clone directly into your web server's `htdocs` directory:
   ```bash
   cd "D:/DEPSTAR SEM3/WDF/PHP/htdocs"
   git clone https://github.com/Dev-Jay99/WDF.git StudentHub
   ```

2. **Start Services in XAMPP**:
   * Open the **XAMPP Control Panel**.
   * Start **Apache** and **MySQL**.

3. **Import Database Schema & Seed Data**:
   * Open **phpMyAdmin** (`http://localhost/phpmyadmin/`).
   * Create a new database named `studenthub_db`.
   * Click **Import** $\to$ choose [`sql/studenthub.sql`](sql/studenthub.sql) $\to$ click **Go**.
   * *(Alternatively via terminal)*:
     ```bash
     mysql -u root -p < "D:/DEPSTAR SEM3/WDF/StudentHub/sql/studenthub.sql"
     ```

4. **Environment Configuration**:
   * Copy the template to create your `.env`:
     ```bash
     cp .env.example .env
     ```
   * Default credentials in `includes/db.php` connect out-of-the-box (`127.0.0.1:3306`, user: `root`, password: `""`).

5. **Verify Database Connectivity**:
   * Visit `http://localhost/StudentHub/test_connection.php` in your browser.
   * Confirm MySQLi, PDO, and the Stored Procedure all report **PASSED**.

6. **Optional: Virtual Host Setup (`studenthub.local`)**:
   * Add the following block to your Apache `httpd-vhosts.conf` (see [`docs/apache_vhost.conf`](docs/apache_vhost.conf)):
     ```apache
     <VirtualHost *:80>
         ServerName studenthub.local
         DocumentRoot "D:/DEPSTAR SEM3/WDF/StudentHub"
         <Directory "D:/DEPSTAR SEM3/WDF/StudentHub">
             Options Indexes FollowSymLinks
             AllowOverride All
             Require all granted
         </Directory>
     </VirtualHost>
     ```
   * Add `127.0.0.1 studenthub.local` to `C:\Windows\System32\drivers\etc\hosts`.

---

## 🔑 Default Verified Seed Credentials

| Role | Email Address | Password | Privileges / Scope |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin@charusat.edu.in` | `Admin@123` | Full access to Admin Dashboard, Student CRUD, Event CRUD, Deployment Diagnostics, and Audit Trail. |
| **Verified Student** | `student@charusat.edu.in` | `Student@123` | Student Dashboard, profile management, campus event exploration, and registration. |

---

## ⚡ Master Automated Verification Suite (15/15 Passed)

StudentHub features an automated test runner asserting integration correctness across all 15 practicals:
* **Web GUI**: Navigate to `http://localhost/StudentHub/tests/test_all_practicals.php`
* **CLI Execution**:
  ```bash
  php tests/test_all_practicals.php
  ```

```text
================================================================================
StudentHub Portal - Master Automated Test Runner (Practical 15)
================================================================================
[PASS] P1  : Requirement analysis, README & documentation files exist
[PASS] P2  : Semantic HTML5 core pages present with skip links
[PASS] P3  : Responsive CSS Grid/Flexbox stylesheets & reusable component loader present
[PASS] P4  : JavaScript DOM components: Theme toggle, hamburger menu & slider verified
[PASS] P5  : Registration frontend validation & Canvas CAPTCHA present
[PASS] P6  : External JSON datasets present and readable with valid syntax
[PASS] P7  : Server-side input sanitization, CSRF tokens & dual CSV/JSON storage verified
[PASS] P8  : MySQL schema integrity, PDO connectivity & Stored Procedure (sp_get_dashboard_stats)
[PASS] P9  : Bcrypt password hashing (PASSWORD_BCRYPT) and registration handler operational
[PASS] P10 : PHP session management, 15-min inactivity timeout & SHA-256 Remember-Me tokens
[PASS] P11 : Student Management CRUD module with search, filter, CSV export & soft-delete
[PASS] P12 : Event Management CRUD with poster upload validation & secure upload directory
[PASS] P13 : RESTful JSON API (api/events.php) with HTTP status codes (200, 201, 400, 404, 500)
[PASS] P14 : Role-Based Access Control Admin Dashboard, KPI metrics, status toggle & audit trail
[PASS] P15 : Local Apache Virtual Host config (studenthub.local), .env template & Deployment Guide
--------------------------------------------------------------------------------
Total Assertions: 15 | Passed: 15 | Failed: 0 (100% Success Rate)
================================================================================
```

---

## 🖥️ Deployment & Diagnostics Center

The administrative diagnostics dashboard is accessible at:
> **`http://localhost/StudentHub/pages/deployment.php`**

* **Server & PHP Telemetry**: Real-time inspection of OS (`Windows 11 Pro/Home x86_64`), Apache 2.4 server software, PHP 8.2 version, memory limits (`512M`), and max file upload limits (`40M`).
* **PHP Extension Audit**: Automated audit verifying 10 required extensions (`pdo`, `pdo_mysql`, `mysqli`, `json`, `session`, `openssl`, `mbstring`, `fileinfo`, `curl`, `exif`).
* **Database Telemetry**: Live row counters, storage engine confirmation, and `sp_get_dashboard_stats()` execution metrics.
* **Persistent Storage Telemetry**: Concurrency, existence, and writability verification of `uploads/posters/`, `data/registrations.csv`, and `data/registrations.json`.

---

## 📁 Project Directory & File Structure

```text
StudentHub/
├── index.html                  # Semantic Home Page (Practical 2)
├── index.php                   # Full-Stack Dynamic Home Page with RBAC Navbar (Practical 15)
├── db.php                      # Root Database Connection Forwarder
├── .env.example                # Production Environment Configuration Template (Practical 15)
├── .gitignore                  # Git VCS Ignore Rules for OS, IDE & Credentials
├── test_connection.php         # Diagnostic Script for MySQLi, PDO & Stored Procedures (Practical 8)
├── sql/                        # Relational Database Schema & Data Dumps
│   └── studenthub.sql          # 6 normalized tables, stored procedures & seed data
├── api/                        # RESTful JSON API Endpoints (Practical 13)
│   └── events.php              # Asynchronous JSON API supporting GET, POST, PUT, DELETE
├── includes/                   # Core Server-Side Business Logic & Helpers
│   ├── auth.php                # Sessions, timeout, password_verify, RBAC & Remember-Me tokens (Practical 10)
│   ├── db.php                  # Central MySQLi & PDO prepared database handler (Practical 8)
│   ├── csrf.php                # Anti-CSRF token generator & timing-safe validator (Practical 7 & 9)
│   └── form_helpers.php        # Input sanitization & safe atomic file locking flock() (Practical 7)
├── pages/                      # Portal Subpages & Administrative Modules
│   ├── admin.html              # Static Admin landmark skeleton (Practical 2)
│   ├── admin_dashboard.php     # RBAC Admin Dashboard with KPI stats, user toggle & analytics chart (Practical 14)
│   ├── deployment.php          # System Deployment & Master Diagnostics Center (Practical 15)
│   ├── students.php            # Student Management CRUD with search, filter, CSV export & soft delete (Practical 11)
│   ├── events_admin.php        # Event Management CRUD with poster upload & validation (Practical 12)
│   ├── events_ajax.php         # Single-page AJAX event CRUD with debounced search & Fetch API (Practical 13)
│   ├── login.php               # Secure login with sessions, timeout alert & Remember-Me (Practical 10)
│   ├── process_login.php       # Password verification, session regeneration & RBAC redirect (Practical 10)
│   ├── logout.php              # Session destruction & remember token invalidation (Practical 10)
│   ├── register.php            # Registration form with MySQLi prepared insert & audit log (Practical 9)
│   ├── process_register.php    # Server-side validation, Bcrypt hashing & duplicate check (Practical 9)
│   ├── dashboard.php           # Authenticated role-protected student dashboard (Practical 10)
│   ├── view_records.php        # Stored CSV/JSON records viewer (Practical 7)
│   ├── events.html             # Dynamic campus events directory with live Fetch API (Practical 6)
│   ├── courses.html            # Course curriculum with syllabus popup modals (Practical 4)
│   ├── attendance.html         # Subject-wise attendance tracker
│   ├── assignment.html         # Assessment deadlines & submission status
│   ├── result.html             # Academic SGPA/CGPA grade sheets
│   ├── profile.html            # Student profile with academic credentials
│   ├── profile.php             # Authenticated student profile editor with audit logging
│   ├── faq.html                # Searchable FAQ directory with accordion UI (Practical 6)
│   ├── feedback.html           # Interactive student feedback form
│   ├── contact.html            # Static contact page
│   ├── contact.php             # Dynamic contact form with server-side validation
│   └── process_contact.php     # Contact form submission handler
├── tests/                      # Automated Verification & Test Suites (Practical 15)
│   └── test_all_practicals.php # Master automated test runner covering all 15 practical assertions
├── uploads/                    # User-Uploaded Media Assets
│   └── posters/                # Event poster uploads protected by .htaccess execution denial (Practical 12)
├── data/                       # External JSON & CSV Datasets (>= 15 records each)
│   ├── events.json             # 16 campus events, hackathons & workshops
│   ├── students.json           # 16 verified student profile records
│   ├── faqs.json               # 16 academic & portal FAQ entries
│   ├── registrations.csv       # Practical 7 CSV file archive
│   ├── registrations.json      # Practical 7 JSON file archive
│   ├── contacts.csv            # Contact inquiry CSV archive
│   └── contacts.json           # Contact inquiry JSON archive
├── css/                        # Global & Modular Stylesheets
│   ├── style.css               # Global theme tokens, navbar, grid, accessibility & utilities
│   ├── index.css               # Home page specific hero and layout styles
│   └── *.css                   # Page-specific modular stylesheets
├── js/                         # Client-Side Scripts
│   ├── script.js               # Theme toggle, slider, accordion, modal, validation & Fetch API
│   └── components.js           # Practical 3 Advanced jQuery reusable layout component loader
├── images/                     # Branding, Assets & Mockups
│   ├── logo.png                # StudentHub portal logo
│   └── *.png                   # System sitemap & wireframes
└── docs/                       # Project Documentation & Specifications
    ├── apache_vhost.conf       # Apache Virtual Host configuration for studenthub.local (Practical 15)
    ├── Deployment_Guide.md     # Step-by-step local & cloud deployment instructions (Practical 15)
    ├── Requirement_Gathering.md# Functional requirements, user roles, ER model & REST route plan (Practical 1)
    ├── Accessibility_Checklist.md# WCAG 2.1 AA accessibility verification checklist (Practical 2)
    ├── Validation_Test_Cases.md# Registration form test cases matrix (Practical 5)
    ├── Practical_6_Test_Report.md# Fetch API, array methods & filter test report (Practical 6)
    └── Practical_7_Test_Report.md# PHP server-side validation & file storage report (Practical 7)
```

---

## 🎓 Academic Credits & License

* **Institution:** Charotar University of Science and Technology (CHARUSAT)
* **Faculty:** Devang Patel Institute of Advance Technology and Research (DEPSTAR)
* **Course:** ITUE203 - Web Development Frameworks
* **Author:** Dev Patel ([@Dev-Jay99](https://github.com/Dev-Jay99))
* **License:** Released under the [MIT License](LICENSE) for academic education and development.
