<?php
$devTitle = 'Developer Profile & Skills';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Developer Gate
requireDeveloper();

$pdo = getDB();
$currentUser = getCurrentUser();
$devId = $currentUser['id'];

// Handle Profile & Password Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        if ($action === 'update_profile') {
            $fullName = trim($_POST['full_name'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $skills   = trim($_POST['skills'] ?? '');

            if (empty($fullName)) {
                setFlash('error', 'Full Name is required.');
            } else {
                $up = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, skills = ? WHERE id = ?");
                $up->execute([$fullName, $phone, $skills, $devId]);

                $_SESSION['user_fullname'] = $fullName;
                $_SESSION['user_phone']    = $phone;
                $_SESSION['user_skills']   = $skills;

                setFlash('success', 'Profile updated successfully.');
                header("Location: " . BASE_URL . "/developer/profile.php");
                exit;
            }
        } elseif ($action === 'change_dev_password') {
            $curPass = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            $conPass = $_POST['confirm_password'] ?? '';

            if (empty($curPass) || empty($newPass)) {
                setFlash('error', 'Please fill in all password fields.');
            } elseif ($newPass !== $conPass) {
                setFlash('error', 'New passwords do not match.');
            } elseif (strlen($newPass) < 8) {
                setFlash('error', 'Password must be at least 8 characters long.');
            } else {
                $hash = $pdo->query("SELECT password_hash FROM users WHERE id = {$devId}")->fetchColumn();
                if (password_verify($curPass, $hash)) {
                    $newHash = password_hash($newPass, PASSWORD_BCRYPT);
                    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$newHash, $devId]);
                    setFlash('success', 'Password updated successfully!');
                } else {
                    setFlash('error', 'Current password is incorrect.');
                }
            }
            header("Location: " . BASE_URL . "/developer/profile.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/dev_header.php';

// Fetch fresh user data
$userData = $pdo->query("SELECT * FROM users WHERE id = {$devId}")->fetch();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-user-gear text-primary me-2"></i> Developer Profile</h3>
        <p class="text-secondary small mb-0">Manage your technical skills, contact details, and account security.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Left: Profile Details -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-id-card text-primary"></i> Personal Information</h5>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_profile">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Username</label>
                        <input type="text" class="form-control form-control-chandila" value="<?php echo htmlspecialchars($userData['username']); ?>" disabled>
                        <div class="form-text text-secondary small">Usernames are managed by system administrator.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Email Address</label>
                        <input type="email" class="form-control form-control-chandila" value="<?php echo htmlspecialchars($userData['email']); ?>" disabled>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="full_name" value="<?php echo htmlspecialchars($userData['full_name']); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Phone / WhatsApp Number</label>
                        <input type="text" class="form-control form-control-chandila" name="phone" value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>" placeholder="+94 77 123 4567">
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-dark small fw-semibold">Technical Skills & Specializations</label>
                        <textarea class="form-control form-control-chandila" name="skills" rows="3" placeholder="e.g. Core PHP 8.2, MySQL Architecture, Bootstrap 5, REST APIs..."><?php echo htmlspecialchars($userData['skills'] ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-electric w-100">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Profile Details
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Right: Change Password -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-key text-primary"></i> Change Password</h5>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="change_dev_password">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Current Password</label>
                        <input type="password" class="form-control form-control-chandila" name="current_password" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">New Password</label>
                        <input type="password" class="form-control form-control-chandila" name="new_password" placeholder="Minimum 8 characters" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-dark small fw-semibold">Confirm New Password</label>
                        <input type="password" class="form-control form-control-chandila" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn btn-outline-warning w-100">
                        <i class="fa-solid fa-lock me-1"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/dev_footer.php'; ?>

