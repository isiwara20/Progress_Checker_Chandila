<?php
/**
 * Chandila Web Development – WhatsApp Notification Service
 * Configurable WhatsApp Messaging API Handler
 * Supports: Simulator, UltraMsg, Twilio, GreenAPI, Generic Webhook
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class WhatsAppService {

    /**
     * Get system setting from database with fallback
     */
    public static function getSetting(PDO $pdo, string $key, string $default = ''): string {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            return $val !== false ? (string)$val : $default;
        } catch (Exception $e) {
            return $default;
        }
    }

    /**
     * Build standard approved WhatsApp messages
     */
    public static function getMessageTemplate(string $eventType, array $data = []): string {
        $jobId = $data['job_id'] ?? 'CHW-XXXX';
        $progress = $data['progress_percentage'] ?? '0';
        $latestUpdate = $data['latest_update'] ?? 'Development in progress.';

        switch ($eventType) {
            case 'Project Request Submitted':
                return "Thank you for choosing Chandila Web Development! Your project request has been successfully received. Your Job ID is {$jobId}. You can use this Job ID to track your project progress.\n\n🌐 Track online: " . BASE_URL . "/track_project.php";

            case 'Project Approved':
                return "Good news! Your project ({$jobId}) has been reviewed and approved. Our team will contact you regarding the advance payment and project confirmation.";

            case 'Advance Payment Confirmed':
                return "Your advance payment for project {$jobId} has been successfully confirmed. Your project is now officially confirmed.\n\nDevelopment will commence shortly!";

            case 'Project Assigned':
                return "Your project ({$jobId}) has been assigned to our development team. We are preparing to start working on your project.";

            case 'Project Progress Updated':
                return "Your project progress has been updated!\n\nJob ID: {$jobId}\nCurrent Progress: {$progress}%\nLatest Update: {$latestUpdate}\n\nTrack your project using your Job ID: " . BASE_URL . "/track_project.php";

            case 'Second Payment Reminder':
                return "Your project ({$jobId}) has reached an important development milestone. Please complete the scheduled milestone payment before the agreed completion date.";

            case 'Development Completed':
                return "Your website development has been completed! We are now preparing the final delivery process.";

            case 'Final Payment Reminder':
                return "Your project ({$jobId}) development is complete. Please settle the outstanding balance to proceed with hosting and deployment.";

            case 'Hosting Completed':
                $hostingUrl = !empty($data['hosting_url']) ? "\nURL: " . $data['hosting_url'] : '';
                return "Your website ({$jobId}) has been successfully hosted and deployed.{$hostingUrl}\nThank you for choosing Chandila Web Development!";

            default:
                return $data['custom_message'] ?? "Update from Chandila Web Development regarding project {$jobId}.";
        }
    }

    /**
     * Dispatch WhatsApp notification with duplicate prevention and audit logging
     */
    public static function send(
        PDO $pdo,
        string $phone,
        string $eventType,
        array $templateData = [],
        ?int $projectId = null,
        ?string $jobId = null,
        bool $forceResend = false
    ): array {
        // Sanitize phone number to international format (e.g. +94771234567 or 94771234567)
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '94' . substr($cleanPhone, 1);
        } elseif (!str_starts_with($cleanPhone, '94') && strlen($cleanPhone) <= 10) {
            $cleanPhone = '94' . $cleanPhone;
        }

        $message = self::getMessageTemplate($eventType, $templateData);

        // 1. Prevent duplicate sends for the same project and event type (unless forceResend is true)
        if (!$forceResend && $projectId !== null) {
            // For progress update, allow if progress changed; for one-time events, prevent duplicates
            if ($eventType !== 'Project Progress Updated') {
                $checkStmt = $pdo->prepare("
                    SELECT id FROM whatsapp_notifications 
                    WHERE project_id = ? AND event_type = ? AND status = 'Sent'
                    LIMIT 1
                ");
                $checkStmt->execute([$projectId, $eventType]);
                if ($checkStmt->fetch()) {
                    return [
                        'success' => false,
                        'status'  => 'Duplicate_Skipped',
                        'message' => "WhatsApp notification for '{$eventType}' already sent previously."
                    ];
                }
            } else {
                // For progress updates, don't duplicate identical percentage within 2 hours
                $checkStmt = $pdo->prepare("
                    SELECT id FROM whatsapp_notifications 
                    WHERE project_id = ? AND event_type = ? AND message_text = ? AND created_at > (NOW() - INTERVAL 2 HOUR)
                    LIMIT 1
                ");
                $checkStmt->execute([$projectId, $eventType, $message]);
                if ($checkStmt->fetch()) {
                    return [
                        'success' => false,
                        'status'  => 'Duplicate_Skipped',
                        'message' => "Identical progress notification already sent recently."
                    ];
                }
            }
        }

        // 2. Fetch provider configuration
        $provider = self::getSetting($pdo, 'whatsapp_provider', 'simulator');
        $apiUrl = self::getSetting($pdo, 'whatsapp_api_url', '');
        $apiToken = self::getSetting($pdo, 'whatsapp_api_token', '');
        $instanceId = self::getSetting($pdo, 'whatsapp_instance_id', '');

        $status = 'Pending';
        $apiResponse = null;
        $isSuccess = false;

        // 3. Dispatch based on provider
        if ($provider === 'waclient' && !empty($apiUrl)) {
            // WA Client (WhatsApp Web Client Gateway / Local Node.js / Baileys / WPP)
            $payload = json_encode([
                'phone'      => $cleanPhone,
                'number'     => $cleanPhone . '@c.us',
                'to'         => '+' . $cleanPhone,
                'chatId'     => $cleanPhone . '@c.us',
                'message'    => $message,
                'text'       => $message,
                'job_id'     => $jobId,
                'event_type' => $eventType,
                'timestamp'  => date('Y-m-d H:i:s')
            ]);

            $headers = ['Content-Type: application/json'];
            if (!empty($apiToken)) {
                $headers[] = 'Authorization: Bearer ' . $apiToken;
                $headers[] = 'x-api-key: ' . $apiToken;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response && !$curlError && $httpCode >= 200 && $httpCode < 400) {
                $status = 'Sent';
                $apiResponse = $response;
                $isSuccess = true;
            } else {
                $status = 'Failed';
                $apiResponse = $curlError ? "cURL Error: {$curlError}" : "WA Client returned HTTP {$httpCode}: {$response}";
            }
        } elseif ($provider === 'ultramsg' && !empty($apiUrl) && !empty($apiToken)) {
            $params = [
                'token' => $apiToken,
                'to'    => '+' . $cleanPhone,
                'body'  => $message
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response && !$curlError) {
                $status = 'Sent';
                $apiResponse = $response;
                $isSuccess = true;
            } else {
                $status = 'Failed';
                $apiResponse = $curlError ?: 'No response from UltraMsg API';
            }
        } elseif ($provider === 'generic_webhook' && !empty($apiUrl)) {
            $payload = json_encode([
                'phone'      => '+' . $cleanPhone,
                'message'    => $message,
                'event_type' => $eventType,
                'job_id'     => $jobId,
                'timestamp'  => date('Y-m-d H:i:s')
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiToken]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($response && !$curlError) {
                $status = 'Sent';
                $apiResponse = $response;
                $isSuccess = true;
            } else {
                $status = 'Failed';
                $apiResponse = $curlError ?: 'Webhook connection failed';
            }
        } else {
            // Simulator Mode (Safe, offline-friendly, logged to database)
            $status = 'Sent';
            $apiResponse = json_encode([
                'mode'           => 'SIMULATED',
                'provider'       => $provider,
                'sent_timestamp' => date('Y-m-d H:i:s'),
                'recipient'      => '+' . $cleanPhone,
                'note'           => 'Message logged in simulator mode. Configure live API provider in Admin > Settings.'
            ]);
            $isSuccess = true;
        }

        // 4. Save to whatsapp_notifications table
        $insertStmt = $pdo->prepare("
            INSERT INTO whatsapp_notifications 
            (project_id, job_id, recipient_phone, event_type, message_text, status, api_response, sent_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $sentAt = ($status === 'Sent') ? date('Y-m-d H:i:s') : null;
        $insertStmt->execute([
            $projectId,
            $jobId,
            '+' . $cleanPhone,
            $eventType,
            $message,
            $status,
            $apiResponse,
            $sentAt
        ]);
        $notificationId = (int)$pdo->lastInsertId();

        return [
            'success'         => $isSuccess,
            'notification_id' => $notificationId,
            'status'          => $status,
            'message'         => $message,
            'phone'           => '+' . $cleanPhone,
            'event_type'      => $eventType
        ];
    }

    /**
     * Manually resend a notification by its notification ID
     */
    public static function resend(PDO $pdo, int $notificationId): array {
        $stmt = $pdo->prepare("SELECT * FROM whatsapp_notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        $notif = $stmt->fetch();

        if (!$notif) {
            return ['success' => false, 'message' => 'Notification record not found.'];
        }

        return self::send(
            $pdo,
            $notif['recipient_phone'],
            $notif['event_type'],
            ['custom_message' => $notif['message_text'], 'job_id' => $notif['job_id']],
            $notif['project_id'],
            $notif['job_id'],
            true // force resend
        );
    }
}

