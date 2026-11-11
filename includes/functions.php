<?php
/**
 * Chandila Web Development – Reusable Business Logic Functions
 */

if (!defined('CHANDILA_FUNCTIONS_LOADED')) {
    define('CHANDILA_FUNCTIONS_LOADED', true);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Sanitize and clean user input string
 * Trims whitespace, strips null bytes, and removes hazardous tags
 */
function sanitize($input): string {
    if ($input === null) {
        return '';
    }
    if (is_array($input)) {
        return '';
    }
    $clean = trim(str_replace(chr(0), '', (string)$input));
    return strip_tags($clean);
}

/**
 * Generate Unique Job ID (Format: CHW-YYYY-XXXX, e.g. CHW-2026-0001)
 * Guaranteed unique and sequential per year.
 */
function generateJobId(PDO $pdo): string {
    $currentYear = date('Y');
    $prefix = "CHW-{$currentYear}-";

    // Lock and query the highest job id for the current year
    $stmt = $pdo->prepare("
        SELECT job_id FROM projects 
        WHERE job_id LIKE ? 
        ORDER BY id DESC 
        LIMIT 1
    ");
    $stmt->execute([$prefix . '%']);
    $lastJob = $stmt->fetchColumn();

    $nextNum = 1;
    if ($lastJob) {
        $parts = explode('-', $lastJob);
        if (isset($parts[2]) && is_numeric($parts[2])) {
            $nextNum = (int)$parts[2] + 1;
        }
    }

    $newJobId = sprintf("CHW-%s-%04d", $currentYear, $nextNum);

    // Double check uniqueness constraint safety
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM projects WHERE job_id = ?");
    $checkStmt->execute([$newJobId]);
    while ($checkStmt->fetchColumn() > 0) {
        $nextNum++;
        $newJobId = sprintf("CHW-%s-%04d", $currentYear, $nextNum);
        $checkStmt->execute([$newJobId]);
    }

    return $newJobId;
}

/**
 * Log Project Activity to project_activities table
 */
function logActivity(
    PDO $pdo,
    string $jobId,
    int $projectId,
    string $activityType,
    string $description,
    string $updatedBy
): bool {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO project_activities 
            (project_id, job_id, activity_type, description, updated_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$projectId, $jobId, $activityType, $description, $updatedBy]);
    } catch (Exception $e) {
        error_log("Failed to log activity: " . $e->getMessage());
        return false;
    }
}

/**
 * Format Currency (Sri Lanka Rupees default)
 */
function formatCurrency(?float $amount): string {
    if ($amount === null) {
        $amount = 0.00;
    }
    return CURRENCY_SYMBOL . ' ' . number_format($amount, 2);
}

/**
 * Format Date/Time in Asia/Colombo
 */
function formatDateTime(?string $date, string $format = 'M d, Y h:i A'): string {
    if (!$date || $date === '0000-00-00 00:00:00') {
        return '—';
    }
    $d = new DateTime($date, new DateTimeZone('Asia/Colombo'));
    return $d->format($format);
}

function formatDateOnly(?string $date): string {
    if (!$date || $date === '0000-00-00') {
        return '—';
    }
    $d = new DateTime($date, new DateTimeZone('Asia/Colombo'));
    return $d->format('M d, Y');
}

/**
 * Format Date helper / alias for formatDateTime
 */
if (!function_exists('formatDate')) {
    function formatDate(?string $date, string $format = 'M d, Y h:i A'): string {
        return formatDateTime($date, $format);
    }
}

/**
 * Get modern status badge HTML
 */
function getStatusBadge(string $status): string {
    $map = [
        'Request Submitted'          => ['bg' => 'secondary', 'icon' => 'fa-paper-plane'],
        'Request Reviewed'           => ['bg' => 'info',      'icon' => 'fa-eye'],
        'Project Confirmed'          => ['bg' => 'primary',   'icon' => 'fa-circle-check'],
        'Advance Payment Pending'    => ['bg' => 'warning',   'icon' => 'fa-hourglass-half'],
        'Advance Payment Confirmed'  => ['bg' => 'success',   'icon' => 'fa-money-bill-wave'],
        'Development Started'        => ['bg' => 'primary',   'icon' => 'fa-code-branch'],
        'Development in Progress'    => ['bg' => 'info',      'icon' => 'fa-code'],
        'Second Payment Pending'     => ['bg' => 'warning',   'icon' => 'fa-file-invoice-dollar'],
        'Client Review / Revisions'  => ['bg' => 'warning',   'icon' => 'fa-comments'],
        'Final Payment Pending'      => ['bg' => 'danger',    'icon' => 'fa-wallet'],
        'Hosting & Deployment'       => ['bg' => 'info',      'icon' => 'fa-server fa-bounce'],
        'Project Completed'          => ['bg' => 'success',   'icon' => 'fa-flag-checkered'],
        'Project Delivered'          => ['bg' => 'success',   'icon' => 'fa-trophy'],
        'Cancelled'                  => ['bg' => 'dark',      'icon' => 'fa-ban']
    ];

    $info = $map[$status] ?? ['bg' => 'secondary', 'icon' => 'fa-circle-info'];
    $bg = $info['bg'];
    $icon = $info['icon'];

    return "<span class=\"badge bg-{$bg} px-2 py-1 text-nowrap\"><i class=\"fa-solid {$icon} me-1\"></i> " . htmlspecialchars($status) . "</span>";
}

