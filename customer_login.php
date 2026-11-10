<?php
/**
 * Chandila Web Development – Customer Project Portal Login
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'customer_login') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = "Security token expired. Please refresh the page.";
    } else {
        $jobId = trim($_POST['job_id'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        if (empty($jobId) || empty($phone)) {
            $errorMessage = "Please enter both Job ID and your registered phone number.";
        } else {
            header("Location: " . BASE_URL . "/track_project.php?job_id=" . urlencode($jobId) . "&phone=" . urlencode($phone));
            exit;
        }
    }
}

// Disable preloader on customer login
$disablePreloader = true;
$pageTitle = 'Customer Project Portal – Chandila Web Development';
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
                        <h4 class="text-dark fw-bold mb-1">Customer Portal Access</h4>
                        <p class="text-muted small mb-0">Track your project progress and milestones securely</p>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                showChandilaToast('error', <?php echo json_encode($errorMessage); ?>, 'Access Denied');
                            });
                        </script>
                    <?php endif; ?>

                    <?php echo renderFlashAlerts(); ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="customer_login">

                        <div class="mb-3">
                            <label for="c_job_id" class="form-label text-dark small fw-semibold">Your Job ID</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border text-muted"><i class="fa-solid fa-barcode text-primary"></i></span>
                                <input type="text" class="form-control form-control-chandila" id="c_job_id" name="job_id" placeholder="e.g. CHW-2026-0001" required autofocus>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="c_phone" class="form-label text-dark small fw-semibold">Registered WhatsApp / Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border text-muted"><i class="fa-brands fa-whatsapp text-success"></i></span>
                                <input type="text" class="form-control form-control-chandila" id="c_phone" name="phone" placeholder="e.g. 0768433340 or +9476843340" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-chandila-primary w-100 py-3 fw-bold mb-3 shadow-sm">
                            <i class="fa-solid fa-crosshairs me-2"></i> Access My Project
                        </button>

                        <div class="text-center pt-2 border-top">
                            <span class="text-muted small">Need to start a new project?</span>
                            <a href="<?php echo BASE_URL; ?>/request_project.php" class="text-primary fw-semibold small ms-1">Submit Requirement &rarr;</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

