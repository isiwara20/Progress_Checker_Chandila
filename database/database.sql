-- =======================================================================
-- Chandila Web Development – Project & Customer Management System
-- Database Schema & Initial Seed Data
-- Compatible with MySQL 5.7+ / MariaDB 10.4+ / phpMyAdmin
-- Timezone: Asia/Colombo (Sri Lanka)
-- =======================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+05:30";

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `chandila_pms` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `chandila_pms`;

-- --------------------------------------------------------
-- Table: users (Admins & Developers)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `role` ENUM('admin', 'developer', 'customer_care') NOT NULL DEFAULT 'developer',
  `skills` VARCHAR(255) DEFAULT 'Full Stack Development',
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `avatar` VARCHAR(255) DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role` (`role`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: customers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(100) NOT NULL,
  `company_name` VARCHAR(150) DEFAULT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `whatsapp_number` VARCHAR(30) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_customer_email` (`email`),
  INDEX `idx_customer_whatsapp` (`whatsapp_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: projects
-- --------------------------------------------------------
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `job_id` VARCHAR(30) NOT NULL UNIQUE,
  `customer_id` INT(11) NOT NULL,
  `project_title` VARCHAR(200) NOT NULL,
  `project_type` ENUM(
    'Business Website',
    'E-commerce Website',
    'Web Application',
    'Student Management System',
    'Booking System',
    'Custom Web Application',
    'Other'
  ) NOT NULL DEFAULT 'Business Website',
  `description` TEXT NOT NULL,
  `required_features` TEXT NOT NULL,
  `reference_url` VARCHAR(255) DEFAULT NULL,
  `expected_budget` VARCHAR(100) DEFAULT NULL,
  `total_cost` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `advance_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `milestone2_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `final_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `status` ENUM(
    'Request Submitted',
    'Request Reviewed',
    'Project Confirmed',
    'Advance Payment Pending',
    'Advance Payment Confirmed',
    'Development Started',
    'Development in Progress',
    'Second Payment Pending',
    'Client Review / Revisions',
    'Final Payment Pending',
    'Hosting & Deployment',
    'Project Completed',
    'Project Delivered',
    'Cancelled'
  ) NOT NULL DEFAULT 'Request Submitted',
  `progress_percentage` INT(3) NOT NULL DEFAULT 0,
  `assigned_developer_id` INT(11) DEFAULT NULL,
  `assigned_date` DATETIME DEFAULT NULL,
  `expected_completion_date` DATE DEFAULT NULL,
  `development_completed_date` DATETIME DEFAULT NULL,
  `hosting_url` VARCHAR(255) DEFAULT NULL,
  `hosting_deployed_date` DATETIME DEFAULT NULL,
  `delivered_date` DATETIME DEFAULT NULL,
  `additional_requirements` TEXT DEFAULT NULL,
  `internal_notes` TEXT DEFAULT NULL,
  `is_approved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_job_id` (`job_id`),
  INDEX `idx_customer` (`customer_id`),
  INDEX `idx_assigned_dev` (`assigned_developer_id`),
  INDEX `idx_status` (`status`),
  CONSTRAINT `fk_project_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_project_dev` FOREIGN KEY (`assigned_developer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: project_milestones
-- --------------------------------------------------------
DROP TABLE IF EXISTS `project_milestones`;
CREATE TABLE `project_milestones` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `milestone_name` VARCHAR(150) NOT NULL,
  `target_percentage` INT(3) NOT NULL DEFAULT 0,
  `is_completed` TINYINT(1) NOT NULL DEFAULT 0,
  `completed_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_milestone_project` (`project_id`),
  CONSTRAINT `fk_milestone_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: project_progress
-- --------------------------------------------------------
DROP TABLE IF EXISTS `project_progress`;
CREATE TABLE `project_progress` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `percentage` INT(3) NOT NULL,
  `status_stage` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `internal_note` TEXT DEFAULT NULL,
  `updated_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_progress_project` (`project_id`),
  INDEX `idx_progress_user` (`updated_by`),
  CONSTRAINT `fk_progress_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progress_user` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payments
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `payment_stage` ENUM('Advance Payment', 'Second Payment (50% Milestone)', 'Final Payment', 'Other') NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL DEFAULT 'Bank Transfer',
  `transaction_reference` VARCHAR(100) DEFAULT NULL,
  `receipt_file` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('Pending', 'Payment Received', 'Payment Rejected') NOT NULL DEFAULT 'Pending',
  `admin_notes` TEXT DEFAULT NULL,
  `verified_by` INT(11) DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_payment_project` (`project_id`),
  INDEX `idx_payment_status` (`status`),
  CONSTRAINT `fk_payment_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_payment_verified_by` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: payment_schedules
-- --------------------------------------------------------
DROP TABLE IF EXISTS `payment_schedules`;
CREATE TABLE `payment_schedules` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `stage_name` VARCHAR(100) NOT NULL,
  `due_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('Pending', 'Paid', 'Overdue') NOT NULL DEFAULT 'Pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_schedule_project` (`project_id`),
  CONSTRAINT `fk_schedule_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: project_activities (Timeline & Audit Log)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `project_activities`;
CREATE TABLE `project_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `job_id` VARCHAR(30) NOT NULL,
  `activity_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `updated_by` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activity_project` (`project_id`),
  INDEX `idx_activity_job_id` (`job_id`),
  CONSTRAINT `fk_activity_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: whatsapp_notifications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `whatsapp_notifications`;
CREATE TABLE `whatsapp_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) DEFAULT NULL,
  `job_id` VARCHAR(30) DEFAULT NULL,
  `recipient_phone` VARCHAR(30) NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `message_text` TEXT NOT NULL,
  `status` ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
  `api_response` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_wa_project` (`project_id`),
  INDEX `idx_wa_job_id` (`job_id`),
  INDEX `idx_wa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: email_notifications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `email_notifications`;
CREATE TABLE `email_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) DEFAULT NULL,
  `job_id` VARCHAR(30) DEFAULT NULL,
  `recipient_email` VARCHAR(150) NOT NULL,
  `recipient_name` VARCHAR(120) DEFAULT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body_html` LONGTEXT NOT NULL,
  `event_type` VARCHAR(100) NOT NULL,
  `status` ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
  `mail_response` TEXT DEFAULT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_email_project` (`project_id`),
  INDEX `idx_email_job_id` (`job_id`),
  INDEX `idx_email_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: uploaded_files
-- --------------------------------------------------------
DROP TABLE IF EXISTS `uploaded_files`;
CREATE TABLE `uploaded_files` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) DEFAULT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `stored_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_size` INT(11) NOT NULL,
  `file_type` VARCHAR(100) NOT NULL,
  `uploaded_by` VARCHAR(50) NOT NULL DEFAULT 'customer',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_file_project` (`project_id`),
  CONSTRAINT `fk_file_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: system_settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `system_settings`;
