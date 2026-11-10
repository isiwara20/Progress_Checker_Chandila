<?php
$adminTitle = 'System & WhatsApp Settings';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

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

        // 2. Company & Payment Settings
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

        // 3. Admin Password Change
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
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

