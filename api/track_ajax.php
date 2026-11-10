<?php
/**
 * Chandila Web Development – Public Track Project AJAX Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = getDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection unavailable.']);
    exit;
}

$jobId = trim($_GET['job_id'] ?? $_POST['job_id'] ?? '');
$phone = trim($_GET['phone'] ?? $_POST['phone'] ?? '');

if (empty($jobId) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Both Job ID and registered phone number are required.']);
    exit;
}

$cleanPhoneInput = preg_replace('/[^0-9]/', '', $phone);
if (str_starts_with($cleanPhoneInput, '0')) {
    $cleanPhoneInput = substr($cleanPhoneInput, 1);
}

try {
    $stmt = $pdo->prepare("
        SELECT p.job_id, p.project_title, p.project_type, p.status, p.progress_percentage, 
               p.expected_completion_date, p.hosting_url, p.created_at,
               c.phone, c.whatsapp_number
        FROM projects p
        JOIN customers c ON p.customer_id = c.id
        WHERE p.job_id = ?
        LIMIT 1
    ");
    $stmt->execute([$jobId]);
    $project = $stmt->fetch();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Invalid Job ID.']);
        exit;
    }

    $cleanDbPhone = preg_replace('/[^0-9]/', '', $project['phone']);
    $cleanDbWa    = preg_replace('/[^0-9]/', '', $project['whatsapp_number']);

    $match = false;
    $inputTail = substr($cleanPhoneInput, -7);
    if (strlen($inputTail) >= 6) {
        if (str_contains($cleanDbPhone, $inputTail) || str_contains($cleanDbWa, $inputTail)) {
            $match = true;
        }
    }

    if (!$match) {
        echo json_encode(['success' => false, 'message' => 'Verification failed. Registered contact number does not match this Job ID.']);
        exit;
    }

    // Return non-sensitive tracking info
    echo json_encode([
        'success'                  => true,
        'job_id'                   => $project['job_id'],
        'project_title'            => $project['project_title'],
        'project_type'             => $project['project_type'],
        'status'                   => $project['status'],
        'stage_number'             => getStageStepNumber($project['status']),
        'progress_percentage'      => (int)$project['progress_percentage'],
        'expected_completion_date' => formatDateOnly($project['expected_completion_date']),
        'hosting_url'              => $project['hosting_url']
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error querying project.']);
}

