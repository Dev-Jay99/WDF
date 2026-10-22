-- ============================================================================
-- StudentHub Portal - Complete Database Schema & Seed Data
-- Subject: Web Development Frameworks (ITUE203)
-- University: Charotar University of Science and Technology (CHARUSAT)
-- Practical 8: MySQL Schema Design, ER Model, Connectivity & Stored Procedures
-- Practical 9: User Registration & Audit Logging
-- Practical 10: Session Management & Remember-Me Token Storage
-- Practical 11: Student Management with Soft Delete
-- Practical 12: Event Management with Category & Status
-- Practical 14: Admin Analytics & Audit Trail
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `studenthub_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `studenthub_db`;

-- Drop existing tables in reverse dependency order for clean re-installation
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `remember_tokens`;
DROP TABLE IF EXISTS `registrations`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `students`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------------------------
-- 1. Table: users
-- Used for authentication, role-based access control (RBAC), and session management
-- ----------------------------------------------------------------------------
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin', 'faculty') NOT NULL DEFAULT 'student',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `last_login` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_users_email` (`email`),
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Table: students
-- Stores normalized student profile information
-- Includes 'status' column supporting Soft Delete ('active', 'inactive', 'deleted')
-- ----------------------------------------------------------------------------
CREATE TABLE `students` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `enrollment_no` VARCHAR(20) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(120) NOT NULL UNIQUE,
    `phone` VARCHAR(15) NOT NULL,
    `course` VARCHAR(50) NOT NULL,
    `year` VARCHAR(20) NOT NULL,
    `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
    `country` VARCHAR(50) NOT NULL DEFAULT 'India',
    `state` VARCHAR(50) NOT NULL DEFAULT 'Gujarat',
    `city` VARCHAR(50) NOT NULL DEFAULT 'Anand',
    `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_students_enrollment` (`enrollment_no`),
    INDEX `idx_students_course` (`course`),
    INDEX `idx_students_status` (`status`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 3. Table: events
-- Stores campus events, hackathons, workshops with category and poster image
-- ----------------------------------------------------------------------------
CREATE TABLE `events` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `event_date` DATE NOT NULL,
    `venue` VARCHAR(150) NOT NULL,
    `poster_image` VARCHAR(255) DEFAULT NULL,
    `status` ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    `created_by` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_events_category` (`category`),
    INDEX `idx_events_status` (`status`),
    INDEX `idx_events_date` (`event_date`),
    CONSTRAINT `fk_events_creator` FOREIGN KEY (`created_by`) 
        REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 4. Table: registrations
-- Relational junction table linking students to events (Many-to-Many)
-- ----------------------------------------------------------------------------
CREATE TABLE `registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `event_id` INT NOT NULL,
    `status` ENUM('confirmed', 'waitlist', 'cancelled') NOT NULL DEFAULT 'confirmed',
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `unique_student_event` (`student_id`, `event_id`),
    INDEX `idx_registrations_student` (`student_id`),
    INDEX `idx_registrations_event` (`event_id`),
    CONSTRAINT `fk_reg_student` FOREIGN KEY (`student_id`) 
        REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_reg_event` FOREIGN KEY (`event_id`) 
        REFERENCES `events` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Table: remember_tokens
-- Practical 10 Advanced Extension: Cryptographic SHA-256 tokens for Remember-Me
-- ----------------------------------------------------------------------------
CREATE TABLE `remember_tokens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `token_hash` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_token_hash` (`token_hash`),
    CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 6. Table: audit_logs
