<?php
/**
 * Chandila Web Development – Admin Header Component
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();
$adminPage = basename($_SERVER['PHP_SELF']);

// Query pending request counts for badge
$pendingReqCount = 0;
$pendingPayCount = 0;
if ($pdo) {
    try {
        $stmtReq = $pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('Request Submitted', 'Request Reviewed')");
        $pendingReqCount = (int)$stmtReq->fetchColumn();

        $stmtPay = $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'");
        $pendingPayCount = (int)$stmtPay->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $adminTitle ?? 'Admin Dashboard'; ?> – Chandila Web Development</title>
    <!-- Favicon & Brand Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo BASE_URL; ?>/assets/images/favicon-32x32.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/favicon-32x32.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo BASE_URL; ?>/assets/images/favicon-16x16.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/favicon-16x16.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo BASE_URL; ?>/assets/images/apple-touch-icon.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/apple-touch-icon.png'); ?>">
    <link rel="shortcut icon" href="<?php echo BASE_URL; ?>/assets/images/favicon.png?v=<?php echo filemtime(__DIR__ . '/../assets/images/favicon.png'); ?>">
    <!-- Google Fonts Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
    <!-- Toast Notification System (Early Load) -->
    <script src="<?php echo BASE_URL; ?>/assets/js/toast.js"></script>
</head>
<body class="admin-body">

<div class="admin-wrapper">
    <!-- Include Sidebar -->
    <?php require_once __DIR__ . '/admin_sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="admin-main-content">
        <!-- Top Navigation Bar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h5 class="mb-0 text-dark fw-bold"><?php echo $adminTitle ?? 'Admin Control Center'; ?></h5>
                    <span class="text-muted small">Chandila Web Development • Management Portal</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- System Timezone Clock -->
                <div class="d-none d-md-flex align-items-center gap-2 text-muted small px-3 py-1 rounded bg-light border">
                    <i class="fa-regular fa-clock text-primary"></i>
                    <span><?php echo date('D, M d, Y - h:i A'); ?> (Asia/Colombo)</span>
                </div>

                <!-- Public Site Link -->
                <a href="<?php echo BASE_URL; ?>/index.php" target="_blank" class="btn btn-sm btn-outline-primary d-none d-sm-inline-flex align-items-center gap-1" title="View Public Website">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    <span>Live Site</span>
                </a>

                <!-- User Profile Dropdown -->
                <div class="dropdown">
                    <button class="btn btn-light border text-dark d-flex align-items-center gap-2 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar-circle" style="width: 30px; height: 30px; font-size: 0.8rem;">
                            <i class="fa-solid fa-user-shield"></i>
                        </div>
                        <span class="d-none d-md-inline fw-semibold small"><?php echo htmlspecialchars($currentUser['full_name']); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border">
                        <li>
                            <div class="px-3 py-2 border-bottom">
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
                                <div class="text-muted small"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                                <span class="badge bg-primary text-uppercase mt-1" style="font-size: 0.65rem;">Administrator</span>
                            </div>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?php echo BASE_URL; ?>/admin/settings.php"><i class="fa-solid fa-sliders me-2 text-primary"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger admin-logout-trigger" href="<?php echo BASE_URL; ?>/logout.php" data-confirm-logout="true"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Log Out</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Body Content Wrapper Starts -->
        <main class="admin-body-content">
            <?php echo renderFlashAlerts(); ?>

