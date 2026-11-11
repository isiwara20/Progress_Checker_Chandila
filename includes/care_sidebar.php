<?php
/**
 * Chandila Web Development – Customer Care Sidebar Component
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$currentUser = getCurrentUser();
?>
<aside class="admin-sidebar" id="careSidebar">
    <!-- Brand Title -->
    <div class="admin-sidebar-brand">
        <a href="<?php echo BASE_URL; ?>/care/index.php" class="d-flex align-items-center justify-content-center text-decoration-none">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="admin-logo-img">
        </a>
    </div>

    <!-- Sidebar Menu -->
    <ul class="admin-sidebar-menu">
        <li class="admin-menu-header d-flex justify-content-between align-items-center">
            <span>Customer Care Desk</span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" style="font-size: 0.65rem;">
                <i class="fa-solid fa-headset me-1"></i> Care Ops
            </span>
        </li>

        <!-- 1. Dashboard & SOP Guide -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/index.php" class="admin-menu-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard & SOP Guide</span>
            </a>
        </li>

        <li class="admin-menu-header">Client Operations</li>

        <!-- 2. Enter Customer Order -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/orders.php" class="admin-menu-link <?php echo ($currentPage === 'orders.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-cart-plus text-primary"></i>
                <span>Enter Customer Order</span>
            </a>
        </li>

        <!-- 3. Assign Tasks to Developers -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/assign.php" class="admin-menu-link <?php echo ($currentPage === 'assign.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-check text-info"></i>
                <span>Assign Developers</span>
                <?php if (!empty($unassignedCount)): ?>
                    <span class="admin-menu-badge bg-warning text-dark"><?php echo $unassignedCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="admin-menu-header">Finance & Milestones</li>

        <!-- 4. Mark Payments Done -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/payments.php" class="admin-menu-link <?php echo ($currentPage === 'payments.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-credit-card text-success"></i>
                <span>Mark Payments Done</span>
                <?php if (!empty($pendingPaymentsCount)): ?>
                    <span class="admin-menu-badge bg-danger text-white"><?php echo $pendingPaymentsCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <li class="admin-menu-header">Monitoring & Support</li>

        <!-- 5. Check Project Status -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/projects.php" class="admin-menu-link <?php echo in_array($currentPage, ['projects.php', 'project_view.php']) ? 'active' : ''; ?>">
                <i class="fa-solid fa-bars-progress text-warning"></i>
                <span>Check Project Status</span>
            </a>
        </li>

        <!-- 6. Client Live Tracker Link -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/track_project.php" target="_blank" class="admin-menu-link">
                <i class="fa-solid fa-satellite-dish text-info"></i>
                <span>Client Live Tracker <i class="fa-solid fa-arrow-up-right-from-square ms-1 small opacity-50"></i></span>
            </a>
        </li>

        <?php if (getUserRole() === 'admin'): ?>
            <li class="admin-menu-header">Administrator Access</li>
            <li class="admin-menu-item">
                <a href="<?php echo BASE_URL; ?>/admin/index.php" class="admin-menu-link">
                    <i class="fa-solid fa-shield-halved text-danger"></i>
                    <span>Return to Admin Panel</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>

    <!-- User Profile Footer with Confirm Logout Modal Trigger -->
    <div class="admin-sidebar-footer">
        <div class="user-avatar-circle" style="background: linear-gradient(135deg, #0ea5e9, #2563eb); color: #fff;">
            <i class="fa-solid fa-headset"></i>
        </div>
        <div class="admin-user-info flex-grow-1 overflow-hidden">
            <div class="admin-user-name text-truncate"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'Care Agent'); ?></div>
            <div class="admin-user-role badge <?php echo (getUserRole() === 'admin') ? 'bg-danger-subtle text-danger border border-danger-subtle' : 'bg-primary bg-opacity-20 text-primary'; ?> py-0 px-2" style="font-size: 0.68rem;">
                <?php echo (getUserRole() === 'admin') ? 'Admin • Care View' : 'Customer Care'; ?>
            </div>
        </div>
        <button type="button" class="btn btn-link text-secondary p-0 border-0" data-bs-toggle="modal" data-bs-target="#logoutConfirmModal" title="Sign Out">
            <i class="fa-solid fa-right-from-bracket text-danger fs-5"></i>
        </button>
    </div>
</aside>

<!-- Instant Synchronous Scroll Restoration -->
<script>
(function() {
    var sidebar = document.getElementById('careSidebar') || document.querySelector('.admin-sidebar');
    if (!sidebar) return;

    var storageKey = 'care_sidebar_scroll_top';
    var savedPos = sessionStorage.getItem(storageKey);
    if (savedPos !== null) {
        sidebar.scrollTop = parseInt(savedPos, 10);
    } else {
        var activeItem = sidebar.querySelector('.admin-menu-link.active');
        if (activeItem) {
            var rect = activeItem.getBoundingClientRect();
            var sidebarRect = sidebar.getBoundingClientRect();
            if (rect.top < sidebarRect.top || rect.bottom > sidebarRect.bottom) {
                activeItem.scrollIntoView({ block: 'center' });
            }
        }
    }

    sidebar.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (link && link.classList.contains('admin-menu-link')) {
            sessionStorage.setItem(storageKey, sidebar.scrollTop);
        }
    });

    var scrollDebounce;
    sidebar.addEventListener('scroll', function() {
        clearTimeout(scrollDebounce);
        scrollDebounce = setTimeout(function() {
            sessionStorage.setItem(storageKey, sidebar.scrollTop);
        }, 80);
    }, { passive: true });

    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem(storageKey, sidebar.scrollTop);
    });
})();
</script>