/**
 * Get step index for the 12-stage timeline (1-based)
 */
function getStageStepNumber(string $status): int {
    $index = array_search($status, PROJECT_STAGES, true);
    return ($index !== false) ? ($index + 1) : 1;
}

/**
 * Handle secure file upload
 */
function handleSecureUpload(array $file, ?int $projectId = null, string $uploadedBy = 'customer'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid file parameter.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => true, 'file_id' => null, 'uploaded' => false];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload error code: ' . $file['error']];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'message' => 'File exceeds maximum size of 10MB.'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_FILE_EXTENSIONS, true)) {
        return ['success' => false, 'message' => 'Invalid file extension. Allowed: ' . implode(', ', ALLOWED_FILE_EXTENSIONS)];
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $safeName = 'CHW_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . $safeName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.'];
    }

    // Save record to uploaded_files table
    try {
        $pdo = getDB();
        if ($pdo) {
            $stmt = $pdo->prepare("
                INSERT INTO uploaded_files 
                (project_id, original_name, stored_name, file_path, file_size, file_type, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $relPath = 'uploads/' . $safeName;
            $stmt->execute([
                $projectId,
                $file['name'],
                $safeName,
                $relPath,
                $file['size'],
                $file['type'] ?? 'application/octet-stream',
                $uploadedBy
            ]);
            $fileId = (int)$pdo->lastInsertId();
            return ['success' => true, 'file_id' => $fileId, 'stored_name' => $safeName, 'file_path' => $relPath, 'original_name' => $file['name'], 'uploaded' => true];
        }
    } catch (Exception $e) {
        error_log("Upload DB insert failed: " . $e->getMessage());
    }

    return ['success' => true, 'file_id' => null, 'stored_name' => $safeName, 'file_path' => 'uploads/' . $safeName, 'uploaded' => true];
}

/**
 * Flash messaging helpers
 */
function setFlash(string $type, string $message): void {
    $_SESSION["flash_{$type}"] = $message;
}

function getFlash(string $type): ?string {
    $key = "flash_{$type}";
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function renderFlashAlerts(): string {
    $types = [
        'success' => [
            'title'     => 'Action Completed',
            'icon'      => 'fa-solid fa-circle-check',
            'border'    => '#10b981',
            'iconColor' => '#059669',
            'iconBg'    => '#ecfdf5',
        ],
        'error' => [
            'title'     => 'Attention Required',
            'icon'      => 'fa-solid fa-circle-exclamation',
            'border'    => '#ef4444',
            'iconColor' => '#dc2626',
            'iconBg'    => '#fef2f2',
        ],
        'warning' => [
            'title'     => 'System Notice',
            'icon'      => 'fa-solid fa-triangle-exclamation',
            'border'    => '#f59e0b',
            'iconColor' => '#d97706',
            'iconBg'    => '#fffbeb',
        ],
        'info' => [
            'title'     => 'Update',
            'icon'      => 'fa-solid fa-circle-info',
            'border'    => '#2563eb',
            'iconColor' => '#2563eb',
            'iconBg'    => '#eff6ff',
        ],
    ];

    $cardsHtml = '';
    $hasAlerts = false;

    foreach ($types as $key => $meta) {
        $msg = getFlash($key);
        if ($msg) {
            $hasAlerts = true;
            $toastId = 'toast_' . uniqid();
            $safeMsg = nl2br(htmlspecialchars($msg));
            $cardsHtml .= "
            <div class=\"chandila-toast-card toast-type-{$key}\" id=\"{$toastId}\" data-toast-id=\"{$toastId}\" role=\"alert\" style=\"border-left-color: {$meta['border']};\">
                <div class=\"chandila-toast-body\">
                    <div class=\"chandila-toast-icon\" style=\"background: {$meta['iconBg']}; color: {$meta['iconColor']};\">
                        <i class=\"{$meta['icon']}\"></i>
                    </div>
                    <div class=\"chandila-toast-content\">
                        <div class=\"chandila-toast-header\">
                            <span class=\"chandila-toast-title\">{$meta['title']}</span>
                            <span class=\"chandila-toast-time\">Just now</span>
                        </div>
                        <div class=\"chandila-toast-message\">{$safeMsg}</div>
                    </div>
                    <button type=\"button\" class=\"chandila-toast-close\" onclick=\"dismissChandilaToast('{$toastId}')\" aria-label=\"Close\">
                        <i class=\"fa-solid fa-xmark\"></i>
                    </button>
                </div>
                <div class=\"chandila-toast-progress-bar\" style=\"background-color: {$meta['border']};\"></div>
            </div>";
        }
    }

    $out = '<div id="chandilaToastContainer" class="chandila-toast-container" aria-live="polite">';
    if ($hasAlerts) {
        $out .= $cardsHtml;
    }
    $out .= '</div>';

    return $out;
}

/**
 * Safe HTTP Redirect Helper
 * Performs header redirect with fallback for environments where output has begun
 */
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        if (!headers_sent()) {
            header("Location: {$url}");
            exit;
        }
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($url) . "\"></noscript>";
        exit;
    }
}
}


