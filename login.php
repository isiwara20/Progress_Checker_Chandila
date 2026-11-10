<?php
/**
 * Chandila Web Development – Staff Login Portal
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$errorMessage = '';
$redirect = $_GET['redirect'] ?? '';

// If already logged in, redirect to respective portal immediately
if (isLoggedIn()) {
    $role = getUserRole();
    if ($role === 'admin') {
        header("Location: " . BASE_URL . "/admin/index.php");
        exit;
    } elseif ($role === 'developer') {
        header("Location: " . BASE_URL . "/developer/index.php");
        exit;
    }
}

// Handle Staff Authentication POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'staff_login') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = "Security session expired. Please refresh the page.";
    } else {
        $usernameOrEmail = trim($_POST['username_or_email'] ?? '');
        $password        = $_POST['password'] ?? '';

        if (empty($usernameOrEmail) || empty($password)) {
            $errorMessage = "Please enter both username/email and password.";
        } else {
            $loginResult = loginUser($pdo, $usernameOrEmail, $password);

            if ($loginResult['success']) {
                $role = $loginResult['role'];
                if (!empty($redirect) && str_starts_with($redirect, '/')) {
                    header("Location: " . BASE_URL . $redirect);
                    exit;
                }

                if ($role === 'admin') {
                    header("Location: " . BASE_URL . "/admin/index.php");
                    exit;
                } else {
                    header("Location: " . BASE_URL . "/developer/index.php");
                    exit;
                }
            } else {
                $errorMessage = $loginResult['message'];
            }
        }
    }
}

// Disable preloader on login page to ensure instant, crisp render without any blocking overlay
$disablePreloader = true;
$pageTitle = 'Staff Login – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: linear-gradient(180deg, #f1f5f9 0%, #f8fafc 100%); min-height: 80vh; display: flex; align-items: center;">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-5">
                <div class="card border-0 shadow-sm p-4 p-md-5" style="border-radius: 16px; border: 1px solid #e2e8f0 !important; background: #ffffff;">
                    <div class="text-center mb-4">
                        <a href="<?php echo BASE_URL; ?>/index.php" class="d-inline-block mb-3">
                            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="img-fluid" style="max-height: 52px; object-fit: contain;">
                        </a>
                        <h4 class="text-dark fw-bold mb-1">Staff Portal Login</h4>
                        <p class="text-muted small mb-0">Authorized administration and developer access</p>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showChandilaToast('error', <?php echo json_encode($errorMessage); ?>, 'Authentication Failed');
                            });
                        </script>
                    <?php endif; ?>

                    <?php echo renderFlashAlerts(); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="staff_login">

                        <div class="mb-3">
                            <label for="username_or_email" class="form-label text-dark small fw-semibold">Username or Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border text-muted"><i class="fa-solid fa-user"></i></span>
                                <input type="text" class="form-control form-control-chandila" id="username_or_email" name="username_or_email" placeholder="e.g. admin or dev1" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="password" class="form-label text-dark small fw-semibold mb-0">Password</label>
                                <span class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-lock text-success me-1"></i> Bcrypt Secured</span>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light border text-muted"><i class="fa-solid fa-key"></i></span>
                                <input type="password" class="form-control form-control-chandila" id="password" name="password" placeholder="••••••••" required>
                                <button class="btn btn-outline-secondary border" type="button" id="togglePasswordBtn">
                                    <i class="fa-regular fa-eye text-muted" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-chandila-primary w-100 py-3 fw-bold mb-4 shadow-sm">
                            <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Sign In to Portal
                        </button>

                        <!-- Quick Demo Credentials Selector -->
                        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1.5px dashed #cbd5e1;">
                            <div class="text-muted small fw-semibold mb-2 text-center">
                                <i class="fa-solid fa-wand-magic-sparkles text-warning me-1"></i> Quick Demo Accounts (Click to Auto-Fill):
                            </div>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-primary demo-fill-btn fw-semibold" data-user="admin" data-pass="Admin@12345">
                                    <i class="fa-solid fa-user-shield me-1"></i> Admin
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary demo-fill-btn fw-semibold" data-user="dev1" data-pass="Dev1@12345">
                                    <i class="fa-solid fa-laptop-code me-1"></i> Dev 01
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary demo-fill-btn fw-semibold" data-user="dev2" data-pass="Dev2@12345">
                                    <i class="fa-solid fa-laptop-code me-1"></i> Dev 02
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Password toggle
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');
    if (toggleBtn && passwordInput && eyeIcon) {
        toggleBtn.addEventListener('click', function () {
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    }

    // Demo fill buttons
    document.querySelectorAll('.demo-fill-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const user = this.getAttribute('data-user');
            const pass = this.getAttribute('data-pass');
            document.getElementById('username_or_email').value = user;
            document.getElementById('password').value = pass;
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

