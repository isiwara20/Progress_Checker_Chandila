<?php
/**
 * Chandila Web Development – Project & Customer Management System
 * Global Application Configuration File
 * Timezone: Asia/Colombo (Sri Lanka)
 */

// Include Guard: Prevent multiple definitions when included via different relative paths or symlinks
if (defined('CHANDILA_CONFIG_LOADED')) {
    return;
}
define('CHANDILA_CONFIG_LOADED', true);

// Global Output Buffering to prevent header modification errors during redirects
if (ob_get_level() === 0) {
    ob_start();
}

// Environment Detection & Error Reporting
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1']) || ($_SERVER['HTTP_HOST'] ?? '') === 'localhost';
error_reporting(E_ALL);
ini_set('display_errors', $isLocal ? '1' : '0');
ini_set('log_errors', '1');

// Set strict Sri Lanka Timezone
date_default_timezone_set('Asia/Colombo');

// Database Configuration
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_NAME')) define('DB_NAME', 'chandila_pms');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// System Constants
if (!defined('APP_NAME')) define('APP_NAME', 'Chandila Web Development');
if (!defined('APP_TAGLINE')) define('APP_TAGLINE', 'Premium Web & Digital Solutions');
if (!defined('APP_VERSION')) define('APP_VERSION', '1.0.0');
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', 'Rs.');
if (!defined('CURRENCY_CODE')) define('CURRENCY_CODE', 'LKR');

// Official Contact & WhatsApp (076 843 3340 / +94 76 843 3340)
if (!defined('COMPANY_PHONE')) define('COMPANY_PHONE', '076 843 3340');
if (!defined('COMPANY_PHONE_RAW')) define('COMPANY_PHONE_RAW', '0768433340');
if (!defined('COMPANY_PHONE_INTL')) define('COMPANY_PHONE_INTL', '+94 76 843 3340');
if (!defined('COMPANY_WHATSAPP')) define('COMPANY_WHATSAPP', '9476843340');
if (!defined('COMPANY_WHATSAPP_LINK')) define('COMPANY_WHATSAPP_LINK', 'https://wa.me/9476843340');
if (!defined('COMPANY_EMAIL')) define('COMPANY_EMAIL', 'contact@chandilaweb.com');

// Paths and Directories
if (!defined('ROOT_PATH')) define('ROOT_PATH', dirname(__DIR__));
if (!defined('UPLOAD_DIR')) define('UPLOAD_DIR', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR);
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB limit
if (!defined('ALLOWED_FILE_EXTENSIONS')) define('ALLOWED_FILE_EXTENSIONS', ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip', 'txt']);

// Base URL detection (Seamlessly supports both cPanel root domains and localhost subdirectories)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $scriptDir = '';
    }

    if (strpos($scriptDir, '/admin') !== false || strpos($scriptDir, '/developer') !== false || strpos($scriptDir, '/care') !== false || strpos($scriptDir, '/api') !== false) {
        $parentScriptDir = dirname($scriptDir);
        if ($parentScriptDir === '/' || $parentScriptDir === '\\' || $parentScriptDir === '.') {
            $parentScriptDir = '';
        }
        define('BASE_URL', rtrim($protocol . $host . $parentScriptDir, '/'));
    } else {
        define('BASE_URL', rtrim($protocol . $host . $scriptDir, '/'));
    }
}

// Project Status Stages (including 50% Milestone Second Payment Pending)
if (!defined('PROJECT_STAGES')) {
    define('PROJECT_STAGES', [
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
        'Project Delivered'
    ]);
}

// Project Types
if (!defined('PROJECT_TYPES')) {
    define('PROJECT_TYPES', [
        'Business Website',
        'E-commerce Website',
        'Web Application',
        'Student Management System',
        'Booking System',
        'Custom Web Application',
        'Other'
    ]);
}

// Payment Stages
if (!defined('PAYMENT_STAGES')) {
    define('PAYMENT_STAGES', [
        'Advance Payment',
        'Second Payment (50% Milestone)',
        'Final Payment',
        'Other'
    ]);
}

// Start Session securely if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
