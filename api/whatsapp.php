<?php
/**
 * Chandila Web Development – WhatsApp API Endpoint
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/whatsapp_config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'resend') {
    // Only authenticated staff can resend notifications
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }

    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if (!$notificationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
        exit;
    }

    $result = WhatsAppService::resend($pdo, $notificationId);
    echo json_encode($result);
    exit;
}

if ($action === 'test_send') {
    // Admin only test send
    if (!isLoggedIn() || getUserRole() !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin authorization required.']);
        exit;
    }

    $phone = $_POST['phone'] ?? '';
    $message = $_POST['message'] ?? 'This is a test notification from Chandila Web Development.';

    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
        exit;
    }

    $result = WhatsAppService::send(
        $pdo,
        $phone,
        'Test Message',
        ['custom_message' => $message],
        null,
        'TEST-' . date('His'),
        true
    );

    echo json_encode($result);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);

