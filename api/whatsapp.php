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

if ($action === 'ping_provider') {
    if (!isLoggedIn() || getUserRole() !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin authorization required.']);
        exit;
    }

    $provider = WhatsAppService::getSetting($pdo, 'whatsapp_provider', 'simulator');
    $apiUrl = WhatsAppService::getSetting($pdo, 'whatsapp_api_url', '');

    if ($provider === 'simulator') {
        echo json_encode([
            'success'  => true,
            'provider' => 'simulator',
            'message'  => 'Interactive Simulator is active and operational in local offline mode.'
        ]);
        exit;
    }

    if (empty($apiUrl)) {
        echo json_encode(['success' => false, 'message' => 'No API endpoint URL configured.']);
        exit;
    }

    // Try status endpoint first if it looks like a WA Client send URL
    $statusUrl = preg_replace('/\/send(-message)?\/?$/i', '/status', $apiUrl);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $statusUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if (!$curlError && $httpCode >= 200 && $httpCode < 400) {
        $json = json_decode($response, true);
        $clientStatus = $json['status'] ?? 'online';
        $clientPhone = $json['phone'] ?? $json['number'] ?? '';
        $msg = "WA Client is ONLINE and reachable! (Status: {$clientStatus}" . ($clientPhone ? ", Phone: {$clientPhone}" : "") . ")";
        echo json_encode(['success' => true, 'message' => $msg, 'response' => $json ?: $response]);
        exit;
    }

    // Fallback: ping base apiUrl directly
    $ch2 = curl_init();
    curl_setopt($ch2, CURLOPT_URL, $apiUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
    $res2 = curl_exec($ch2);
    $code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    $err2 = curl_error($ch2);
    curl_close($ch2);

    if (!$err2 && $code2 > 0) {
        echo json_encode(['success' => true, 'message' => "Server responded with HTTP {$code2}.", 'response' => $res2]);
    } else {
        echo json_encode(['success' => false, 'message' => "Could not reach WA Client at {$apiUrl}. Make sure WA Client service is running." . ($curlError ? " Error: {$curlError}" : "")]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action.']);

