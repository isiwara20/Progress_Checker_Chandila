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
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-brands fa-whatsapp text-success"></i> WhatsApp API Integration</h5>
                <span class="badge bg-success-subtle text-success">Configurable API</span>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="save_whatsapp_settings">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Active Messaging Provider</label>
                        <select class="form-select form-select-chandila" name="whatsapp_provider" id="providerSelect">
                            <option value="simulator" <?php echo (($settings['whatsapp_provider'] ?? '') === 'simulator') ? 'selected' : ''; ?>>Interactive Simulator (Local & Offline Mode)</option>
                            <option value="ultramsg" <?php echo (($settings['whatsapp_provider'] ?? '') === 'ultramsg') ? 'selected' : ''; ?>>UltraMsg Cloud API</option>
                            <option value="generic_webhook" <?php echo (($settings['whatsapp_provider'] ?? '') === 'generic_webhook') ? 'selected' : ''; ?>>Generic cURL Webhook Gateway</option>
                        </select>
                        <div class="form-text text-secondary small">Simulator logs all outgoing messages to database and allows instant manual testing without live API keys.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">API Endpoint URL</label>
                        <input type="url" class="form-control form-control-chandila" name="whatsapp_api_url" value="<?php echo htmlspecialchars($settings['whatsapp_api_url'] ?? ''); ?>" placeholder="https://api.ultramsg.com/instance9999/messages/chat">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">API Token / Secret Key</label>
                        <input type="password" class="form-control form-control-chandila" name="whatsapp_api_token" value="<?php echo htmlspecialchars($settings['whatsapp_api_token'] ?? ''); ?>" placeholder="••••••••">
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Instance ID</label>
                            <input type="text" class="form-control form-control-chandila" name="whatsapp_instance_id" value="<?php echo htmlspecialchars($settings['whatsapp_instance_id'] ?? ''); ?>" placeholder="instance9999">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Sender Phone Number</label>
                            <input type="text" class="form-control form-control-chandila" name="whatsapp_sender_number" value="<?php echo htmlspecialchars($settings['whatsapp_sender_number'] ?? '+9476843340'); ?>" placeholder="+94 76 843 3340">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-electric w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save WhatsApp Configuration
                    </button>
                </form>
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

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