CREATE TABLE `system_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `setting_group` VARCHAR(50) NOT NULL DEFAULT 'general',
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: posts (1080x1080 Square Posts & Project Showcases)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL DEFAULT 'Project Showcase',
  `image_path` VARCHAR(255) NOT NULL,
  `description` TEXT NOT NULL,
  `external_url` VARCHAR(255) DEFAULT NULL,
  `aspect_ratio` VARCHAR(20) NOT NULL DEFAULT '1080x1080',
  `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `views_count` INT(11) NOT NULL DEFAULT 0,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_post_status` (`status`),
  INDEX `idx_post_category` (`category`),
  CONSTRAINT `fk_post_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: career_applications
-- --------------------------------------------------------
DROP TABLE IF EXISTS `career_applications`;
CREATE TABLE `career_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `application_no` VARCHAR(50) NOT NULL UNIQUE,
  `full_name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `position` VARCHAR(150) NOT NULL,
  `experience` VARCHAR(50) NOT NULL,
  `portfolio_url` VARCHAR(255) DEFAULT NULL,
  `cover_letter` TEXT DEFAULT NULL,
  `resume_file` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('New', 'Reviewed', 'Shortlisted', 'Interviewed', 'Rejected', 'Hired') NOT NULL DEFAULT 'New',
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_career_app_no` (`application_no`),
  INDEX `idx_career_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: care_working_hours (Customer Care Duty & Work Hours)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `care_working_hours`;
CREATE TABLE `care_working_hours` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `work_date` DATE NOT NULL,
  `shift_type` VARCHAR(100) NOT NULL DEFAULT 'Standard Full Day',
  `start_time` DATETIME NOT NULL,
  `end_time` DATETIME DEFAULT NULL,
  `total_hours` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('In Progress', 'Completed', 'Approved') NOT NULL DEFAULT 'In Progress',
  `tasks_summary` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_care_user` (`user_id`),
  INDEX `idx_care_date` (`work_date`),
  INDEX `idx_care_status` (`status`),
  CONSTRAINT `fk_care_hours_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =======================================================================
