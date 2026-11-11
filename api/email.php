<?php
/**
 * Chandila Web Development – Email API Endpoint
 * Handles AJAX email operations: resend, test send, and HTML preview
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email_config.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDB();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// 1. Resend Email Notification (Staff only)
if ($action === 'resend') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
        exit;
    }

    $notificationId = (int)($_POST['notification_id'] ?? 0);
    if (!$notificationId) {
        echo json_encode(['success' => false, 'message' => 'Invalid notification ID.']);
        exit;
    }

    $result = EmailService::resend($pdo, $notificationId);
    echo json_encode($result);
    exit;
}

// 2. Test Send Email (Admin only)
if ($action === 'test_send') {
    if (!isLoggedIn() || getUserRole() !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin authorization required.']);
        exit;
    }

    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? 'Chandila Web Development Email Verification');
    $message = trim($_POST['message'] ?? 'This is a test notification verifying that your email integration is fully operational.');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'A valid recipient email address is required.']);
        exit;
    }

    $result = EmailService::send(
        $pdo,
        $email,
        'Test Notification',
        [
            'custom_subject'  => $subject,
            'custom_headline' => 'Email Integration Verified',
            'custom_message'  => $message,
            'job_id'          => 'CHW-TEST-' . date('Hi'),
            'project_title'   => 'Email Integration Test'
        ],
        null,
        'CHW-TEST',
        true,
        'System Administrator'
    );

    echo json_encode($result);
    exit;
}

// 3. Get Rendered HTML Preview (Staff only)
if ($action === 'get_html') {
    if (!isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
        exit;
    }

    $id = (int)($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM email_notifications WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        echo json_encode([
            'success'   => true,
            'subject'   => $row['subject'],
            'recipient' => $row['recipient_email'],
            'name'      => $row['recipient_name'],
            'sent_at'   => $row['sent_at'] ?: $row['created_at'],
            'status'    => $row['status'],
            'body_html' => $row['body_html']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Email record not found.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
exit;