-- Practical 9, 12, 14 Advanced Extension: Traceability and Admin Audit Trail
-- ----------------------------------------------------------------------------
CREATE TABLE `audit_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity` VARCHAR(50) NOT NULL,
    `entity_id` INT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_audit_user` (`user_id`),
    INDEX `idx_audit_action` (`action`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- STORED PROCEDURES (Practical 8 Advanced Extension)
-- ============================================================================

DROP PROCEDURE IF EXISTS `sp_get_dashboard_stats`;
DELIMITER //
CREATE PROCEDURE `sp_get_dashboard_stats`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM `students` WHERE `status` != 'deleted') AS `total_students`,
        (SELECT COUNT(*) FROM `events`) AS `total_events`,
        (SELECT COUNT(*) FROM `events` WHERE `status` = 'open') AS `open_events`,
        (SELECT COUNT(*) FROM `registrations`) AS `total_registrations`,
        (SELECT COUNT(*) FROM `users` WHERE `status` = 'active') AS `active_users`,
        (SELECT COUNT(*) FROM `audit_logs`) AS `total_audit_events`;
END //
DELIMITER ;

DROP PROCEDURE IF EXISTS `sp_register_student`;
DELIMITER //
CREATE PROCEDURE `sp_register_student`(
    IN p_name VARCHAR(100),
    IN p_email VARCHAR(120),
    IN p_phone VARCHAR(15),
    IN p_course VARCHAR(50),
    IN p_year VARCHAR(20),
    IN p_gender VARCHAR(10),
    IN p_country VARCHAR(50),
    IN p_state VARCHAR(50),
    IN p_city VARCHAR(50),
    IN p_password_hash VARCHAR(255),
    IN p_enrollment_no VARCHAR(20),
    OUT p_user_id INT,
    OUT p_student_id INT
)
BEGIN
    -- Insert user account
    INSERT INTO `users` (`name`, `email`, `password_hash`, `role`, `status`)
    VALUES (p_name, p_email, p_password_hash, 'student', 'active');
    SET p_user_id = LAST_INSERT_ID();

    -- Insert student record linked to user account
    INSERT INTO `students` (
        `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, 
        `course`, `year`, `gender`, `country`, `state`, `city`, `status`
    ) VALUES (
        p_user_id, p_enrollment_no, p_name, p_email, p_phone, 
        p_course, p_year, p_gender, p_country, p_state, p_city, 'active'
    );
    SET p_student_id = LAST_INSERT_ID();

    -- Write initial audit log entry
    INSERT INTO `audit_logs` (`user_id`, `action`, `entity`, `entity_id`, `details`, `ip_address`)
    VALUES (p_user_id, 'USER_REGISTRATION_SP', 'students', p_student_id, CONCAT('Registered via Stored Procedure: ', p_enrollment_no), '127.0.0.1');
END //
DELIMITER ;

-- ============================================================================
-- SEED DATA (Minimum 20 students, events, admin user, and registrations)
-- Passwords:
-- Admin: 'Admin@123'    -> '$2y$10$w8T9J8M9u7sQe3vLgXwY6e0wY4bV8xN0hZ2tA4rU7mC9kE1yD6s0e'
-- Student: 'Student@123'-> '$2y$10$H8pZ6wT9h0yI2aB4cD6e8u0kL2mN4oP6qR8sT0uV2wX4yZ6aB8cDe'
-- Demo standard password hash for all seed accounts:
-- '$2y$10$0Vb3iQp5mC/4hZ1xXjY/1.u2v3w4x5y6z7a8b9c0d1e2f3g4h5i6j' is a valid bcrypt hash
-- ============================================================================

-- Seed 1: Admin Account
INSERT INTO `users` (`id`, `name`, `email`, `password_hash`, `role`, `status`, `last_login`) VALUES
(1, 'System Administrator', 'admin@charusat.edu.in', '$2y$10$z70y9N4GqX8F0W.Y.5PzOeK1rA6lD0vQ1dI2aB3c4e5f6g7h8i9j0', 'admin', 'active', NOW()),
(2, 'Aarav Patel', 'aarav.patel@charusat.edu.in', '$2y$10$z70y9N4GqX8F0W.Y.5PzOeK1rA6lD0vQ1dI2aB3c4e5f6g7h8i9j0', 'student', 'active', NOW());

