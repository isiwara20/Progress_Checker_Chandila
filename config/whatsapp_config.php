<?php
/**
 * Chandila Web Development – WhatsApp Notification Service
 * Configurable WhatsApp Messaging API Handler
 * Supports: Simulator, UltraMsg, Twilio, GreenAPI, Generic Webhook
 */

if (!defined('CHANDILA_WHATSAPP_CONFIG_LOADED')) {
    define('CHANDILA_WHATSAPP_CONFIG_LOADED', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/email_config.php';

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
                $invLink = BASE_URL . "/invoice.php?job_id={$jobId}&stage=advance";
                return "🎉 *Advance Payment Confirmed!* [{$jobId}]\n\nYour advance deposit for project {$jobId} has been successfully verified and credited. Development kickoff has commenced!\n\n📄 *Official Advance Receipt & Invoice:*\n{$invLink}\n\nTrack your live project milestones: " . BASE_URL . "/track_project.php";

            case 'Project Assigned':
                return "Your project ({$jobId}) has been assigned to our development team. We are preparing to start working on your project.";

            case 'Project Progress Updated':
                return "Your project progress has been updated!\n\nJob ID: {$jobId}\nCurrent Progress: {$progress}%\nLatest Update: {$latestUpdate}\n\nTrack your project using your Job ID: " . BASE_URL . "/track_project.php";

            case 'Second Payment Reminder':
                $custName = $data['customer_name'] ?? 'Valued Customer';
                $title = $data['project_title'] ?? 'Custom Project';
                $cur = function_exists('getCurrencySymbol') ? getCurrencySymbol() : (defined('APP_CURRENCY_SYMBOL') ? APP_CURRENCY_SYMBOL : 'Rs.');
                $totalCost = isset($data['total_cost']) && (float)$data['total_cost'] > 0 ? $cur . ' ' . number_format((float)$data['total_cost'], 2) : 'Agreed Scope';
                $totalPaid = isset($data['total_paid']) && (float)$data['total_paid'] > 0 ? $cur . ' ' . number_format((float)$data['total_paid'], 2) : ($cur . ' ' . number_format((float)($data['advance_amount'] ?? 0), 2));
                
                // 50% Milestone Due
                $m2Amt = 0;
                if (!empty($data['milestone2_amount'])) {
                    $m2Amt = (float)$data['milestone2_amount'];
                } elseif (!empty($data['total_cost'])) {
                    $m2Amt = round(((float)$data['total_cost']) * 0.50, 2);
                }
                $milestoneDue = ($m2Amt > 0) ? ($cur . ' ' . number_format($m2Amt, 2)) : '50% Milestone Amount';

                $bankName = $data['bank_name'] ?? 'Commercial Bank of Ceylon';
                $bankAccName = $data['bank_account_name'] ?? 'Chandila Web Development (Pvt) Ltd';
                $bankAccNum = $data['bank_account_number'] ?? '800123456789';
                $bankBranch = $data['bank_branch'] ?? 'Colombo 03 Branch';
                $trackLink = BASE_URL . "/track_project.php?job_id={$jobId}";

                return "🧾 *CHANDILA WEB DEVELOPMENT – PAYMENT INVOICE*\n" .
                       "━━━━━━━━━━━━━━━━━━━━\n" .
                       "Dear *{$custName}*,\n\n" .
                       "Your project *{$title}* (*{$jobId}*) has reached the *50% Development Milestone*! 🚀\n\n" .
                       "📊 *Milestone Progress:* 50% Completed\n" .
                       "📝 *Engineering Note:* " . ($data['latest_update'] ?? 'Core modules and architectural foundation completed.') . "\n\n" .
                       "💰 *PAYMENT INVOICE BREAKDOWN:*\n" .
                       "• Total Project Value: {$totalCost}\n" .
                       "• Advance Payment Paid: {$totalPaid}\n" .
                       "• *50% Milestone Amount Due: {$milestoneDue}*\n\n" .
                       "🏦 *BANK DEPOSIT DETAILS:*\n" .
                       "• Bank: *{$bankName}*\n" .
                       "• Account Name: *{$bankAccName}*\n" .
                       "• Account Number: *{$bankAccNum}*\n" .
                       "• Branch: *{$bankBranch}*\n" .
                       "• Deposit Reference: *{$jobId}*\n\n" .
                       "Please make the milestone deposit and upload your bank receipt or reply with the transaction slip.\n\n" .
                       "🔍 *Track Online & Upload Receipt:*\n{$trackLink}\n\n" .
                       "Thank you for choosing Chandila Web Development!";

            case 'Second Payment Confirmed':
                $invLink = BASE_URL . "/invoice.php?job_id={$jobId}&stage=milestone2";
                return "✅ *50% Milestone Payment Received!* [{$jobId}]\n\nWe have received and confirmed your 50% milestone payment for project {$jobId}. Thank you! Our development team is continuing into client review and final testing.\n\n📄 *Official 50% Milestone Receipt & Statement:*\n{$invLink}\n\nTrack your project: " . BASE_URL . "/track_project.php";

            case 'Development Completed':
                return "Your website development has been completed! We are now preparing the final delivery process.";

            case 'Final Payment Reminder':
                $invLink = BASE_URL . "/invoice.php?job_id={$jobId}&stage=final";
                return "Your project ({$jobId}) development is complete. Please settle the final remaining balance to proceed with production hosting and deployment.\n\n📄 *View Invoice:*\n{$invLink}";

            case 'Final Payment Confirmed':
                $invLink = BASE_URL . "/invoice.php?job_id={$jobId}&stage=final";
                return "🏆 *FULL PAYMENT CONFIRMED & PROJECT SETTLED!* [{$jobId}]\n\nYour final settlement has been verified and cleared. Your project balance is now Rs. 0.00 (Fully Settled).\n\n📄 *Your Official PAID IN FULL Invoice (with Certified Seal):*\n{$invLink}\n\nWe are preparing live production hosting, domain linking, and credentials handover. Thank you for partnering with Chandila Web Development!";

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

        // Auto-enrich template data for Second Payment Reminder (Milestone Invoice)
        if ($eventType === 'Second Payment Reminder') {
            if (empty($templateData['bank_name'])) {
                $templateData['bank_name'] = self::getSetting($pdo, 'bank_name', 'Commercial Bank of Ceylon');
                $templateData['bank_account_name'] = self::getSetting($pdo, 'bank_account_name', 'Chandila Web Development (Pvt) Ltd');
                $templateData['bank_account_number'] = self::getSetting($pdo, 'bank_account_number', '800123456789');
                $templateData['bank_branch'] = self::getSetting($pdo, 'bank_branch', 'Colombo 03 Branch');
            }
            if ($projectId !== null && (empty($templateData['total_cost']) || !isset($templateData['milestone2_amount']))) {
                try {
                    $pStmt = $pdo->prepare("
                        SELECT p.project_title, p.total_cost, p.advance_amount, p.milestone2_amount, p.final_amount, p.total_paid, p.balance_due,
                               c.full_name as customer_name, c.email as customer_email
                        FROM projects p
                        LEFT JOIN customers c ON p.customer_id = c.id
                        WHERE p.id = ? LIMIT 1
                    ");
                    $pStmt->execute([$projectId]);
                    $pRow = $pStmt->fetch();
                    if ($pRow) {
                        foreach ($pRow as $k => $v) {
                            if (!isset($templateData[$k]) || $templateData[$k] === '') {
                                $templateData[$k] = $v;
                            }
                        }
                    }
                } catch (Exception $e) {}
            }
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

        // 5. Multi-Channel Synchronization: Dispatch Customer Email Notification Along with WhatsApp
        try {
            $custEmail = $templateData['customer_email'] ?? null;
            $custName  = $templateData['customer_name'] ?? null;

            if (empty($custEmail) && $projectId !== null) {
                $stmtC = $pdo->prepare("
                    SELECT c.email, c.full_name, p.job_id, p.project_title, p.progress_percentage 
                    FROM projects p 
                    JOIN customers c ON p.customer_id = c.id 
                    WHERE p.id = ? LIMIT 1
                ");
                $stmtC->execute([$projectId]);
                $cRow = $stmtC->fetch();
                if ($cRow) {
                    $custEmail = $cRow['email'];
                    if (empty($custName)) $custName = $cRow['full_name'];
                    if (empty($templateData['project_title'])) $templateData['project_title'] = $cRow['project_title'];
                    if (!isset($templateData['progress_percentage'])) $templateData['progress_percentage'] = $cRow['progress_percentage'];
                    if (!$jobId) $jobId = $cRow['job_id'];
                }
            } elseif (empty($custEmail) && !empty($jobId)) {
                $stmtC = $pdo->prepare("
                    SELECT c.email, c.full_name, p.id as project_id, p.project_title, p.progress_percentage 
                    FROM projects p 
                    JOIN customers c ON p.customer_id = c.id 
                    WHERE p.job_id = ? LIMIT 1
                ");
                $stmtC->execute([$jobId]);
                $cRow = $stmtC->fetch();
                if ($cRow) {
                    $custEmail = $cRow['email'];
                    if (empty($custName)) $custName = $cRow['full_name'];
                    if (empty($templateData['project_title'])) $templateData['project_title'] = $cRow['project_title'];
                    if (!isset($templateData['progress_percentage'])) $templateData['progress_percentage'] = $cRow['progress_percentage'];
                    if (!$projectId) $projectId = (int)$cRow['project_id'];
                }
            }

            if (!empty($custEmail) && class_exists('EmailService')) {
                EmailService::send($pdo, $custEmail, $eventType, $templateData, $projectId, $jobId, $forceResend, $custName);
            }
        } catch (Exception $emailEx) {
            // Email dispatch failure should never interrupt main operational workflow
        }

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
}


