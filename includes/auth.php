<?php
/**
 * Chandila Web Development – Authentication & Security Helper
 * Role-Based Access Control, CSRF, Rate Limiting & Session Management
 */

if (!defined('CHANDILA_AUTH_LOADED')) {
    define('CHANDILA_AUTH_LOADED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Generate CSRF Token
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render CSRF Hidden Field
 */
function csrfField(): string {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check if a staff user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
}

/**
 * Get logged-in user data
 */
function getCurrentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'email'     => $_SESSION['user_email'],
        'full_name' => $_SESSION['user_fullname'],
        'role'      => $_SESSION['user_role'],
        'phone'     => $_SESSION['user_phone'] ?? '',
        'skills'    => $_SESSION['user_skills'] ?? ''
    ];
}

function getUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

function getUserName(): string {
    return $_SESSION['user_fullname'] ?? 'User';
}

function isAdmin(): bool {
    return getUserRole() === 'admin';
}

function isDeveloper(): bool {
    return getUserRole() === 'developer';
}

function isCustomerCare(): bool {
    return getUserRole() === 'customer_care';
}

/**
 * Require Admin Access
 */
function requireAdmin(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_flash'] = "Please log in to access the Admin Portal.";
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (getUserRole() !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

/**
 * Require Customer Care Access (allows customer_care and admin)
 */
function requireCustomerCare(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_flash'] = "Please log in to access the Customer Care Portal.";
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (getUserRole() !== 'customer_care' && getUserRole() !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

/**
 * Require Developer Access
 */
function requireDeveloper(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_flash'] = "Please log in to access the Developer Portal.";
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    if (getUserRole() !== 'developer' && getUserRole() !== 'admin') {
        http_response_code(403);
        include __DIR__ . '/403.php';
        exit;
    }
}

/**
 * Require Any Staff Login
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        $_SESSION['error_flash'] = "Please log in to continue.";
        header("Location: " . BASE_URL . "/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

/**
 * Rate limiting check (max 5 failed attempts per 15 minutes)
 */
function checkRateLimit(string $identifier): bool {
    $key = 'login_attempts_' . md5($identifier);
    $timeKey = 'login_time_' . md5($identifier);

    if (isset($_SESSION[$timeKey]) && (time() - $_SESSION[$timeKey] > 900)) {
        unset($_SESSION[$key]);
        unset($_SESSION[$timeKey]);
    }

    $attempts = $_SESSION[$key] ?? 0;
    return $attempts < 5;
}

function recordFailedAttempt(string $identifier): void {
    $key = 'login_attempts_' . md5($identifier);
    $timeKey = 'login_time_' . md5($identifier);

    $_SESSION[$key] = ($_SESSION[$key] ?? 0) + 1;
    $_SESSION[$timeKey] = time();
}

function clearRateLimit(string $identifier): void {
    $key = 'login_attempts_' . md5($identifier);
    $timeKey = 'login_time_' . md5($identifier);
    unset($_SESSION[$key]);
    unset($_SESSION[$timeKey]);
}

/**
 * Staff Login Handler
 */
function loginUser(PDO $pdo, string $usernameOrEmail, string $password): array {
    $clientIp = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $identifier = $usernameOrEmail . '_' . $clientIp;

    if (!checkRateLimit($identifier)) {
        return [
            'success' => false,
            'message' => 'Too many failed login attempts. Please wait 15 minutes before trying again.'
        ];
    }

    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE (username = ? OR email = ?) AND status = 'active'
        LIMIT 1
    ");
    $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Clear rate limit
        clearRateLimit($identifier);

        // Regenerate session id to protect against session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']       = (int)$user['id'];
        $_SESSION['username']      = $user['username'];
        $_SESSION['user_email']     = $user['email'];
        $_SESSION['user_fullname'] = $user['full_name'];
        $_SESSION['user_role']     = $user['role'];
        $_SESSION['user_phone']    = $user['phone'];
        $_SESSION['user_skills']   = $user['skills'];

        // Update last login timestamp
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        // Generate new CSRF token
        generateCsrfToken();

        return [
            'success' => true,
            'role'    => $user['role'],
            'user'    => $user
        ];
    }

    recordFailedAttempt($identifier);
    return [
        'success' => false,
        'message' => 'Invalid username or password. Please check your credentials.'
    ];
}

/**
 * Logout User safely
 */
function logoutUser(): void {
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    generateCsrfToken();
}
}