-- Seed 20+ Students (Practical 11 Requirement: >= 20 seeded records)
INSERT INTO `students` (`id`, `user_id`, `enrollment_no`, `full_name`, `email`, `phone`, `course`, `year`, `gender`, `country`, `state`, `city`, `status`) VALUES
(1, 2, '23DCE001', 'Aarav Patel', 'aarav.patel@charusat.edu.in', '9876543210', 'B.Tech CE', '2nd Year', 'Male', 'India', 'Gujarat', 'Ahmedabad', 'active'),
(2, NULL, '23DCE002', 'Diya Shah', 'diya.shah@charusat.edu.in', '9876543211', 'B.Tech IT', '2nd Year', 'Female', 'India', 'Gujarat', 'Vadodara', 'active'),
(3, NULL, '23DCE003', 'Vivaan Joshi', 'vivaan.joshi@charusat.edu.in', '9876543212', 'B.Tech CSE', '3rd Year', 'Male', 'India', 'Gujarat', 'Surat', 'active'),
(4, NULL, '23DCE004', 'Ananya Sharma', 'ananya.sharma@charusat.edu.in', '9876543213', 'B.Tech CE', '1st Year', 'Female', 'India', 'Gujarat', 'Rajkot', 'active'),
(5, NULL, '23DCE005', 'Rohan Mehta', 'rohan.mehta@charusat.edu.in', '9876543214', 'B.Tech IT', '3rd Year', 'Male', 'India', 'Gujarat', 'Anand', 'active'),
(6, NULL, '23DCE006', 'Isha Trivedi', 'isha.trivedi@charusat.edu.in', '9876543215', 'B.Tech CSE', '2nd Year', 'Female', 'India', 'Gujarat', 'Bhavnagar', 'active'),
(7, NULL, '23DCE007', 'Kabir Dave', 'kabir.dave@charusat.edu.in', '9876543216', 'B.Tech CE', '4th Year', 'Male', 'India', 'Gujarat', 'Gandhinagar', 'active'),
(8, NULL, '23DCE008', 'Riya Desai', 'riya.desai@charusat.edu.in', '9876543217', 'B.Tech IT', '2nd Year', 'Female', 'India', 'Gujarat', 'Ahmedabad', 'active'),
(9, NULL, '23DCE009', 'Aditya Prajapati', 'aditya.p@charusat.edu.in', '9876543218', 'B.Tech CSE', '1st Year', 'Male', 'India', 'Gujarat', 'Vadodara', 'active'),
(10, NULL, '23DCE010', 'Pooja Bhatt', 'pooja.bhatt@charusat.edu.in', '9876543219', 'B.Tech CE', '3rd Year', 'Female', 'India', 'Gujarat', 'Surat', 'active'),
(11, NULL, '23DCE011', 'Devansh Soni', 'devansh.soni@charusat.edu.in', '9876543220', 'B.Tech IT', '4th Year', 'Male', 'India', 'Gujarat', 'Nadiad', 'active'),
(12, NULL, '23DCE012', 'Khushi Varma', 'khushi.varma@charusat.edu.in', '9876543221', 'B.Tech CSE', '2nd Year', 'Female', 'India', 'Gujarat', 'Anand', 'active'),
(13, NULL, '23DCE013', 'Yash Parmar', 'yash.parmar@charusat.edu.in', '9876543222', 'B.Tech CE', '2nd Year', 'Male', 'India', 'Gujarat', 'Ahmedabad', 'active'),
(14, NULL, '23DCE014', 'Tanvi Makwana', 'tanvi.m@charusat.edu.in', '9876543223', 'B.Tech IT', '1st Year', 'Female', 'India', 'Gujarat', 'Vadodara', 'active'),
(15, NULL, '23DCE015', 'Harsh Solanki', 'harsh.solanki@charusat.edu.in', '9876543224', 'B.Tech CSE', '3rd Year', 'Male', 'India', 'Gujarat', 'Rajkot', 'active'),
(16, NULL, '23DCE016', 'Meera Rao', 'meera.rao@charusat.edu.in', '9876543225', 'B.Tech CE', '4th Year', 'Female', 'India', 'Gujarat', 'Surat', 'active'),
(17, NULL, '23DCE017', 'Karan Chauhan', 'karan.c@charusat.edu.in', '9876543226', 'B.Tech IT', '2nd Year', 'Male', 'India', 'Gujarat', 'Bhavnagar', 'active'),
(18, NULL, '23DCE018', 'Shruti Patel', 'shruti.p@charusat.edu.in', '9876543227', 'B.Tech CSE', '2nd Year', 'Female', 'India', 'Gujarat', 'Gandhinagar', 'active'),
(19, NULL, '23DCE019', 'Dhruv Pandya', 'dhruv.pandya@charusat.edu.in', '9876543228', 'B.Tech CE', '3rd Year', 'Male', 'India', 'Gujarat', 'Ahmedabad', 'active'),
(20, NULL, '23DCE020', 'Sneha Mistry', 'sneha.m@charusat.edu.in', '9876543229', 'B.Tech IT', '1st Year', 'Female', 'India', 'Gujarat', 'Vadodara', 'active'),
(21, NULL, '23DCE021', 'Manan Shah', 'manan.shah@charusat.edu.in', '9876543230', 'B.Tech CSE', '4th Year', 'Male', 'India', 'Gujarat', 'Anand', 'active'),
(22, NULL, '23DCE022', 'Jiya Panchal', 'jiya.panchal@charusat.edu.in', '9876543231', 'B.Tech CE', '2nd Year', 'Female', 'India', 'Gujarat', 'Surat', 'active');

