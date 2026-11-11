<?php
$adminTitle = 'System & Communications Settings';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';
require_once __DIR__ . '/../config/email_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Settings Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        // 1. WhatsApp Configuration
        if ($action === 'save_whatsapp_settings') {
            $provider = trim($_POST['whatsapp_provider'] ?? 'simulator');
            $apiUrl   = trim($_POST['whatsapp_api_url'] ?? '');
            $token    = trim($_POST['whatsapp_api_token'] ?? '');
            $instance = trim($_POST['whatsapp_instance_id'] ?? '');
            $sender   = trim($_POST['whatsapp_sender_number'] ?? '');

            $settingsMap = [
                'whatsapp_provider'      => $provider,
                'whatsapp_api_url'       => $apiUrl,
                'whatsapp_api_token'     => $token,
                'whatsapp_instance_id'   => $instance,
                'whatsapp_sender_number' => $sender
            ];

            foreach ($settingsMap as $k => $v) {
                $up = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at)
                    VALUES (?, ?, 'whatsapp', NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $up->execute([$k, $v]);
            }

            setFlash('success', 'WhatsApp API credentials saved successfully.');
            header("Location: " . BASE_URL . "/admin/settings.php");
            exit;
        }

        // 2. Email Notification Configuration
        if ($action === 'save_email_settings') {
            $provider  = trim($_POST['email_provider'] ?? 'mail');
            $fromName  = trim($_POST['email_sender_name'] ?? 'Chandila Web Development');
            $fromEmail = trim($_POST['email_sender_email'] ?? 'contact@chandilaweb.com');
            $enabled   = isset($_POST['email_notifications_enabled']) ? '1' : '0';
            $smtpHost  = trim($_POST['email_smtp_host'] ?? '');
            $smtpPort  = trim($_POST['email_smtp_port'] ?? '587');
            $smtpUser  = trim($_POST['email_smtp_user'] ?? '');
            $smtpPass  = trim($_POST['email_smtp_pass'] ?? '');
            $smtpSec   = trim($_POST['email_smtp_secure'] ?? 'tls');

            $emailSettingsMap = [
                'email_provider'              => $provider,
                'email_sender_name'           => $fromName,
                'email_sender_email'          => $fromEmail,
                'email_notifications_enabled' => $enabled,
                'email_smtp_host'             => $smtpHost,
                'email_smtp_port'             => $smtpPort,
                'email_smtp_user'             => $smtpUser,
                'email_smtp_pass'             => $smtpPass,
                'email_smtp_secure'           => $smtpSec
            ];

            foreach ($emailSettingsMap as $k => $v) {
                $up = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at)
                    VALUES (?, ?, 'email', NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $up->execute([$k, $v]);
            }

            setFlash('success', 'Email notification configuration saved successfully.');
            header("Location: " . BASE_URL . "/admin/settings.php");
            exit;
        }

        // 3. Company & Payment Settings
        if ($action === 'save_company_settings') {
            $compName   = trim($_POST['company_name'] ?? '');
            $compEmail  = trim($_POST['company_email'] ?? '');
            $compPhone  = trim($_POST['company_phone'] ?? '');
            $compWa     = trim($_POST['company_whatsapp'] ?? '');
            $compAddr   = trim($_POST['company_address'] ?? '');
            $bankName   = trim($_POST['bank_name'] ?? '');
            $bankAccName= trim($_POST['bank_account_name'] ?? '');
            $bankAccNum = trim($_POST['bank_account_number'] ?? '');
            $bankBranch = trim($_POST['bank_branch'] ?? '');

            $map = [
                'company_name'        => $compName,
                'company_email'       => $compEmail,
                'company_phone'       => $compPhone,
                'company_whatsapp'    => $compWa,
                'company_address'     => $compAddr,
                'bank_name'           => $bankName,
                'bank_account_name'   => $bankAccName,
                'bank_account_number' => $bankAccNum,
                'bank_branch'         => $bankBranch
            ];

            foreach ($map as $k => $v) {
                $up = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at)
                    VALUES (?, ?, 'general', NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $up->execute([$k, $v]);
            }

            setFlash('success', 'Company profile and payment account details updated.');
            header("Location: " . BASE_URL . "/admin/settings.php");
            exit;
        }

        // 4. Invoice Customization & Branding Settings
        if ($action === 'save_invoice_settings') {
            $invCompanyName   = trim($_POST['invoice_company_name'] ?? '');
            $invTagline       = trim($_POST['invoice_tagline'] ?? '');
            $invAddress       = trim($_POST['invoice_address'] ?? '');
            $invPhone         = trim($_POST['invoice_phone'] ?? '');
            $invEmail         = trim($_POST['invoice_email'] ?? '');
            $invTaxNumber     = trim($_POST['invoice_tax_number'] ?? '');
            $invPrefix        = trim($_POST['invoice_prefix'] ?? 'INV-');
            $invThemeColor    = trim($_POST['invoice_theme_color'] ?? '#2563eb');
            $invStampText     = trim($_POST['invoice_paid_stamp_text'] ?? 'PAID IN FULL');
            $invStampStyle    = trim($_POST['invoice_paid_stamp_style'] ?? 'classic_red');
            $invShowWatermark = isset($_POST['invoice_show_watermark']) ? '1' : '0';
            $invFooterNotes   = trim($_POST['invoice_footer_notes'] ?? '');

            $invMap = [
                'invoice_company_name'    => $invCompanyName,
                'invoice_tagline'         => $invTagline,
                'invoice_address'         => $invAddress,
                'invoice_phone'           => $invPhone,
                'invoice_email'           => $invEmail,
                'invoice_tax_number'      => $invTaxNumber,
                'invoice_prefix'          => $invPrefix,
                'invoice_theme_color'     => $invThemeColor,
                'invoice_paid_stamp_text' => $invStampText,
                'invoice_paid_stamp_style'=> $invStampStyle,
                'invoice_show_watermark'  => $invShowWatermark,
                'invoice_footer_notes'    => $invFooterNotes
            ];

            foreach ($invMap as $k => $v) {
                $up = $pdo->prepare("
                    INSERT INTO system_settings (setting_key, setting_value, setting_group, updated_at)
                    VALUES (?, ?, 'invoice', NOW())
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
                ");
                $up->execute([$k, $v]);
            }

            setFlash('success', 'Invoice template, branding, and PAID stamp settings saved successfully!');
            header("Location: " . BASE_URL . "/admin/settings.php#invoiceSettingsCard");
            exit;
        }

        // 5. Admin Password Change
        if ($action === 'change_password') {
            $currentPass = $_POST['current_password'] ?? '';
            $newPass     = $_POST['new_password'] ?? '';
            $confirmPass = $_POST['confirm_password'] ?? '';

            if (empty($currentPass) || empty($newPass)) {
                setFlash('error', 'Please fill in all password fields.');
            } elseif ($newPass !== $confirmPass) {
                setFlash('error', 'New passwords do not match.');
            } elseif (strlen($newPass) < 8) {
                setFlash('error', 'New password must be at least 8 characters long.');
            } else {
                $userStmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $userStmt->execute([$currentUser['id']]);
                $hash = $userStmt->fetchColumn();

                if (password_verify($currentPass, $hash)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $currentUser['id']]);
                    setFlash('success', 'Your password has been changed successfully!');
                } else {
                    setFlash('error', 'Current password is incorrect.');
                }
            }
            header("Location: " . BASE_URL . "/admin/settings.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch all settings
$settings = [];
$sQuery = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while ($r = $sQuery->fetch()) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-gear text-primary me-2"></i> System & Integration Settings</h3>
        <p class="text-secondary small mb-0">Configure WhatsApp API providers, company profile, and bank deposit accounts.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: WhatsApp API Configuration -->
    <div class="col-lg-6">
        <div class="admin-card mb-4">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title mb-0"><i class="fa-brands fa-whatsapp text-success me-2"></i> WhatsApp API Integration</h5>
                <span class="badge bg-success-subtle text-success border border-success-subtle">Configurable API</span>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="whatsappSettingsForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_whatsapp_settings">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Active Messaging Provider</label>
                        <select class="form-select form-select-chandila" name="whatsapp_provider" id="providerSelect">
                            <option value="waclient" <?php echo (($settings['whatsapp_provider'] ?? '') === 'waclient') ? 'selected' : ''; ?>>WA Client (WhatsApp Web Gateway - Free & Recommended)</option>
                            <option value="simulator" <?php echo (($settings['whatsapp_provider'] ?? '') === 'simulator') ? 'selected' : ''; ?>>Interactive Simulator (Local & Offline Mode)</option>
                            <option value="ultramsg" <?php echo (($settings['whatsapp_provider'] ?? '') === 'ultramsg') ? 'selected' : ''; ?>>UltraMsg Cloud API</option>
                            <option value="generic_webhook" <?php echo (($settings['whatsapp_provider'] ?? '') === 'generic_webhook') ? 'selected' : ''; ?>>Generic cURL Webhook Gateway</option>
                        </select>
                        <div id="providerHelpText" class="form-text text-secondary small mt-1">
                            Connect directly to your WhatsApp number using the self-hosted WA Client Gateway. No per-message fees!
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label text-dark small fw-semibold mb-0">API Endpoint URL</label>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" id="btnPingProvider" style="font-size: 0.75rem;" title="Test connectivity to configured endpoint">
                                <i class="fa-solid fa-signal me-1"></i> Ping Server
                            </button>
                        </div>
                        <input type="url" class="form-control form-control-chandila" name="whatsapp_api_url" id="whatsappApiUrl" value="<?php echo htmlspecialchars($settings['whatsapp_api_url'] ?? 'http://localhost:3000/send-message'); ?>" placeholder="http://localhost:3000/send-message">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">API Token / Secret Key (Optional for WA Client)</label>
                        <input type="password" class="form-control form-control-chandila" name="whatsapp_api_token" value="<?php echo htmlspecialchars($settings['whatsapp_api_token'] ?? ''); ?>" placeholder="•••••••• (Leave blank if running unsecured on localhost)">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Instance ID / Client Name</label>
                            <input type="text" class="form-control form-control-chandila" name="whatsapp_instance_id" value="<?php echo htmlspecialchars($settings['whatsapp_instance_id'] ?? 'wa-client-01'); ?>" placeholder="wa-client-01">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Sender Phone Number</label>
                            <input type="text" class="form-control form-control-chandila" name="whatsapp_sender_number" value="<?php echo htmlspecialchars($settings['whatsapp_sender_number'] ?? '+9476843340'); ?>" placeholder="+94 76 843 3340">
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-electric flex-grow-1">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save WhatsApp Configuration
                        </button>
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalTestWhatsApp" title="Send a live test WhatsApp message">
                            <i class="fa-brands fa-whatsapp me-1"></i> Test Send
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- WA Client Quick Start Guide Card -->
        <div class="admin-card mb-4" id="waClientGuideCard" style="border-left: 4px solid #10b981;">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h6 class="admin-card-title mb-0 text-success"><i class="fa-solid fa-circle-nodes me-2"></i> WA Client Setup & Quick Start</h6>
                <a href="http://localhost:3000" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Gateway UI
                </a>
            </div>
            <div class="admin-card-body small text-secondary">
                <p class="mb-2"><strong>WA Client</strong> runs directly on your machine or server. It pairs with your phone via QR code and dispatches WhatsApp messages without UltraMsg subscription costs.</p>
                <div class="p-2 rounded bg-light border font-monospace text-dark mb-2" style="font-size: 0.8rem;">
                    cd wa_client &amp;&amp; node server.js
                </div>
                <ul class="ps-3 mb-0">
                    <li>Gateway runs on: <code>http://localhost:3000</code></li>
                    <li>Message Endpoint: <code>POST http://localhost:3000/send-message</code></li>
                    <li>Windows: double-click <code>wa_client/run.bat</code> to start instantly.</li>
                </ul>
            </div>
        </div>

        <!-- Security & Admin Password -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-lock text-info"></i> Security & Password Management</h5>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Current Password</label>
                        <input type="password" class="form-control form-control-chandila" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">New Password</label>
                        <input type="password" class="form-control form-control-chandila" name="new_password" placeholder="At least 8 characters" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-dark small fw-semibold">Confirm New Password</label>
                        <input type="password" class="form-control form-control-chandila" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="fa-solid fa-key me-1"></i> Update Admin Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Company Profile & Bank Account Details -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-building text-info"></i> Company Profile & Bank Deposit Details</h5>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_company_settings">

                    <h6 class="text-info mb-3">Company Information</h6>
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Company Name</label>
                        <input type="text" class="form-control form-control-chandila" name="company_name" value="<?php echo htmlspecialchars($settings['company_name'] ?? 'Chandila Web Development'); ?>" required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Official Email</label>
                            <input type="email" class="form-control form-control-chandila" name="company_email" value="<?php echo htmlspecialchars($settings['company_email'] ?? 'contact@chandilaweb.com'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Official Phone / WhatsApp</label>
                            <input type="text" class="form-control form-control-chandila" name="company_phone" value="<?php echo htmlspecialchars($settings['company_phone'] ?? '076 843 3340'); ?>" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-dark small fw-semibold">Office Address</label>
                        <textarea class="form-control form-control-chandila" name="company_address" rows="2"><?php echo htmlspecialchars($settings['company_address'] ?? 'No. 45, Galle Road, Colombo 03, Sri Lanka'); ?></textarea>
                    </div>

                    <h6 class="text-info mb-3 pt-3 border-top border-secondary border-opacity-25">Customer Bank Deposit Details</h6>
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Bank Name</label>
                        <input type="text" class="form-control form-control-chandila" name="bank_name" value="<?php echo htmlspecialchars($settings['bank_name'] ?? 'Commercial Bank of Ceylon'); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Account Name</label>
                        <input type="text" class="form-control form-control-chandila" name="bank_account_name" value="<?php echo htmlspecialchars($settings['bank_account_name'] ?? 'Chandila Web Development (Pvt) Ltd'); ?>">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Account Number</label>
                            <input type="text" class="form-control form-control-chandila" name="bank_account_number" value="<?php echo htmlspecialchars($settings['bank_account_number'] ?? '800123456789'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Branch Name</label>
                            <input type="text" class="form-control form-control-chandila" name="bank_branch" value="<?php echo htmlspecialchars($settings['bank_branch'] ?? 'Colombo 03 Branch'); ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-electric w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Company & Bank Profile
                    </button>
                </form>
            </div>
        </div>

        <!-- Email Notification Settings Card -->
        <div class="admin-card mt-4 mb-4">
            <div class="admin-card-header d-flex justify-content-between align-items-center">
                <h5 class="admin-card-title mb-0"><i class="fa-solid fa-envelope text-primary me-2"></i> Email Notification Settings</h5>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Customer Email</span>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="emailSettingsForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_email_settings">

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="email_notifications_enabled" id="emailEnabledSwitch" <?php echo (($settings['email_notifications_enabled'] ?? '1') !== '0') ? 'checked' : ''; ?>>
                        <label class="form-check-label text-dark fw-semibold small" for="emailEnabledSwitch">
                            Enable Automated Customer Email Notifications
                        </label>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Email Transport Provider</label>
                        <select class="form-select form-select-chandila" name="email_provider" id="emailProviderSelect">
                            <option value="mail" <?php echo (($settings['email_provider'] ?? 'mail') === 'mail') ? 'selected' : ''; ?>>Native PHP mail() – Recommended for cPanel Hosting</option>
                            <option value="smtp" <?php echo (($settings['email_provider'] ?? '') === 'smtp') ? 'selected' : ''; ?>>Custom Authenticated SMTP Server (TLS/SSL)</option>
                            <option value="simulator" <?php echo (($settings['email_provider'] ?? '') === 'simulator') ? 'selected' : ''; ?>>Interactive Simulator (Local Offline Testing)</option>
                        </select>
                        <div id="emailProviderHelp" class="form-text text-secondary small mt-1">
                            Sends HTML emails directly using your hosting mail server. Ideal for cPanel and production domains.
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Sender From Name</label>
                            <input type="text" class="form-control form-control-chandila" name="email_sender_name" value="<?php echo htmlspecialchars($settings['email_sender_name'] ?? 'Chandila Web Development'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Sender From Email</label>
                            <input type="email" class="form-control form-control-chandila" name="email_sender_email" value="<?php echo htmlspecialchars($settings['email_sender_email'] ?? 'contact@chandilaweb.com'); ?>" required>
                        </div>
                    </div>

                    <!-- SMTP Fields Block -->
                    <div id="smtpFieldsBlock" class="<?php echo (($settings['email_provider'] ?? '') === 'smtp') ? '' : 'd-none'; ?> p-3 rounded bg-light border mb-3">
                        <h6 class="text-dark small fw-bold mb-2"><i class="fa-solid fa-server text-primary me-1"></i> SMTP Server Authentication</h6>
                        <div class="row g-2 mb-2">
                            <div class="col-md-8">
                                <label class="form-label text-dark small fw-semibold">SMTP Host</label>
                                <input type="text" class="form-control form-control-chandila" name="email_smtp_host" value="<?php echo htmlspecialchars($settings['email_smtp_host'] ?? ''); ?>" placeholder="e.g. mail.chandila.site or smtp.gmail.com">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-dark small fw-semibold">Port</label>
                                <input type="number" class="form-control form-control-chandila" name="email_smtp_port" value="<?php echo htmlspecialchars($settings['email_smtp_port'] ?? '587'); ?>">
                            </div>
                        </div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="form-label text-dark small fw-semibold">SMTP Username</label>
                                <input type="text" class="form-control form-control-chandila" name="email_smtp_user" value="<?php echo htmlspecialchars($settings['email_smtp_user'] ?? ''); ?>" placeholder="notifications@chandila.site">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-dark small fw-semibold">SMTP Password</label>
                                <input type="password" class="form-control form-control-chandila" name="email_smtp_pass" value="<?php echo htmlspecialchars($settings['email_smtp_pass'] ?? ''); ?>" placeholder="••••••••">
                            </div>
                        </div>

                        <div class="mb-1">
                            <label class="form-label text-dark small fw-semibold">Encryption Protocol</label>
                            <select class="form-select form-select-chandila" name="email_smtp_secure">
                                <option value="tls" <?php echo (($settings['email_smtp_secure'] ?? 'tls') === 'tls') ? 'selected' : ''; ?>>TLS (Port 587 - Recommended)</option>
                                <option value="ssl" <?php echo (($settings['email_smtp_secure'] ?? '') === 'ssl') ? 'selected' : ''; ?>>SSL (Port 465)</option>
                                <option value="none" <?php echo (($settings['email_smtp_secure'] ?? '') === 'none') ? 'selected' : ''; ?>>None (Plain Port 25)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Save Email Configuration
                        </button>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTestEmail" title="Send a test email notification">
                            <i class="fa-solid fa-paper-plane me-1"></i> Test Email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Full-Width Row: Invoice Appearance, Branding & PAID Stamp Settings -->
<div class="row g-4 mt-1 mb-4" id="invoiceSettingsCard">
    <div class="col-12">
        <div class="admin-card">
            <div class="admin-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="admin-card-title mb-0">
                        <i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i> Invoice Appearance, Branding &amp; PAID Stamp Settings
                    </h5>
                    <div class="text-secondary small mt-1">Customize client invoice design, milestone receipts, legal company information, and the official PAID stamp.</div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal" data-bs-target="#modalInvoicePreview">
                        <i class="fa-solid fa-eye me-1"></i> Live Invoice Preview
                    </button>
                    <a href="<?php echo BASE_URL; ?>/invoice.php?preview=1&stage=final" target="_blank" class="btn btn-light border btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open In New Tab
                    </a>
                </div>
            </div>
            <div class="admin-card-body p-4">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="invoiceSettingsForm">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_invoice_settings">

                    <!-- Section 1: Company Identity on Invoices -->
                    <h6 class="text-dark fw-bold mb-3 d-flex align-items-center">
                        <span class="badge bg-primary me-2">1</span> Company Branding &amp; Header Identity
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-dark small fw-semibold">Company Legal Name on Invoice</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_company_name" value="<?php echo htmlspecialchars($settings['invoice_company_name'] ?? ($settings['company_name'] ?? 'Chandila Web Development (Pvt) Ltd')); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-dark small fw-semibold">Tagline / Business Slogan</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_tagline" value="<?php echo htmlspecialchars($settings['invoice_tagline'] ?? 'Where Innovation Meets Growth'); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark small fw-semibold">Invoice No. Prefix</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_prefix" value="<?php echo htmlspecialchars($settings['invoice_prefix'] ?? 'INV-'); ?>" placeholder="INV-">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark small fw-semibold">Business Reg / Tax No</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_tax_number" value="<?php echo htmlspecialchars($settings['invoice_tax_number'] ?? 'PV-00284729'); ?>" placeholder="PV-00284729">
                        </div>
                    </div>

                    <!-- Section 2: Contact Information on Invoices -->
                    <h6 class="text-dark fw-bold mb-3 d-flex align-items-center">
                        <span class="badge bg-primary me-2">2</span> Contact &amp; Physical Coordinates
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Official Office Address</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_address" value="<?php echo htmlspecialchars($settings['invoice_address'] ?? ($settings['company_address'] ?? 'No. 45, Galle Road, Colombo 03, Sri Lanka')); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-dark small fw-semibold">Billing Phone Number</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_phone" value="<?php echo htmlspecialchars($settings['invoice_phone'] ?? ($settings['company_phone'] ?? '076 843 3340')); ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-dark small fw-semibold">Billing Support Email</label>
                            <input type="email" class="form-control form-control-chandila" name="invoice_email" value="<?php echo htmlspecialchars($settings['invoice_email'] ?? ($settings['company_email'] ?? 'billing@chandilaweb.com')); ?>" required>
                        </div>
                    </div>

                    <!-- Section 3: Visual Theme & The Official PAID Seal / Stamp -->
                    <h6 class="text-dark fw-bold mb-3 d-flex align-items-center">
                        <span class="badge bg-warning text-dark me-2">3</span> Theme Styling &amp; Official Final Payment PAID Stamp
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label text-dark small fw-semibold">Invoice Theme Accent Color</label>
                            <div class="input-group mb-2">
                                <input type="color" class="form-control form-control-color" id="invoiceThemeColorInput" name="invoice_theme_color" value="<?php echo htmlspecialchars($settings['invoice_theme_color'] ?? '#2563eb'); ?>" title="Choose invoice color">
                                <input type="text" class="form-control font-monospace" id="invoiceThemeColorText" value="<?php echo htmlspecialchars($settings['invoice_theme_color'] ?? '#2563eb'); ?>" readonly>
                            </div>
                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#2563eb" style="width: 24px; height: 24px; background: #2563eb;" title="Electric Blue"></button>
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#d97706" style="width: 24px; height: 24px; background: #d97706;" title="Golden Amber"></button>
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#0f172a" style="width: 24px; height: 24px; background: #0f172a;" title="Midnight Navy"></button>
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#059669" style="width: 24px; height: 24px; background: #059669;" title="Emerald Green"></button>
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#dc2626" style="width: 24px; height: 24px; background: #dc2626;" title="Crimson Red"></button>
                                <button type="button" class="btn btn-sm p-1 rounded-circle border btn-color-swatch" data-color="#7c3aed" style="width: 24px; height: 24px; background: #7c3aed;" title="Royal Purple"></button>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-dark small fw-semibold">Final Payment "PAID" Stamp Style</label>
                            <select class="form-select form-select-chandila" name="invoice_paid_stamp_style" id="invoiceStampStyleSelect">
                                <option value="classic_red" <?php echo (($settings['invoice_paid_stamp_style'] ?? 'classic_red') === 'classic_red') ? 'selected' : ''; ?>>
                                    Crimson Ink Rubber Stamp (Classic Angled -13°)
                                </option>
                                <option value="emerald_badge" <?php echo (($settings['invoice_paid_stamp_style'] ?? '') === 'emerald_badge') ? 'selected' : ''; ?>>
                                    Emerald Green Corporate Verification Seal
                                </option>
                                <option value="electric_gold" <?php echo (($settings['invoice_paid_stamp_style'] ?? '') === 'electric_gold') ? 'selected' : ''; ?>>
                                    Electric Gold Wax Premium Stamp
                                </option>
                                <option value="royal_navy" <?php echo (($settings['invoice_paid_stamp_style'] ?? '') === 'royal_navy') ? 'selected' : ''; ?>>
                                    Royal Navy Enterprise Stamp
                                </option>
                            </select>
                            <div class="form-text text-secondary small">Appears automatically when the final balance reaches Rs. 0.00.</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-dark small fw-semibold">PAID Stamp Text</label>
                            <input type="text" class="form-control form-control-chandila" name="invoice_paid_stamp_text" value="<?php echo htmlspecialchars($settings['invoice_paid_stamp_text'] ?? 'PAID IN FULL'); ?>" placeholder="PAID IN FULL">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="invoice_show_watermark" id="invoiceWatermarkCheck" <?php echo (($settings['invoice_show_watermark'] ?? '1') !== '0') ? 'checked' : ''; ?>>
                                <label class="form-check-label text-dark small fw-semibold" for="invoiceWatermarkCheck">
                                    Display diagonal "PAID" background watermark on settled invoices
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Invoice Footer Policies & Terms -->
                    <h6 class="text-dark fw-bold mb-3 d-flex align-items-center">
                        <span class="badge bg-secondary me-2">4</span> Invoice Footer Terms &amp; Accounting Policies
                    </h6>
                    <div class="mb-4">
                        <label class="form-label text-dark small fw-semibold">Payment Terms &amp; Legal Policy Notice</label>
                        <textarea class="form-control form-control-chandila" name="invoice_footer_notes" rows="3"><?php echo htmlspecialchars($settings['invoice_footer_notes'] ?? 'Thank you for choosing Chandila Web Development. All software code, database structures, and production access rights will be permanently transferred upon complete project settlement. Please retain this official invoice for your accounting records.'); ?></textarea>
                        <div class="form-text text-secondary small">Printed at the bottom of customer invoices and receipts.</div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3 border-top">
                        <div class="text-secondary small">
                            <i class="fa-solid fa-circle-check text-success me-1"></i> Changes apply immediately to all client invoices and download links.
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalInvoicePreview">
                                <i class="fa-solid fa-eye me-1"></i> Live Invoice Preview
                            </button>
                            <button type="submit" class="btn btn-electric px-4 fw-bold">
                                <i class="fa-solid fa-floppy-disk me-1"></i> Save Invoice &amp; PAID Stamp Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Test Email Notification -->
<div class="modal fade" id="modalTestEmail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-envelope text-primary me-2"></i> Send Live Test Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-dark small fw-semibold">Recipient Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-chandila" id="testEmailAddress" placeholder="you@domain.com" value="<?php echo htmlspecialchars($currentUser['email'] ?? 'admin@chandilaweb.com'); ?>" required>
                    <div class="form-text text-secondary small">A formatted test email will be sent using your active email provider.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark small fw-semibold">Subject Line</label>
                    <input type="text" class="form-control form-control-chandila" id="testEmailSubject" value="Chandila Web Development – Email Delivery Test">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark small fw-semibold">Message Body</label>
                    <textarea class="form-control form-control-chandila" id="testEmailMessage" rows="3">This is an automated test verifying that the Chandila customer notification mailer is active and operating properly.</textarea>
                </div>
            </div>
            <div class="modal-footer border-top">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary px-4" id="btnSendTestEmail">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Verification Email
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Test WhatsApp Message -->
<div class="modal fade" id="modalTestWhatsApp" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <div class="modal-header">
                <h5 class="modal-title text-dark fw-bold"><i class="fa-brands fa-whatsapp text-success me-2"></i> Send Test WhatsApp Message</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label text-dark small fw-semibold">Recipient WhatsApp Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-chandila" id="testPhone" placeholder="e.g. 0768433340 or +9476843340" value="<?php echo htmlspecialchars($settings['whatsapp_sender_number'] ?? '0768433340'); ?>" required>
                    <div class="form-text text-secondary small">Enter your phone number to receive a live verification ping.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark small fw-semibold">Test Message Text</label>
                    <textarea class="form-control form-control-chandila" id="testMessage" rows="3">🚀 Chandila Web Development: WhatsApp API connection verified successfully via active provider!</textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btnSendTestWhatsApp">
                    <i class="fa-solid fa-paper-plane me-1"></i> Send Verification Ping
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Live Interactive Invoice Preview -->
<div class="modal fade" id="modalInvoicePreview" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header bg-dark text-white p-3">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <h5 class="modal-title fw-bold mb-0">
                        <i class="fa-solid fa-file-invoice-dollar text-warning me-2"></i> Live Invoice Template Preview
                    </h5>
                    <div class="btn-group btn-group-sm" id="invoicePreviewStageTabs">
                        <button type="button" class="btn btn-light active" data-stage="final">
                            <i class="fa-solid fa-stamp text-danger me-1"></i> Final Paid (PAID Stamp)
                        </button>
                        <button type="button" class="btn btn-outline-light" data-stage="milestone2">
                            <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i> 50% Milestone
                        </button>
                        <button type="button" class="btn btn-outline-light" data-stage="advance">
                            <i class="fa-solid fa-receipt text-info me-1"></i> Advance Deposit
                        </button>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo BASE_URL; ?>/invoice.php?preview=1&stage=final" target="_blank" id="btnPreviewNewTab" class="btn btn-sm btn-outline-light" title="Open full printable view in new window">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Tab
                    </a>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="background: #0f172a;">
                <iframe id="invoicePreviewIframe" src="<?php echo BASE_URL; ?>/invoice.php?preview=1&stage=final" style="width: 100%; height: 680px; border: none; display: block;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('providerSelect');
    const apiUrlInput = document.getElementById('whatsappApiUrl');
    const helpText = document.getElementById('providerHelpText');
    const guideCard = document.getElementById('waClientGuideCard');
    const btnPing = document.getElementById('btnPingProvider');
    const btnSendTest = document.getElementById('btnSendTestWhatsApp');

    function updateProviderUI() {
        const val = providerSelect.value;
        if (val === 'waclient') {
            if (!apiUrlInput.value || apiUrlInput.value.includes('ultramsg')) {
                apiUrlInput.value = 'http://localhost:3000/send-message';
            }
            apiUrlInput.placeholder = 'http://localhost:3000/send-message';
            helpText.innerHTML = 'Connect directly to your personal or business WhatsApp number via the self-hosted WA Client Gateway. No subscription or per-message fees!';
            if (guideCard) guideCard.classList.remove('d-none');
        } else if (val === 'ultramsg') {
            if (!apiUrlInput.value || apiUrlInput.value.includes('localhost:3000')) {
                apiUrlInput.value = 'https://api.ultramsg.com/instance9999/messages/chat';
            }
            apiUrlInput.placeholder = 'https://api.ultramsg.com/instance9999/messages/chat';
            helpText.innerHTML = 'Dispatches messages via UltraMsg cloud instances. Requires paid UltraMsg instance ID and token.';
            if (guideCard) guideCard.classList.add('d-none');
        } else if (val === 'generic_webhook') {
            apiUrlInput.placeholder = 'https://your-custom-gateway.com/api/send';
            helpText.innerHTML = 'Forwards messages as JSON POST payload to your custom server or webhook endpoint.';
            if (guideCard) guideCard.classList.add('d-none');
        } else {
            helpText.innerHTML = 'Simulator mode logs all outgoing messages to database without calling external APIs. Ideal for offline demos.';
            if (guideCard) guideCard.classList.add('d-none');
        }
    }

    if (providerSelect) {
        providerSelect.addEventListener('change', updateProviderUI);
        updateProviderUI();
    }

    // Ping Provider Button
    if (btnPing) {
        btnPing.addEventListener('click', function() {
            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Pinging...';
            this.disabled = true;

            fetch((window.BASE_URL || '') + '/api/whatsapp.php?action=ping_provider')
                .then(r => r.json())
                .then(data => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                    if (data.success) {
                        showChandilaToast('success', data.message, 'WA Client Status: Online');
                    } else {
                        showChandilaToast('error', data.message, 'WA Client Status: Offline');
                    }
                })
                .catch(err => {
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                    showChandilaToast('error', 'Could not reach server: ' + err.message, 'Connection Error');
                });
        });
    }

    // Send Test Message Button in Modal
    if (btnSendTest) {
        btnSendTest.addEventListener('click', function() {
            const phone = document.getElementById('testPhone').value.trim();
            const message = document.getElementById('testMessage').value.trim();

            if (!phone) {
                showChandilaToast('warning', 'Please enter a recipient phone number.', 'Missing Field');
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';
            this.disabled = true;

            const fd = new FormData();
            fd.append('action', 'test_send');
            fd.append('phone', phone);
            fd.append('message', message);

            fetch((window.BASE_URL || '') + '/api/whatsapp.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                this.innerHTML = originalText;
                this.disabled = false;
                const modalEl = document.getElementById('modalTestWhatsApp');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                if (data.success) {
                    showChandilaToast('success', 'Test message sent to ' + phone + ' successfully!', 'WhatsApp Test Success');
                } else {
                    showChandilaToast('error', data.message || 'Failed to dispatch test message.', 'Dispatch Error');
                }
            })
            .catch(err => {
                this.innerHTML = originalText;
                this.disabled = false;
                showChandilaToast('error', 'Connection error: ' + err.message, 'Network Error');
            });
        });
    }

    // Email Provider Selection UI toggle
    const emailProviderSelect = document.getElementById('emailProviderSelect');
    const smtpFieldsBlock = document.getElementById('smtpFieldsBlock');
    const emailProviderHelp = document.getElementById('emailProviderHelp');

    if (emailProviderSelect && smtpFieldsBlock) {
        function updateEmailProviderUI() {
            const val = emailProviderSelect.value;
            if (val === 'smtp') {
                smtpFieldsBlock.classList.remove('d-none');
                emailProviderHelp.innerText = 'Delivers emails directly through your custom SMTP server with authenticated TLS/SSL.';
            } else if (val === 'mail') {
                smtpFieldsBlock.classList.add('d-none');
                emailProviderHelp.innerText = 'Recommended for cPanel hosting. Uses server native mail() with zero configuration.';
            } else {
                smtpFieldsBlock.classList.add('d-none');
                emailProviderHelp.innerText = 'Safe offline testing. Logs all customer emails to the database without sending real emails.';
            }
        }
        emailProviderSelect.addEventListener('change', updateEmailProviderUI);
        updateEmailProviderUI();
    }

    // Send Live Test Email AJAX
    const btnSendTestEmail = document.getElementById('btnSendTestEmail');
    if (btnSendTestEmail) {
        btnSendTestEmail.addEventListener('click', function() {
            const email = (document.getElementById('testEmailAddress').value || '').trim();
            const subject = (document.getElementById('testEmailSubject').value || '').trim();
            const message = (document.getElementById('testEmailMessage').value || '').trim();

            if (!email) {
                showChandilaToast('warning', 'Please enter a recipient email address.', 'Missing Email');
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Sending...';
            this.disabled = true;

            const fd = new FormData();
            fd.append('action', 'test_send');
            fd.append('email', email);
            fd.append('subject', subject);
            fd.append('message', message);

            fetch((window.BASE_URL || '') + '/api/email.php', {
                method: 'POST',
                body: fd
            })
            .then(r => r.json())
            .then(data => {
                this.innerHTML = originalText;
                this.disabled = false;
                const modalEl = document.getElementById('modalTestEmail');
                if (modalEl) {
                    const modal = bootstrap.Modal.getInstance(modalEl);
                    if (modal) modal.hide();
                }

                if (data.success) {
                    showChandilaToast('success', 'Verification email sent to ' + email + ' successfully!', 'Email Test Success');
                } else {
                    showChandilaToast('error', data.message || data.response || 'Failed to dispatch test email.', 'Dispatch Error');
                }
            })
            .catch(err => {
                this.innerHTML = originalText;
                this.disabled = false;
                showChandilaToast('error', 'Connection error: ' + err.message, 'Network Error');
            });
        });
    }

    // Invoice Theme Color Swatches & Inputs
    const colorSwatches = document.querySelectorAll('.btn-color-swatch');
    const colorInput = document.getElementById('invoiceThemeColorInput');
    const colorText = document.getElementById('invoiceThemeColorText');
    if (colorSwatches && colorInput && colorText) {
        colorSwatches.forEach(btn => {
            btn.addEventListener('click', function() {
                const c = this.getAttribute('data-color');
                colorInput.value = c;
                colorText.value = c;
            });
        });
        colorInput.addEventListener('input', function() {
            colorText.value = this.value;
        });
    }

    // Invoice Preview Stage Switcher Tabs
    const stageTabs = document.querySelectorAll('#invoicePreviewStageTabs button');
    const previewIframe = document.getElementById('invoicePreviewIframe');
    const previewNewTab = document.getElementById('btnPreviewNewTab');
    if (stageTabs && previewIframe) {
        stageTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                stageTabs.forEach(t => {
                    t.classList.remove('active', 'btn-light');
                    t.classList.add('btn-outline-light');
                });
                this.classList.remove('btn-outline-light');
                this.classList.add('active', 'btn-light');

                const stage = this.getAttribute('data-stage');
                const baseUrl = window.BASE_URL || '';
                const newUrl = baseUrl + '/invoice.php?preview=1&stage=' + stage;
                previewIframe.src = newUrl;
                if (previewNewTab) previewNewTab.href = newUrl;
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

