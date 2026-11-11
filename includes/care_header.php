<?php
/**
 * Chandila Web Development – Customer Care Header Component
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Customer Care Gate (allows customer_care & admin)
requireCustomerCare();

$pdo = getDB();
$currentUser = getCurrentUser();
$carePage = basename($_SERVER['PHP_SELF']);

// Dynamic sidebar badges
$unassignedCount = 0;
$pendingPaymentsCount = 0;
$activeCareDuty = null;
if ($pdo) {
    try {
        $unassignedCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE assigned_developer_id IS NULL AND status != 'Cancelled'")->fetchColumn();
        $pendingPaymentsCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
        if (!empty($currentUser['id'])) {
            $stmtDuty = $pdo->prepare("SELECT * FROM care_working_hours WHERE user_id = ? AND status = 'In Progress' ORDER BY id DESC LIMIT 1");
            $stmtDuty->execute([$currentUser['id']]);
            $activeCareDuty = $stmtDuty->fetch();
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $careTitle ?? 'Customer Care Desk'; ?> – Chandila Web Development</title>
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
    <!-- Include Customer Care Sidebar -->
    <?php require_once __DIR__ . '/care_sidebar.php'; ?>

    <!-- Main Content Container -->
    <div class="admin-main-content">
        <!-- Top Navigation Bar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle-btn" id="sidebarToggleBtn" type="button" aria-label="Toggle Sidebar">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="d-none d-sm-block">
                    <h5 class="mb-0 text-dark fw-bold"><?php echo $careTitle ?? 'Customer Care Desk'; ?></h5>
                    <span class="text-muted small">Chandila Web Development • Client Relations & Operations</span>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Duty / Working Hours Live Indicator -->
                <a href="<?php echo BASE_URL; ?>/care/hours.php" class="btn btn-sm <?php echo $activeCareDuty ? 'btn-outline-success bg-success-subtle text-success border-success' : 'btn-outline-secondary'; ?> d-inline-flex align-items-center gap-1 text-decoration-none px-2 py-1" title="<?php echo $activeCareDuty ? 'Currently ON DUTY since ' . date('h:i A', strtotime($activeCareDuty['start_time'])) . ' (' . htmlspecialchars($activeCareDuty['shift_type']) . ')' : 'Currently OFF DUTY – Click to Clock In / Log Hours'; ?>">
                    <i class="fa-solid fa-clock<?php echo $activeCareDuty ? ' fa-spin-pulse text-success' : ''; ?>"></i>
                    <span class="d-none d-sm-inline fw-semibold small"><?php echo $activeCareDuty ? 'On Duty (' . date('h:i A', strtotime($activeCareDuty['start_time'])) . ')' : 'Off Duty'; ?></span>
                </a>

                <!-- Fast Quick Action: Enter Order -->
                <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-sm btn-electric d-none d-md-inline-flex align-items-center">
                    <i class="fa-solid fa-cart-plus me-1"></i> Enter Order
                </a>

                <!-- Client Live Tracker Link -->
                <a href="<?php echo BASE_URL; ?>/track_project.php" target="_blank" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex align-items-center" title="Open Client Project Tracker">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Client Tracker
                </a>

                <!-- User Dropdown Menu -->
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border dropdown-toggle d-flex align-items-center gap-2 py-1 px-2" type="button" data-bs-toggle="dropdown">
                        <div class="user-avatar-circle" style="width: 28px; height: 28px; font-size: 0.8rem; background: #2563eb; color: #fff;">
                            <i class="fa-solid fa-headset"></i>
                        </div>
                        <span class="d-none d-lg-inline text-dark fw-semibold small"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Agent'); ?></span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 py-2" style="border-radius: 12px; min-width: 200px;">
                        <li class="px-3 py-1">
                            <div class="fw-bold text-dark small"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Agent'); ?></div>
                            <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($currentUser['email'] ?? ''); ?></div>
                            <div class="badge <?php echo (getUserRole() === 'admin') ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary-subtle text-primary border border-primary-subtle'; ?> mt-1" style="font-size: 0.7rem;">
                                <?php echo (getUserRole() === 'admin') ? 'Admin • Care View' : 'Customer Care'; ?>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>/care/hours.php">
                                <i class="fa-solid fa-business-time me-2 text-warning"></i> Working Hours & Duty
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>/care/index.php">
                                <i class="fa-solid fa-book-open me-2 text-primary"></i> SOP Operating Guide
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small" href="<?php echo BASE_URL; ?>/care/orders.php">
                                <i class="fa-solid fa-cart-plus me-2 text-success"></i> Enter New Order
                            </a>
                        </li>
                        <?php if (getUserRole() === 'admin'): ?>
                            <li>
                                <a class="dropdown-item small text-danger" href="<?php echo BASE_URL; ?>/admin/index.php">
                                    <i class="fa-solid fa-shield-halved me-2"></i> Switch to Admin Panel
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-2"></li>
                        <li>
                            <a class="dropdown-item small text-danger admin-logout-trigger" href="<?php echo BASE_URL; ?>/logout.php" data-confirm-logout="true">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Body Content Wrapper Starts -->
        <main class="admin-body-content">
            <?php echo renderFlashAlerts(); ?>