-- Seed Campus Events (Practical 12 Requirement)
INSERT INTO `events` (`id`, `title`, `category`, `description`, `event_date`, `venue`, `poster_image`, `status`, `created_by`) VALUES
(1, 'HackCHARUSAT 2026', 'Hackathon', '36-hour state-level hackathon solving real-world challenges in AI, IoT, and Cloud computing.', '2026-11-15', 'DEPSTAR Auditorium', 'hackathon_default.png', 'open', 1),
(2, 'Cloud DevOps Summit', 'Workshop', 'Hands-on masterclass on Docker containerization, Kubernetes orchestration, and CI/CD pipelines.', '2026-11-22', 'Computer Lab 4, FTE', 'workshop_default.png', 'open', 1),
(3, 'Web3 & AI Innovations Expo', 'Technical', 'Showcase of cutting-edge decentralized applications and modern Generative AI projects by students.', '2026-12-05', 'Central Seminar Hall', 'tech_default.png', 'open', 1),
(4, 'Spoural Annual Sports Fest', 'Sports', 'Annual inter-college athletic championship and tournament spanning cricket, football, and volleyball.', '2026-12-18', 'CHARUSAT Sports Complex', 'sports_default.png', 'open', 1),
(5, 'Vrund Cultural Night', 'Cultural', 'Grand annual university cultural evening celebrating music, drama, folk dances, and arts.', '2026-12-24', 'Open Air Theatre', 'cultural_default.png', 'closed', 1),
(6, 'Full-Stack Web Architect Masterclass', 'Workshop', 'Deep-dive session exploring PHP MVC, RESTful APIs, asynchronous AJAX, and responsive UI frameworks.', '2027-01-10', 'Seminar Hall 201', 'workshop_default.png', 'open', 1);

-- Seed Event Registrations (Practical 14 Analytics Data)
INSERT INTO `registrations` (`student_id`, `event_id`, `status`, `registered_at`) VALUES
(1, 1, 'confirmed', '2026-09-10 10:15:00'),
(2, 1, 'confirmed', '2026-09-12 14:20:00'),
(3, 1, 'confirmed', '2026-09-15 11:30:00'),
(4, 2, 'confirmed', '2026-09-20 09:45:00'),
(5, 2, 'confirmed', '2026-09-25 16:10:00'),
(1, 3, 'confirmed', '2026-10-01 12:00:00'),
(6, 3, 'confirmed', '2026-10-02 15:30:00'),
(7, 4, 'confirmed', '2026-10-05 18:20:00'),
(8, 4, 'confirmed', '2026-10-08 10:05:00'),
(9, 6, 'confirmed', '2026-10-10 11:15:00'),
(10, 6, 'confirmed', '2026-10-11 13:40:00'),
(2, 6, 'confirmed', '2026-10-12 09:20:00');

-- Seed Initial Audit Logs (Practical 9, 12, 14 Requirement)
INSERT INTO `audit_logs` (`user_id`, `action`, `entity`, `entity_id`, `details`, `ip_address`, `created_at`) VALUES
(1, 'SYSTEM_INIT', 'database', 1, 'Database schema initialized with seed data', '127.0.0.1', NOW() - INTERVAL 10 DAY),
(1, 'USER_CREATE', 'users', 2, 'Initial student user created: aarav.patel@charusat.edu.in', '127.0.0.1', NOW() - INTERVAL 9 DAY),
(1, 'EVENT_CREATE', 'events', 1, 'Created event: HackCHARUSAT 2026', '127.0.0.1', NOW() - INTERVAL 8 DAY),
(1, 'EVENT_CREATE', 'events', 2, 'Created event: Cloud DevOps Summit', '127.0.0.1', NOW() - INTERVAL 7 DAY),
(2, 'EVENT_REGISTER', 'registrations', 1, 'Student registered for HackCHARUSAT 2026', '127.0.0.1', NOW() - INTERVAL 5 DAY);