-- INITIAL SEED DATA
-- =======================================================================

-- 1. Insert Initial Staff Users: 1 Admin, 2 Developers & 1 Customer Care
-- Passwords:
-- Admin: Admin@12345
-- Dev 1: Dev1@12345
-- Dev 2: Dev2@12345
-- Customer Care: Care@12345
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `phone`, `role`, `skills`, `status`, `created_at`) VALUES
(1, 'admin', 'admin@chandilaweb.com', '$2y$10$HFi1EpRAXqxRadALqkXyLePXIVxGjbA8a3jQhVSBPWRO6Djji2.HK', 'Chandila Admin', '+94 76 843 3340', 'admin', 'Project Architecture & Operations', 'active', NOW()),
(2, 'dev1', 'dev1@chandilaweb.com', '$2y$10$a.x.hqxBykWUKhMtaT.7puh.X./fWGJ9eMpuNM2VohfceC.L5XWdO', 'Developer 01', '+94 71 234 5678', 'developer', 'Full Stack PHP, MySQL, REST APIs, Laravel', 'active', NOW()),
(3, 'dev2', 'dev2@chandilaweb.com', '$2y$10$F.66vsE59xnBog70LBTH2OzwnXRGYY/dmyOo.Xe3dvFWCJ9.NoIse', 'Developer 02', '+94 76 345 6789', 'developer', 'Frontend UI/UX, Bootstrap 5, Vue.js, Mobile Responsive', 'active', NOW()),
(4, 'care', 'care@chandilaweb.com', '$2y$10$9ASHwK3T29dDJPwNsG/mluY2/pK7y6sJnLRQ9Rq/EzFtI7UtEKBuW', 'Chandila Customer Care', '+94 76 843 3340', 'customer_care', 'Customer Relations, Order Intake & Project Allocation', 'active', NOW());

