<?php
/**
 * Chandila Web Development – Email Notification Service
 * Multi-Channel Customer Notification System (Email & WhatsApp)
 * Supports: PHP mail() for cPanel hosting, Direct SMTP (TLS/SSL), and Simulator mode
 */

if (defined('CHANDILA_EMAIL_CONFIG_LOADED')) {
    return;
}
define('CHANDILA_EMAIL_CONFIG_LOADED', true);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

class EmailService {

    /**
     * Ensure email_notifications table exists in the database
     */
    public static function initTable(PDO $pdo): void {
        try {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `email_notifications` (
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `project_id` INT(11) DEFAULT NULL,
                  `job_id` VARCHAR(30) DEFAULT NULL,
                  `recipient_email` VARCHAR(150) NOT NULL,
                  `recipient_name` VARCHAR(120) DEFAULT NULL,
                  `subject` VARCHAR(255) NOT NULL,
                  `body_html` LONGTEXT NOT NULL,
                  `event_type` VARCHAR(100) NOT NULL,
                  `status` ENUM('Pending', 'Sent', 'Failed') NOT NULL DEFAULT 'Pending',
                  `mail_response` TEXT DEFAULT NULL,
                  `sent_at` DATETIME DEFAULT NULL,
                  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  INDEX `idx_email_project` (`project_id`),
                  INDEX `idx_email_job_id` (`job_id`),
                  INDEX `idx_email_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        } catch (Exception $e) {
            // Silently proceed if table already exists or permissions restricted
        }
    }

    /**
     * Retrieve system setting from database with fallback
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
     * Build contextual email subject tailored to the notification event
     */
    public static function getEmailSubject(string $eventType, array $data = []): string {
        $jobId = $data['job_id'] ?? 'Project';
        switch ($eventType) {
            case 'Project Request Submitted':
                return "Project Request Received [{$jobId}] – Chandila Web Development";

            case 'Project Approved':
                return "Good News! Project Approved [{$jobId}] – Chandila Web Development";

            case 'Advance Payment Confirmed':
                return "Advance Payment Confirmed & Project Started [{$jobId}]";

            case 'Second Payment Confirmed':
                return "50% Milestone Payment Received & Official Receipt [{$jobId}] – Chandila Web Development";

            case 'Final Payment Confirmed':
                return "🏆 Project Fully Settled & Official PAID Invoice Ready [{$jobId}] – Chandila Web Development";

            case 'Project Assigned':
                return "Development Team Assigned [{$jobId}] – Chandila Web Development";

            case 'Project Progress Updated':
                $pct = $data['progress_percentage'] ?? '0';
                return "Project Progress Update ({$pct}%) [{$jobId}]";

            case 'Second Payment Reminder':
                return "Milestone Payment Invoice (50% Development Reached) [{$jobId}] – Chandila Web Development";

            case 'Development Completed':
                return "Development Completed & Staging Ready [{$jobId}]";

            case 'Final Payment Reminder':
                return "Final Settlement & Deployment Notice [{$jobId}]";

            case 'Hosting Completed':
                return "🎉 Website Live & Hosted Successfully [{$jobId}]";

            default:
                return $data['custom_subject'] ?? "Project Notification [{$jobId}] – Chandila Web Development";
        }
    }

    /**
     * Generate responsive branded HTML email body
     */
    public static function getEmailBody(string $eventType, array $data = []): string {
        $jobId        = htmlspecialchars($data['job_id'] ?? 'CHW-XXXX');
        $customerName = htmlspecialchars($data['customer_name'] ?? 'Valued Customer');
        $progress     = (int)($data['progress_percentage'] ?? 0);
        $latestUpdate = htmlspecialchars($data['latest_update'] ?? 'Development in progress.');
        $projectTitle = htmlspecialchars($data['project_title'] ?? 'Custom Web Application');
        $trackUrl     = BASE_URL . '/track_project.php?job_id=' . urlencode($data['job_id'] ?? '');

        // Determine badge and custom copy based on event type
        $badgeText  = 'Notification';
        $badgeBg    = '#2563eb';
        $headline   = 'Project Notification';
        $paragraphs = '';

        switch ($eventType) {
            case 'Project Request Submitted':
                $badgeText  = 'Request Received';
                $badgeBg    = '#059669';
                $headline   = 'Your Project Request Has Been Received!';
                $paragraphs = "
                    <p>Thank you for choosing <strong>Chandila Web Development</strong> for your digital vision. We have received your project requirements and our team has commenced initial architectural review.</p>
                    <p>Your unique Job ID is <strong style='color: #2563eb;'>{$jobId}</strong>. You can use this ID at any time to monitor your project milestones, team activity, and live status.</p>
                ";
                break;

            case 'Project Approved':
                $badgeText  = 'Project Approved';
                $badgeBg    = '#10b981';
                $headline   = 'Congratulations! Your Project is Approved';
                $paragraphs = "
                    <p>Our engineering team has completed reviewing your project scope and specifications. Your project has been officially <strong>Approved</strong>.</p>
                    <p>Our project management executive will reach out to coordinate your advance payment schedule to initiate development.</p>
                ";
                break;

            case 'Advance Payment Confirmed':
                $badgeText  = 'Advance Verified';
                $badgeBg    = '#059669';
                $headline   = 'Advance Payment Verified & Project Confirmed';
                $invLink    = BASE_URL . "/invoice.php?job_id={$jobId}&stage=advance";
                $paragraphs = "
                    <p>We are pleased to inform you that your initial advance milestone payment for <strong>{$jobId}</strong> has been received and verified.</p>
                    <p>Your project is now in our active sprint queue and development has officially started!</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='{$invLink}' target='_blank' style='display: inline-block; background: #059669; color: #ffffff; padding: 10px 22px; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 13.5px;'>
                            📄 View Advance Payment Receipt &amp; Invoice &rarr;
                        </a>
                    </div>
                ";
                break;

            case 'Second Payment Confirmed':
                $badgeText  = '50% Milestone Settled';
                $badgeBg    = '#059669';
                $headline   = '50% Milestone Payment Confirmed & Credited';
                $invLink    = BASE_URL . "/invoice.php?job_id={$jobId}&stage=milestone2";
                $paragraphs = "
                    <p>We have successfully verified and credited your 50% milestone payment for project <strong>{$jobId}</strong>.</p>
                    <p>Our engineering team has progressed into the client review, revisions, and final module staging phase.</p>
                    <div style='text-align: center; margin: 20px 0;'>
                        <a href='{$invLink}' target='_blank' style='display: inline-block; background: #059669; color: #ffffff; padding: 10px 22px; font-weight: bold; text-decoration: none; border-radius: 6px; font-size: 13.5px;'>
                            📄 View 50% Milestone Receipt &amp; Statement &rarr;
                        </a>
                    </div>
                ";
                break;

            case 'Project Assigned':
                $badgeText  = 'Engineer Assigned';
                $badgeBg    = '#0284c7';
                $headline   = 'Technical Team Assigned to Your Project';
                $paragraphs = "
                    <p>Your project <strong>{$jobId}</strong> has been assigned to our dedicated developer team. We are actively structuring your database, UI components, and application logic.</p>
                ";
                break;

            case 'Project Progress Updated':
                $badgeText  = "Progress: {$progress}%";
                $badgeBg    = '#d97706';
                $headline   = "Project Progress Update ({$progress}%)";
                $paragraphs = "
                    <p>Our development team has logged a new milestone progress update for project <strong>{$jobId}</strong>.</p>
                    <div style='background: #f8fafc; border-left: 4px solid #2563eb; padding: 12px 16px; margin: 16px 0; border-radius: 4px;'>
                        <strong style='color: #0f172a; font-size: 14px;'>Latest Engineering Note:</strong>
                        <p style='margin: 4px 0 0 0; color: #475569; font-size: 14px;'>{$latestUpdate}</p>
                    </div>
                ";
                break;

            case 'Second Payment Reminder':
                $badgeText  = '50% Milestone Invoice';
                $badgeBg    = '#d97706';
                $headline   = 'Payment Invoice: 50% Development Milestone Reached';
                
                $cur = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : 'Rs.';
                $totalVal = isset($data['total_cost']) && (float)$data['total_cost'] > 0 ? $cur . ' ' . number_format((float)$data['total_cost'], 2) : 'Agreed Scope';
                $advancePaid = isset($data['total_paid']) && (float)$data['total_paid'] > 0 ? $cur . ' ' . number_format((float)$data['total_paid'], 2) : ($cur . ' ' . number_format((float)($data['advance_amount'] ?? 0), 2));
                
                $m2Amt = 0;
                if (!empty($data['milestone2_amount'])) {
                    $m2Amt = (float)$data['milestone2_amount'];
                } elseif (!empty($data['total_cost'])) {
                    $m2Amt = round(((float)$data['total_cost']) * 0.50, 2);
                }
                $milestoneDue = ($m2Amt > 0) ? ($cur . ' ' . number_format($m2Amt, 2)) : '50% Milestone Amount';

                $bankName    = htmlspecialchars($data['bank_name'] ?? 'Commercial Bank of Ceylon');
                $bankAccName = htmlspecialchars($data['bank_account_name'] ?? 'Chandila Web Development (Pvt) Ltd');
                $bankAccNum  = htmlspecialchars($data['bank_account_number'] ?? '800123456789');
                $bankBranch  = htmlspecialchars($data['bank_branch'] ?? 'Colombo 03 Branch');
                $noteText    = htmlspecialchars($data['latest_update'] ?? 'Core database schema, backend architecture, and primary UI modules successfully completed.');

                $paragraphs = "
                    <p>We are delighted to inform you that your project <strong>{$jobId}</strong> has successfully completed its core engineering sprint and reached the <strong>50% Development Milestone</strong>!</p>
                    
                    <!-- Milestone Progress Highlight -->
                    <div style='background: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #d97706; padding: 14px 18px; border-radius: 6px; margin: 18px 0;'>
                        <div style='margin-bottom: 6px;'>
                            <strong style='color: #92400e; font-size: 14px;'>Milestone Status: 50% Completed</strong>
                            <span style='background: #d97706; color: #ffffff; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700; margin-left: 8px;'>MIDWAY REVIEW</span>
                        </div>
                        <p style='margin: 0; color: #78350f; font-size: 13.5px;'>{$noteText}</p>
                    </div>

                    <!-- Itemized Milestone Invoice Box -->
                    <div style='background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 8px; overflow: hidden; margin: 22px 0;'>
                        <div style='background: #0f172a; color: #ffffff; padding: 10px 16px; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;'>
                            PAYMENT INVOICE STATEMENT
                        </div>
                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='border-collapse: collapse;'>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 10px 16px; font-size: 13.5px; color: #64748b;'>Project Item</td>
                                <td align='right' style='padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: #0f172a;'>{$projectTitle} ({$jobId})</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 10px 16px; font-size: 13.5px; color: #64748b;'>Total Project Value</td>
                                <td align='right' style='padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: #0f172a;'>{$totalVal}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 10px 16px; font-size: 13.5px; color: #64748b;'>Advance Payment (Credited)</td>
                                <td align='right' style='padding: 10px 16px; font-size: 13.5px; font-weight: 600; color: #059669;'>{$advancePaid} &#10003;</td>
                            </tr>
                            <tr style='background-color: #f8fafc;'>
                                <td style='padding: 14px 16px; font-size: 14px; font-weight: 700; color: #0f172a;'>50% Milestone Payment Due</td>
                                <td align='right' style='padding: 14px 16px; font-size: 18px; font-weight: 800; color: #d97706;'>{$milestoneDue}</td>
                            </tr>
                        </table>
                    </div>

                    <!-- Bank Account Details Card -->
                    <div style='background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 16px; margin: 20px 0;'>
                        <div style='font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;'>
                            &#127974; Direct Bank Deposit Instructions:
                        </div>
                        <table role='presentation' border='0' cellpadding='4' cellspacing='0' width='100%' style='font-size: 13px;'>
                            <tr>
                                <td style='color: #64748b; width: 35%;'>Bank Name:</td>
                                <td style='font-weight: 600; color: #0f172a;'>{$bankName}</td>
                            </tr>
                            <tr>
                                <td style='color: #64748b;'>Account Name:</td>
                                <td style='font-weight: 600; color: #0f172a;'>{$bankAccName}</td>
                            </tr>
                            <tr>
                                <td style='color: #64748b;'>Account Number:</td>
                                <td style='font-weight: 700; color: #2563eb; font-family: monospace; font-size: 14px;'>{$bankAccNum}</td>
                            </tr>
                            <tr>
                                <td style='color: #64748b;'>Branch:</td>
                                <td style='font-weight: 600; color: #0f172a;'>{$bankBranch}</td>
                            </tr>
                            <tr>
                                <td style='color: #64748b;'>Deposit Reference:</td>
                                <td style='font-weight: 700; color: #d97706; font-family: monospace;'>{$jobId}</td>
                            </tr>
                        </table>
                    </div>

                    <p style='color: #475569; font-size: 13.5px; margin-top: 14px;'>Once your payment is deposited, please upload your deposit slip through your live tracking portal or reply directly with a screenshot of the receipt for immediate verification.</p>
                ";
                break;

            case 'Development Completed':
                $badgeText  = 'Development Ready';
                $badgeBg    = '#10b981';
                $headline   = 'Core Development Successfully Completed!';
                $paragraphs = "
                    <p>All core development modules and QA checks for <strong>{$jobId}</strong> are now complete. We are staging your solution for client review and final testing.</p>
                ";
                break;

            case 'Final Payment Reminder':
                $badgeText  = 'Settlement Notice';
                $badgeBg    = '#f59e0b';
                $headline   = 'Final Payment & Deployment Clearance';
                $paragraphs = "
                    <p>Your web application for <strong>{$jobId}</strong> is complete and ready for production launch. Please settle the final remaining balance to initiate domain linking, SSL deployment, and live hosting.</p>
                ";
                break;

            case 'Final Payment Confirmed':
                $badgeText  = 'PAID IN FULL';
                $badgeBg    = '#059669';
                $headline   = 'Official Full Settlement Confirmed – PAID Invoice Ready!';
                $invLink    = BASE_URL . "/invoice.php?job_id={$jobId}&stage=final";
                $paragraphs = "
                    <p>Congratulations! Your final project payment for <strong>{$jobId}</strong> has been successfully verified and cleared.</p>
                    <p>Your account balance is now <strong>Rs. 0.00 (100% Settled)</strong>. Your official digital tax invoice has been stamped with the certified <strong>PAID IN FULL</strong> seal.</p>
                    <div style='text-align: center; margin: 22px 0;'>
                        <a href='{$invLink}' target='_blank' style='display: inline-block; background: #dc2626; color: #ffffff; padding: 12px 26px; font-weight: bold; text-decoration: none; border-radius: 8px; font-size: 14px; box-shadow: 0 4px 12px rgba(220,38,38,0.25);'>
                            🏆 View &amp; Download Official PAID Invoice (with Stamp) &rarr;
                        </a>
                    </div>
                    <p>We are now proceeding with production hosting deployment and secure handover of your administrative credentials.</p>
                ";
                break;

            case 'Hosting Completed':
                $hostingUrl = !empty($data['hosting_url']) ? htmlspecialchars($data['hosting_url']) : '';
                $badgeText  = 'Live & Deployed';
                $badgeBg    = '#059669';
                $headline   = '🎉 Your Website is Officially Live!';
                $paragraphs = "
                    <p>Great news! Your website <strong>{$jobId}</strong> has been successfully deployed to high-performance cloud hosting with full SSL encryption.</p>
                    " . (!empty($hostingUrl) ? "<p style='margin: 16px 0;'><a href='{$hostingUrl}' target='_blank' style='display: inline-block; background: #10b981; color: #ffffff; padding: 10px 20px; font-weight: bold; text-decoration: none; border-radius: 6px;'>🌐 Visit Your Website: {$hostingUrl}</a></p>" : "") . "
                    <p>Thank you for partnering with Chandila Web Development for your digital journey!</p>
                ";
                break;

            default:
                $customMsg  = nl2br(htmlspecialchars($data['custom_message'] ?? 'We have an update regarding your project.'));
                $headline   = $data['custom_headline'] ?? 'Project Communication Update';
                $paragraphs = "<p>{$customMsg}</p>";
                break;
        }

        // Complete branded HTML layout
        $logoUrl = BASE_URL . '/assets/images/logo.png';
        return "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>{$headline}</title>
</head>
<body style='margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; color: #0f172a; line-height: 1.6;'>
    <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f1f5f9; padding: 30px 15px;'>
        <tr>
            <td align='center'>
                <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='600' style='max-width: 600px; background-color: #ffffff; border-radius: 14px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;'>
                    <!-- Brand Top Header -->
                    <tr>
                        <td style='background-color: #ffffff; padding: 25px 35px 20px 35px; border-bottom: 3px solid #d97706; text-align: center;'>
                            <img src='{$logoUrl}' alt='Chandila Web Development' style='max-height: 48px; width: auto; display: inline-block; margin-bottom: 8px;'>
                            <div style='color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 600;'>Where Innovation Meets Growth</div>
                        </td>
                    </tr>

                    <!-- Main Body Content -->
                    <tr>
                        <td style='padding: 35px 35px 25px 35px;'>
                            <!-- Status Badge -->
                            <div style='margin-bottom: 15px;'>
                                <span style='display: inline-block; background-color: {$badgeBg}; color: #ffffff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase;'>
                                    {$badgeText}
                                </span>
                            </div>

                            <h2 style='margin: 0 0 15px 0; color: #0f172a; font-size: 22px; font-weight: 700;'>{$headline}</h2>
                            <p style='color: #334155; font-size: 15px; margin-bottom: 18px;'>Dear <strong>{$customerName}</strong>,</p>

                            <div style='color: #334155; font-size: 15px;'>
                                {$paragraphs}
                            </div>

                            <!-- Project Snapshot Card -->
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%' style='background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; margin: 25px 0 20px 0;'>
                                <tr>
                                    <td style='padding: 16px 20px;'>
                                        <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                            <tr>
                                                <td style='color: #64748b; font-size: 13px; padding-bottom: 6px;'>Job Tracking ID:</td>
                                                <td align='right' style='font-family: monospace; font-weight: 700; color: #2563eb; font-size: 15px; padding-bottom: 6px;'>{$jobId}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #64748b; font-size: 13px; padding-bottom: 6px;'>Project Title:</td>
                                                <td align='right' style='font-weight: 600; color: #0f172a; font-size: 13px; padding-bottom: 6px;'>{$projectTitle}</td>
                                            </tr>
                                            <tr>
                                                <td style='color: #64748b; font-size: 13px;'>Current Progress:</td>
                                                <td align='right' style='font-weight: 700; color: #d97706; font-size: 14px;'>{$progress}%</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Call to Action Button -->
                            <div style='text-align: center; margin: 30px 0 15px 0;'>
                                <a href='{$trackUrl}' target='_blank' style='display: inline-block; background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; text-decoration: none; padding: 14px 30px; font-size: 14px; font-weight: 700; border-radius: 8px; box-shadow: 0 4px 12px rgba(15,23,42,0.15);'>
                                    🔍 Track Project Progress Online &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Company Help & Multi-Channel Support -->
                    <tr>
                        <td style='background-color: #f8fafc; padding: 25px 35px; border-top: 1px solid #e2e8f0;'>
                            <div style='color: #0f172a; font-size: 13px; font-weight: 700; margin-bottom: 8px;'>Need Assistance? Connect Directly:</div>
                            <table role='presentation' border='0' cellpadding='0' cellspacing='0' width='100%'>
                                <tr>
                                    <td style='font-size: 12px; color: #475569;'>
                                        💬 <strong>WhatsApp:</strong> <a href='https://wa.me/9476843340' style='color: #059669; text-decoration: none; font-weight: 600;'>076 843 3340</a><br>
                                        📞 <strong>Official Phone:</strong> <a href='tel:0768433340' style='color: #2563eb; text-decoration: none;'>076 843 3340</a><br>
                                        ✉️ <strong>Support Email:</strong> <a href='mailto:contact@chandilaweb.com' style='color: #2563eb; text-decoration: none;'>contact@chandilaweb.com</a>
                                    </td>
                                    <td align='right' style='font-size: 11px; color: #94a3b8; vertical-align: top;'>
                                        Galle Road, Colombo 03<br>Sri Lanka
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Bottom Legal Disclaimer -->
                    <tr>
                        <td style='background-color: #0f172a; padding: 18px 35px; text-align: center; color: #94a3b8; font-size: 11px;'>
                            &copy; " . date('Y') . " <strong>Chandila Web Development</strong>. All rights reserved.<br>
                            This automated project progress notification was dispatched to <em>" . htmlspecialchars($data['recipient_email'] ?? '') . "</em>.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>";
    }

    /**
     * Dispatch email notification with logging and audit trail
     */
    public static function send(
        PDO $pdo,
        string $recipientEmail,
        string $eventType,
        array $templateData = [],
        ?int $projectId = null,
        ?string $jobId = null,
        bool $forceResend = false,
        ?string $recipientName = null
    ): array {
        self::initTable($pdo);

        $cleanEmail = trim($recipientEmail);
        if (empty($cleanEmail) || !filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'status'  => 'Invalid_Email',
                'message' => "Invalid recipient email address: '{$cleanEmail}'."
            ];
        }

        // Check if email notifications are globally enabled
        $enabled = self::getSetting($pdo, 'email_notifications_enabled', '1');
        if ($enabled === '0' || $enabled === 'false') {
            return [
                'success' => false,
                'status'  => 'Disabled',
                'message' => 'Email notifications are currently disabled in Admin > Settings.'
            ];
        }

        // Fetch customer name and project context if missing
        if ($projectId !== null && (empty($recipientName) || empty($templateData['project_title']))) {
            try {
                $stmtP = $pdo->prepare("SELECT p.job_id, p.project_title, p.progress_percentage, c.full_name, c.email FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
                $stmtP->execute([$projectId]);
                $pRow = $stmtP->fetch();
                if ($pRow) {
                    if (empty($recipientName)) $recipientName = $pRow['full_name'];
                    if (empty($templateData['project_title'])) $templateData['project_title'] = $pRow['project_title'];
                    if (!isset($templateData['progress_percentage'])) $templateData['progress_percentage'] = $pRow['progress_percentage'];
                    if (!$jobId) $jobId = $pRow['job_id'];
                }
            } catch (Exception $e) {}
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
                    $pStmt = $pdo->prepare("SELECT project_title, total_cost, advance_amount, milestone2_amount, final_amount, total_paid, balance_due FROM projects WHERE id = ?");
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

        $templateData['job_id']          = $jobId ?: ($templateData['job_id'] ?? 'CHW-XXXX');
        $templateData['customer_name']   = $recipientName ?: ($templateData['customer_name'] ?? 'Valued Customer');
        $templateData['recipient_email'] = $cleanEmail;

        $subject  = self::getEmailSubject($eventType, $templateData);
        $htmlBody = self::getEmailBody($eventType, $templateData);

        // 1. Duplicate send prevention
        if (!$forceResend && $projectId !== null && $eventType !== 'Project Progress Updated') {
            try {
                $chk = $pdo->prepare("SELECT id FROM email_notifications WHERE project_id = ? AND event_type = ? AND status = 'Sent' LIMIT 1");
                $chk->execute([$projectId, $eventType]);
                if ($chk->fetch()) {
                    return [
                        'success' => false,
                        'status'  => 'Duplicate_Skipped',
                        'message' => "Email notification for '{$eventType}' was already sent previously."
                    ];
                }
            } catch (Exception $e) {}
        }

        // 2. Fetch email provider configuration
        $provider    = self::getSetting($pdo, 'email_provider', 'mail');
        $fromName    = self::getSetting($pdo, 'email_sender_name', 'Chandila Web Development');
        $fromEmail   = self::getSetting($pdo, 'email_sender_email', 'contact@chandilaweb.com');
        $smtpHost    = self::getSetting($pdo, 'email_smtp_host', '');
        $smtpPort    = (int)self::getSetting($pdo, 'email_smtp_port', '587');
        $smtpUser    = self::getSetting($pdo, 'email_smtp_user', '');
        $smtpPass    = self::getSetting($pdo, 'email_smtp_pass', '');
        $smtpSecure  = self::getSetting($pdo, 'email_smtp_secure', 'tls');

        $status       = 'Pending';
        $mailResponse = null;
        $isSuccess    = false;

        // 3. Dispatch based on configured provider
        if ($provider === 'smtp' && !empty($smtpHost)) {
            // Direct Native SMTP Dispatch
            $smtpResult = self::sendViaSmtp(
                $smtpHost,
                $smtpPort,
                $smtpUser,
                $smtpPass,
                $smtpSecure,
                $fromEmail,
                $fromName,
                $cleanEmail,
                $recipientName,
                $subject,
                $htmlBody
            );

            if ($smtpResult['success']) {
                $status       = 'Sent';
                $isSuccess    = true;
                $mailResponse = $smtpResult['response'] ?? 'SMTP delivery confirmed.';
            } else {
                $status       = 'Failed';
                $mailResponse = $smtpResult['error'] ?? 'SMTP dispatch failed.';
            }
        } elseif ($provider === 'mail') {
            // Standard PHP mail() (Native for cPanel Hosting & Linux Apache servers)
            $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $headers = [
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . mb_encode_mimeheader($fromName) . ' <' . $fromEmail . '>',
                'Reply-To: ' . $fromEmail,
                'X-Mailer: Chandila-PMS-Notifier/1.0',
                'Date: ' . date('r')
            ];

            // Send via mail()
            $sent = @mail($cleanEmail, $encodedSubject, $htmlBody, implode("\r\n", $headers));
            if ($sent) {
                $status       = 'Sent';
                $isSuccess    = true;
                $mailResponse = 'Dispatched via native PHP mail() service.';
            } else {
                // If local XAMPP mail fails (e.g. no local sendmail binary), log helpful guidance
                $lastErr = error_get_last();
                $errDetail = !empty($lastErr['message']) ? $lastErr['message'] : 'Local mail transport unconfigured (standard on local XAMPP). Works automatically on cPanel live server.';
                $status       = 'Failed';
                $mailResponse = "mail() error: {$errDetail}";
            }
        } else {
            // Simulator Mode (Safe, offline-friendly, logged to database with preview capability)
            $status       = 'Sent';
            $isSuccess    = true;
            $mailResponse = json_encode([
                'mode'           => 'SIMULATED',
                'provider'       => 'simulator',
                'recipient'      => $cleanEmail,
                'subject'        => $subject,
                'sent_timestamp' => date('Y-m-d H:i:s'),
                'note'           => 'Logged in simulator mode. Configure live cPanel mail or SMTP in Admin > Settings.'
            ]);
        }

        // 4. Save to email_notifications audit table
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO email_notifications 
                (project_id, job_id, recipient_email, recipient_name, subject, body_html, event_type, status, mail_response, sent_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $sentAt = ($status === 'Sent') ? date('Y-m-d H:i:s') : null;
            $insertStmt->execute([
                $projectId,
                $jobId,
                $cleanEmail,
                $recipientName,
                $subject,
                $htmlBody,
                $eventType,
                $status,
                $mailResponse,
                $sentAt
            ]);
            $notificationId = (int)$pdo->lastInsertId();
        } catch (Exception $dbEx) {
            $notificationId = 0;
        }

        return [
            'success'         => $isSuccess,
            'notification_id' => $notificationId,
            'status'          => $status,
            'subject'         => $subject,
            'recipient'       => $cleanEmail,
            'event_type'      => $eventType,
            'response'        => $mailResponse
        ];
    }

    /**
     * Native Lightweight SMTP Dispatcher (TLS/SSL socket handshake)
     */
    private static function sendViaSmtp(
        string $host,
        int $port,
        string $user,
        string $pass,
        string $secure,
        string $fromEmail,
        string $fromName,
        string $toEmail,
        ?string $toName,
        string $subject,
        string $htmlBody
    ): array {
        $timeout = 15;
        $prefix  = ($secure === 'ssl') ? 'ssl://' : '';
        $socket  = @fsockopen($prefix . $host, $port, $errno, $errstr, $timeout);

        if (!$socket) {
            return ['success' => false, 'error' => "Cannot connect to SMTP host {$host}:{$port} ($errstr)"];
        }

        $read = function() use ($socket) {
            $res = '';
            while ($str = fgets($socket, 515)) {
                $res .= $str;
                if (substr($str, 3, 1) === ' ') break;
            }
            return $res;
        };

        $write = function(string $cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        $initRes = $read();

        // EHLO
        $write("EHLO " . (gethostname() ?: 'localhost'));
        $ehloRes = $read();

        // STARTTLS if configured
        if ($secure === 'tls') {
            $write("STARTTLS");
            $tlsRes = $read();
            if (substr($tlsRes, 0, 3) !== '220') {
                fclose($socket);
                return ['success' => false, 'error' => "STARTTLS failed: {$tlsRes}"];
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . (gethostname() ?: 'localhost'));
            $read();
        }

        // AUTH LOGIN if credentials provided
        if (!empty($user) && !empty($pass)) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $authRes = $read();
            if (substr($authRes, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'error' => "SMTP Authentication failed: {$authRes}"];
            }
        }

        // MAIL FROM
        $write("MAIL FROM: <{$fromEmail}>");
        $mailFromRes = $read();
        if (substr($mailFromRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "MAIL FROM rejected: {$mailFromRes}"];
        }

        // RCPT TO
        $write("RCPT TO: <{$toEmail}>");
        $rcptRes = $read();
        if (substr($rcptRes, 0, 3) !== '250') {
            fclose($socket);
            return ['success' => false, 'error' => "RCPT TO rejected: {$rcptRes}"];
        }

        // DATA
        $write("DATA");
        $dataRes = $read();
        if (substr($dataRes, 0, 3) !== '354') {
            fclose($socket);
            return ['success' => false, 'error' => "DATA rejected: {$dataRes}"];
        }

        // Build Payload
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . mb_encode_mimeheader($fromName) . " <{$fromEmail}>\r\n";
        $headers .= "To: " . ($toName ? mb_encode_mimeheader($toName) . " " : "") . "<{$toEmail}>\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "X-Mailer: Chandila-PMS-SMTP/1.0\r\n";

        $write($headers . "\r\n" . $htmlBody . "\r\n.");
        $finalRes = $read();

        $write("QUIT");
        fclose($socket);

        if (substr($finalRes, 0, 3) === '250') {
            return ['success' => true, 'response' => "SMTP delivery verified: " . trim($finalRes)];
        } else {
            return ['success' => false, 'error' => "SMTP message delivery failed: " . trim($finalRes)];
        }
    }

    /**
     * Manually resend an existing email notification by ID
     */
    public static function resend(PDO $pdo, int $notificationId): array {
        $stmt = $pdo->prepare("SELECT * FROM email_notifications WHERE id = ?");
        $stmt->execute([$notificationId]);
        $email = $stmt->fetch();

        if (!$email) {
            return ['success' => false, 'message' => 'Notification record not found.'];
        }

        return self::send(
            $pdo,
            $email['recipient_email'],
            $email['event_type'],
            ['custom_subject' => $email['subject'], 'job_id' => $email['job_id']],
            $email['project_id'],
            $email['job_id'],
            true,
            $email['recipient_name']
        );
    }
}

