<?php
/**
 * Chandila Web Development – Project & Customer Management System
 * Global Application Configuration File
 * Timezone: Asia/Colombo (Sri Lanka)
 */

// Global Output Buffering to prevent header modification errors during redirects
if (ob_get_level() === 0) {
    ob_start();
}

// Strict error reporting for clean code, disable display in production if needed
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set strict Sri Lanka Timezone
date_default_timezone_set('Asia/Colombo');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'chandila_pms');
define('DB_CHARSET', 'utf8mb4');

// System Constants
define('APP_NAME', 'Chandila Web Development');
define('APP_TAGLINE', 'Premium Web & Digital Solutions');
define('APP_VERSION', '1.0.0');
define('CURRENCY_SYMBOL', 'Rs.');
define('CURRENCY_CODE', 'LKR');

// Official Contact & WhatsApp (0768433340 / +94 76 843 3340)
define('COMPANY_PHONE', '076 843 3340');
define('COMPANY_PHONE_RAW', '0768433340');
define('COMPANY_PHONE_INTL', '+94 76 843 3340');
define('COMPANY_WHATSAPP', '9476843340');
define('COMPANY_WHATSAPP_LINK', 'https://wa.me/9476843340');
define('COMPANY_EMAIL', 'contact@chandilaweb.com');

// Paths and Directories
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB limit
define('ALLOWED_FILE_EXTENSIONS', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip', 'txt']);

// Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
// Adjust base url dynamically
$dirParts = explode('/', trim($scriptDir, '/'));
$baseFolder = !empty($dirParts[0]) ? '/' . $dirParts[0] : '';
// If running from subdirectory like /Progress_Checker_Chandila
if (strpos($scriptDir, '/admin') !== false || strpos($scriptDir, '/developer') !== false || strpos($scriptDir, '/api') !== false) {
    $parentScriptDir = dirname($scriptDir);
    define('BASE_URL', rtrim($protocol . $host . $parentScriptDir, '/'));
} else {
    define('BASE_URL', rtrim($protocol . $host . $scriptDir, '/'));
}

// 12 Project Status Stages Defined in Requirement
const PROJECT_STAGES = [
    'Request Submitted',
    'Request Reviewed',
    'Project Confirmed',
    'Advance Payment Pending',
    'Advance Payment Confirmed',
    'Development Started',
    'Development in Progress',
    'Client Review / Revisions',
    'Final Payment Pending',
    'Hosting & Deployment',
    'Project Completed',
    'Project Delivered'
];

// Project Types
const PROJECT_TYPES = [
    'Business Website',
    'E-commerce Website',
    'Web Application',
    'Student Management System',
    'Booking System',
    'Custom Web Application',
    'Other'
];

// Payment Stages
const PAYMENT_STAGES = [
    'Advance Payment',
    'Second Payment (50% Milestone)',
    'Final Payment',
    'Other'
];

// Start Session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Secure cookie parameters
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

