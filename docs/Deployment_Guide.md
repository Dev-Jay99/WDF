# StudentHub Portal - Complete Deployment & Integration Guide

**Subject:** Web Development Frameworks (ITUE203)  
**Institution:** Devang Patel Institute of Advance Technology and Research (DEPSTAR)  
**University:** Charotar University of Science and Technology (CHARUSAT)  
**Practical 15:** Final Integration, Testing, Documentation, GitHub Submission, and Local Deployment  

---

## 1. Overview & System Integration

The **StudentHub Portal** integrates 15 practical modules into a cohesive, production-ready web application:
- **Frontend Layer:** Semantic HTML5, CSS3 (CSS Grid & Flexbox), Vanilla JavaScript (ES6+), and responsive mobile drawer.
- **Data & APIs:** Asynchronous Fetch API consumers, external JSON datasets, and RESTful PHP JSON API (`api/events.php`).
- **Backend Architecture:** Native PHP 8.2 backend with form validation, sanitization, cryptographic CSRF tokens, session management, and role-based access control (RBAC).
- **Database Engine:** MySQL 8.x schema (`studenthub_db`) supporting PDO, MySQLi prepared statements, soft deletion, and stored procedures (`sp_get_dashboard_stats`).

---

## 2. Local Deployment with XAMPP (Step-by-Step)

### Step 2.1: Start Services in XAMPP Control Panel
1. Open **XAMPP Control Panel**.
2. Click **Start** for **Apache** (Port 80/443).
3. Click **Start** for **MySQL** (Port 3306).

### Step 2.2: Import Database Schema & Seed Data
1. Open your web browser and navigate to **phpMyAdmin**:
   ```text
   http://localhost/phpmyadmin/
   ```
2. Click **New** in the left sidebar and create a database named:
   ```sql
   studenthub_db
   ```
   *(Collation: `utf8mb4_unicode_ci`)*
3. Select `studenthub_db` and click the **Import** tab.
4. Choose the file located at:
   ```text
   StudentHub/sql/studenthub.sql
   ```
5. Click **Import** at the bottom of the page. All 6 tables, 2 stored procedures, and 20+ seed records will be created.

### Step 2.3: Verify Database Connectivity
Navigate to the built-in diagnostic test script:
```text
http://localhost/StudentHub/test_connection.php
```
Verify that all 3 checks report green status:
- MySQLi Connection: **PASSED**
- PHP PDO & Prepared Statements: **PASSED**
- Stored Procedure Execution: **PASSED**

---

## 3. Practical 15 Intermediate Extension: Local Apache Virtual Host

To run the portal cleanly under a custom domain (`http://studenthub.local`):

### Step 3.1: Configure Windows Hosts File
1. Open **Notepad** as Administrator.
2. Open `C:\Windows\System32\drivers\etc\hosts`.
3. Add the following entry to the bottom:
   ```text
   127.0.0.1    studenthub.local
   127.0.0.1    www.studenthub.local
   ```
4. Save the file.

### Step 3.2: Configure Apache VirtualHost
1. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf`.
2. Append the configuration found in [`docs/apache_vhost.conf`](./apache_vhost.conf):
   ```apache
   <VirtualHost *:80>
       ServerAdmin admin@charusat.edu.in
       DocumentRoot "d:/DEPSTAR SEM3/WDF/StudentHub"
       ServerName studenthub.local
       ServerAlias www.studenthub.local

       <Directory "d:/DEPSTAR SEM3/WDF/StudentHub">
           Options Indexes FollowSymLinks MultiViews
           AllowOverride All
           Require all granted
           DirectoryIndex index.php index.html
       </Directory>
   </VirtualHost>
   ```
3. Open `C:\xampp\apache\conf\httpd.conf` and ensure `Include conf/extra/httpd-vhosts.conf` is uncommented.
4. Restart Apache in the XAMPP Control Panel.
5. Visit `http://studenthub.local/` in your browser.

---

## 4. Practical 15 Advanced Extension: Online Deployment & Hosting Architecture

### Architecture Distinction:
1. **Static Frontend (GitHub Pages / Vercel / Netlify):**
   - The static HTML/CSS/JS portion (`index.html`, `pages/*.html`, `data/*.json`) can be hosted directly on **GitHub Pages**.
   - *Limitation:* Static hosts cannot execute server-side PHP scripts or connect directly to MySQL databases.
2. **Full-Stack PHP & MySQL Hosting (InfinityFree, Render, Railway, cPanel):**
   - **Shared Hosting (cPanel / InfinityFree / Hostinger):**
     - Upload the `StudentHub/` folder to `public_html/`.
     - Create a MySQL database and user in cPanel.
     - Update credentials in `includes/db.php`:
       ```php
       define('DB_HOST', 'sqlxxx.epizy.com');
       define('DB_USER', 'epiz_xxxxxxx');
       define('DB_PASS', 'your_password');
       define('DB_NAME', 'epiz_xxxxxxx_studenthub');
       ```
   - **Containerized Cloud (Render / Railway):**
     - Package with Docker containing Apache + PHP 8.2 with `pdo_mysql` and `mysqli` extensions.
     - Connect to a managed MySQL service.

---

## 5. System Test & Verification Checklist

| Test Item | Verification Method | Expected Result | Status |
| :--- | :--- | :--- | :--- |
| **Page Navigation** | Click all links across header & footer | No broken 404 links; breadcrumbs update | **PASS** |
| **Accessibility (WCAG)** | Tab keyboard navigation & Screen-reader check | Focus rings visible; skip link functional | **PASS** |
| **Theme Persistence** | Toggle Light/Dark mode and reload | Theme choice persists in `localStorage` | **PASS** |
| **Form Validation** | Submit registration with invalid inputs | Inline red error messages and regex alerts | **PASS** |
| **Canvas CAPTCHA** | Enter incorrect and correct code | Blocks submission on mismatch | **PASS** |
| **Fetch API & Filter** | Search and sort on `events.html` | Immediate client-side filtering without reload | **PASS** |
| **CSRF Protection** | Tamper or omit hidden token | Rejects submission with 403 / CSRF alert | **PASS** |
| **Password Hashing** | Inspect `users` table after registration | Stored as 60-character Bcrypt hash | **PASS** |
| **Session Security** | Wait 15 minutes of inactivity | Automatically logs out with timeout notice | **PASS** |
| **Remember-Me** | Check remember-me; inspect browser cookies | Token stored as SHA-256 in DB, cookie set | **PASS** |
| **Student CRUD** | Add student, edit record, soft delete | Changes reflected; status set to 'deleted' | **PASS** |
| **CSV Export** | Click 'Export to CSV' in Student CRUD | Generates and downloads clean CSV spreadsheet | **PASS** |
| **Event Poster Upload** | Upload image > 2MB or non-image file | Validated server-side; blocked with error | **PASS** |
| **Upload Security** | Inspect `uploads/posters/.htaccess` | Script execution disabled | **PASS** |
| **AJAX JSON API** | Perform CRUD in `events_ajax.php` | Zero page reloads; HTTP status codes shown | **PASS** |
| **Role-Based Access** | Attempt to view `admin_dashboard.php` as student | Blocked with 403 Forbidden | **PASS** |
| **User Status Toggle** | Click Toggle in Admin Panel | User toggles between Active and Inactive | **PASS** |