-- 2. Insert System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_group`) VALUES
('company_name', 'Chandila Web Development', 'general'),
('company_email', 'contact@chandilaweb.com', 'general'),
('company_phone', '076 843 3340', 'general'),
('company_whatsapp', '076 843 3340', 'general'),
('company_address', 'No. 45, Galle Road, Colombo 03, Sri Lanka', 'general'),
('currency_symbol', 'Rs.', 'general'),
('currency_code', 'LKR', 'general'),
('whatsapp_provider', 'waclient', 'whatsapp'),
('whatsapp_api_url', 'http://localhost:3000/send-message', 'whatsapp'),
('whatsapp_api_token', '', 'whatsapp'),
('whatsapp_instance_id', 'wa-client-01', 'whatsapp'),
('whatsapp_sender_number', '+9476843340', 'whatsapp'),
('bank_name', 'Commercial Bank of Ceylon', 'payment'),
('bank_account_name', 'Chandila Web Development (Pvt) Ltd', 'payment'),
('bank_account_number', '800123456789', 'payment'),
('bank_branch', 'Colombo 03 Branch', 'payment'),
('email_provider', 'mail', 'email'),
('email_sender_name', 'Chandila Web Development', 'email'),
('email_sender_email', 'contact@chandilaweb.com', 'email'),
('email_notifications_enabled', '1', 'email'),
('email_smtp_host', 'mail.chandila.site', 'email'),
('email_smtp_port', '587', 'email'),
('email_smtp_user', '', 'email'),
('email_smtp_pass', '', 'email'),
('email_smtp_secure', 'tls', 'email'),
('invoice_company_name', 'Chandila Web Development (Pvt) Ltd', 'invoice'),
('invoice_tagline', 'Where Innovation Meets Growth', 'invoice'),
('invoice_address', 'No. 45, Galle Road, Colombo 03, Sri Lanka', 'invoice'),
('invoice_phone', '076 843 3340', 'invoice'),
('invoice_email', 'billing@chandilaweb.com', 'invoice'),
('invoice_tax_number', 'PV-00284729', 'invoice'),
('invoice_prefix', 'INV-', 'invoice'),
('invoice_theme_color', '#2563eb', 'invoice'),
('invoice_paid_stamp_text', 'PAID IN FULL', 'invoice'),
('invoice_paid_stamp_style', 'classic_red', 'invoice'),
('invoice_show_watermark', '1', 'invoice'),
('invoice_footer_notes', 'Thank you for choosing Chandila Web Development. All software code, database structures, and production access rights will be permanently transferred upon complete project settlement. Please retain this official invoice for your accounting records.', 'invoice');

-- 3. Insert Sample Customers
INSERT INTO `customers` (`id`, `full_name`, `company_name`, `email`, `phone`, `whatsapp_number`, `address`, `created_at`) VALUES
(1, 'Aqua Super Management', 'Aqua Super (Pvt) Ltd', 'orders@aquasuper.lk', '+94771122334', '+94771122334', '12 Lake View Industrial Zone, Colombo, Sri Lanka', NOW()),
(2, 'Dinithi Jayasinghe', 'Apex Academy Colombo', 'dinithi@apexacademy.edu.lk', '+94719876543', '+94719876543', '88 High Level Road, Nugegoda', NOW()),
(3, 'Lesi POS Solutions', 'Lesi POS Lanka', 'support@lesipos.lk', '+94765544332', '+94765544332', '24 Ward Place, Colombo 07', NOW());

-- 4. Insert Sample Projects Illustrating Complete Lifecycle
-- Project 1: Ongoing active project (Development in Progress)
INSERT INTO `projects` (
  `id`, `job_id`, `customer_id`, `project_title`, `project_type`, `description`, `required_features`,
  `reference_url`, `expected_budget`, `total_cost`, `advance_amount`, `milestone2_amount`, `final_amount`,
  `total_paid`, `balance_due`, `status`, `progress_percentage`, `assigned_developer_id`, `assigned_date`,
  `expected_completion_date`, `is_approved`, `created_at`
) VALUES (
  1, 'CHW-2026-0001', 1, 'Aqua Super E-Commerce & Water Delivery Portal', 'E-commerce Website',
  'Commercial bottle water ordering, subscription management, and direct home delivery logistics with integrated online payments.',
  'Online ordering system, monthly refill subscriptions, PayHere payment integration, route delivery optimization, automated invoices.',
  'https://aquasuper.lk', 'Rs. 250,000', 250000.00, 75000.00, 125000.00, 50000.00,
  75000.00, 175000.00, 'Development in Progress', 60, 2, NOW() - INTERVAL 10 DAY,
  CURDATE() + INTERVAL 14 DAY, 1, NOW() - INTERVAL 12 DAY
);

-- Project 2: Advance Payment Confirmed & Development Started
INSERT INTO `projects` (
  `id`, `job_id`, `customer_id`, `project_title`, `project_type`, `description`, `required_features`,
  `reference_url`, `expected_budget`, `total_cost`, `advance_amount`, `milestone2_amount`, `final_amount`,
  `total_paid`, `balance_due`, `status`, `progress_percentage`, `assigned_developer_id`, `assigned_date`,
  `expected_completion_date`, `is_approved`, `created_at`
) VALUES (
  2, 'CHW-2026-0002', 2, 'Apex Student & Batch Management System', 'Student Management System',
  'Comprehensive student information system for tertiary institute with student registration, course scheduling, attendance tracking, marks recording, and parent portal.',
  'Student enrollment, batch scheduler, attendance QR scanner, marks entry, student fee management, automated SMS/WhatsApp notices.',
  NULL, 'Rs. 180,000', 180000.00, 50000.00, 90000.00, 40000.00,
  50000.00, 130000.00, 'Development Started', 20, 3, NOW() - INTERVAL 3 DAY,
  CURDATE() + INTERVAL 25 DAY, 1, NOW() - INTERVAL 5 DAY
);

-- Project 3: Completed & Delivered Project
INSERT INTO `projects` (
  `id`, `job_id`, `customer_id`, `project_title`, `project_type`, `description`, `required_features`,
  `reference_url`, `expected_budget`, `total_cost`, `advance_amount`, `milestone2_amount`, `final_amount`,
  `total_paid`, `balance_due`, `status`, `progress_percentage`, `assigned_developer_id`, `assigned_date`,
  `expected_completion_date`, `development_completed_date`, `hosting_url`, `hosting_deployed_date`, `delivered_date`, `is_approved`, `created_at`
) VALUES (
  3, 'CHW-2026-0003', 3, 'Lesi POS – Cloud Retail & Restaurant Point of Sale System', 'Web Application',
  'Cloud-based Point of Sale and inventory management platform for retail stores, supermarkets, and restaurants.',
  'Barcode scanning, receipt thermal printing, real-time stock deductions, multi-outlet management, daily sales analytics.',
  'https://lesipos.lk', 'Rs. 200,000', 200000.00, 60000.00, 100000.00, 40000.00,
  200000.00, 0.00, 'Project Delivered', 100, 2, NOW() - INTERVAL 30 DAY,
  NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 6 DAY, 'https://lesipos.lk', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 3 DAY, 1, NOW() - INTERVAL 35 DAY
);

-- 5. Insert Milestones for Project 1
INSERT INTO `project_milestones` (`project_id`, `milestone_name`, `target_percentage`, `is_completed`, `completed_at`, `notes`) VALUES
(1, 'Architecture & Database Schema Design', 20, 1, NOW() - INTERVAL 8 DAY, 'Database tables and entity relationships verified.'),
(1, 'Frontend UI & Responsive Layouts', 40, 1, NOW() - INTERVAL 5 DAY, 'Bootstrap 5 responsive layouts completed.'),
(1, 'Product Catalog & Shopping Cart Logic', 60, 1, NOW() - INTERVAL 1 DAY, 'Core shopping cart and checkout endpoints operational.'),
(1, 'Payment Gateway & Shipping Integration', 80, 0, NULL, 'Stripe and PayHere sandbox testing pending.'),
(1, 'Quality Assurance, Testing & Client Review', 100, 0, NULL, 'Final automated and manual testing.');

-- 6. Insert Project Progress Logs for Project 1
INSERT INTO `project_progress` (`project_id`, `percentage`, `status_stage`, `description`, `internal_note`, `updated_by`, `created_at`) VALUES
(1, 10, 'Development Started', '10% – Development environment setup and database schema initiated.', 'Git repository created and dev branch initialized.', 2, NOW() - INTERVAL 9 DAY),
(1, 25, 'Development in Progress', '25% – Database design completed. Core tables and relationships mapped.', 'Foreign keys and indexes verified in MySQL.', 2, NOW() - INTERVAL 7 DAY),
(1, 40, 'Development in Progress', '40% – Frontend product listing and category navigation completed.', 'Client requested dark header mode, implemented.', 2, NOW() - INTERVAL 5 DAY),
(1, 60, 'Development in Progress', '60% – Shopping cart session handling and order placement endpoints complete.', 'Next step is Stripe sandbox integration.', 2, NOW() - INTERVAL 1 DAY);

-- 7. Insert Payments for Seed Projects
INSERT INTO `payments` (`project_id`, `payment_stage`, `amount`, `payment_method`, `transaction_reference`, `status`, `admin_notes`, `verified_by`, `verified_at`, `payment_date`, `created_at`) VALUES
(1, 'Advance Payment', 75000.00, 'Bank Transfer', 'BOC-TXN-984321', 'Payment Received', 'Verified via Commercial Bank slip deposit.', 1, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
(2, 'Advance Payment', 50000.00, 'Online Banking', 'COMM-ONL-554432', 'Payment Received', 'Slip confirmed in online banking portal.', 1, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY),
(3, 'Advance Payment', 60000.00, 'Bank Transfer', 'TXN-001', 'Payment Received', 'Verified.', 1, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY, NOW() - INTERVAL 30 DAY),
(3, 'Second Payment (50% Milestone)', 100000.00, 'Bank Transfer', 'TXN-002', 'Payment Received', 'Verified.', 1, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY, NOW() - INTERVAL 15 DAY),
(3, 'Final Payment', 40000.00, 'Bank Transfer', 'TXN-003', 'Payment Received', 'Final settlement confirmed before deployment.', 1, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY);

-- 8. Insert Activities Log for Project 1
INSERT INTO `project_activities` (`project_id`, `job_id`, `activity_type`, `description`, `updated_by`, `created_at`) VALUES
(1, 'CHW-2026-0001', 'Project Submitted', 'Customer submitted project request online.', 'Customer', NOW() - INTERVAL 12 DAY),
(1, 'CHW-2026-0001', 'Project Reviewed', 'Admin reviewed project scope and estimated total cost at Rs. 250,000.', 'Chandila Admin', NOW() - INTERVAL 11 DAY),
(1, 'CHW-2026-0001', 'Advance Payment Confirmed', 'Advance payment of Rs. 75,000 verified. Project officially confirmed.', 'Chandila Admin', NOW() - INTERVAL 10 DAY),
(1, 'CHW-2026-0001', 'Developer Assigned', 'Project assigned to Developer 01.', 'Chandila Admin', NOW() - INTERVAL 10 DAY),
(1, 'CHW-2026-0001', 'Development Started', 'Developer 01 initiated repository and development environment.', 'Developer 01', NOW() - INTERVAL 9 DAY),
(1, 'CHW-2026-0001', 'Progress Updated', 'Progress updated to 60% – Shopping cart logic complete.', 'Developer 01', NOW() - INTERVAL 1 DAY);

-- 9. Insert WhatsApp Notifications Log
INSERT INTO `whatsapp_notifications` (`project_id`, `job_id`, `recipient_phone`, `event_type`, `message_text`, `status`, `sent_at`, `created_at`) VALUES
(1, 'CHW-2026-0001', '+94771122334', 'Project Request Submitted', 'Thank you for choosing Chandila Web Development! Your project request has been successfully received. Your Job ID is CHW-2026-0001. You can use this Job ID to track your project progress.', 'Sent', NOW() - INTERVAL 12 DAY, NOW() - INTERVAL 12 DAY),
(1, 'CHW-2026-0001', '+94771122334', 'Advance Payment Confirmed', 'Your advance payment has been successfully confirmed. Your project is now officially confirmed.', 'Sent', NOW() - INTERVAL 10 DAY, NOW() - INTERVAL 10 DAY),
(1, 'CHW-2026-0001', '+94771122334', 'Project Progress Updated', 'Your project progress has been updated!\n\nJob ID: CHW-2026-0001\nCurrent Progress: 60%\nLatest Update: Shopping cart session handling and order placement endpoints complete.\n\nTrack your project using your Job ID.', 'Sent', NOW() - INTERVAL 1 DAY, NOW() - INTERVAL 1 DAY);

-- 10. Insert Initial 1080x1080 Showcase Posts
INSERT INTO `posts` (`id`, `title`, `category`, `image_path`, `description`, `external_url`, `aspect_ratio`, `status`, `is_featured`, `created_by`, `created_at`) VALUES
(1, 'Aqua Super – Next-Gen Water Solutions E-Commerce', 'Project Showcase', 'uploads/posts/post_aquasuper.png', 'Engineered a lightning-fast responsive e-commerce web platform for Aqua Super with real-time inventory management, automated order routing, and secure online checkout. Resulted in 180% growth in online inquiries.', 'https://aquasuper.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 5 DAY),
(2, 'Jaffna Ads – High-Traffic Regional Classifieds Portal', 'Project Showcase', 'uploads/posts/post_jaffnaads.png', 'Engineered high-scale classified advertising platform serving Northern Sri Lanka with multi-category listings, instant WhatsApp contact integration, location-based filtering, and automated ad expiration schedules.', 'https://jaffnaads.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 4 DAY),
(3, 'Chanu Gems – Luxury Gemstone & International Catalog', 'Project Showcase', 'uploads/posts/post_chanugems.png', 'Bespoke gemstone and precious jewelry luxury catalog featuring high-definition zoom galleries, multi-currency display (USD, EUR, LKR), and direct VIP buyer WhatsApp reservation engine.', 'https://chanugems.com', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 3 DAY),
(4, 'Ceylon Therapist – Healthcare & Therapy Booking Platform', 'Project Showcase', 'uploads/posts/post_ceylontherapist.png', 'Complete patient appointment reservation and therapist schedule management system with SMS/WhatsApp notifications and automated timeslot availability calendar.', 'https://ceylontherapist.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 2 DAY),
(5, 'Lesi POS – Cloud Restaurant & Retail Management', 'Product Launch', 'uploads/posts/post_lesipos.png', 'Full-stack cloud-native Point of Sale system supporting thermal bill printing, barcode scanning, shift registers, and granular real-time profit/loss reporting.', 'https://lesipos.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 1 DAY),
(6, 'Chandila 500+ Milestone: Digital Transformation Sri Lanka', 'Company Milestone', 'uploads/posts/post_milestone500.png', 'Celebrating over 500+ commercial websites and bespoke web applications successfully deployed worldwide! We thank all our clients, partners, and our engineering team for driving innovation.', 'https://chandilaweb.com', '1080x1080', 'published', 1, 1, NOW());


