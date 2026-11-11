<?php
/**
 * Chandila Web Development – Admin Sidebar Component
 * Includes the 12 required modules from specifications
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <!-- Brand Title -->
    <div class="admin-sidebar-brand">
        <a href="<?php echo BASE_URL; ?>/admin/index.php" class="d-flex align-items-center justify-content-center text-decoration-none">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="admin-logo-img">
        </a>
    </div>

    <!-- Sidebar 12-Item Navigation Menu -->
    <ul class="admin-sidebar-menu">
        <li class="admin-menu-header">Overview</li>

        <!-- 1. Dashboard -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/index.php" class="admin-menu-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="admin-menu-header">Project Operations</li>

        <!-- 2. New Project Requests -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/requests.php" class="admin-menu-link <?php echo ($currentPage === 'requests.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-inbox"></i>
                <span>New Requests</span>
                <?php if (!empty($pendingReqCount)): ?>
                    <span class="admin-menu-badge bg-danger text-white"><?php echo $pendingReqCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- 3. All Projects -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="admin-menu-link <?php echo in_array($currentPage, ['projects.php', 'project_view.php']) ? 'active' : ''; ?>">
                <i class="fa-solid fa-diagram-project"></i>
                <span>All Projects</span>
            </a>
        </li>

        <!-- 4. Manage Customers -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/customers.php" class="admin-menu-link <?php echo ($currentPage === 'customers.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users"></i>
                <span>Manage Customers</span>
            </a>
        </li>

        <!-- 5. Manage Staff & Developers -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/developers.php" class="admin-menu-link <?php echo ($currentPage === 'developers.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-users-gear"></i>
                <span>Staff & Developers</span>
            </a>
        </li>

        <!-- Customer Care Portal Link -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/care/index.php" class="admin-menu-link">
                <i class="fa-solid fa-headset text-warning"></i>
                <span>Customer Care Desk</span>
            </a>
        </li>

        <!-- Customer Care Working Hours Link -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/care_hours.php" class="admin-menu-link <?php echo ($currentPage === 'care_hours.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-business-time text-warning"></i>
                <span>Care Working Hours</span>
            </a>
        </li>

        <!-- 6. Assign Projects -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/assign.php" class="admin-menu-link <?php echo ($currentPage === 'assign.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-check"></i>
                <span>Assign Projects</span>
            </a>
        </li>

        <li class="admin-menu-header">Finance & Progress</li>

        <!-- 7. Payment Management -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="admin-menu-link <?php echo ($currentPage === 'payments.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-credit-card"></i>
                <span>Payment Management</span>
                <?php if (!empty($pendingPayCount)): ?>
                    <span class="admin-menu-badge bg-warning text-dark"><?php echo $pendingPayCount; ?></span>
                <?php endif; ?>
            </a>
        </li>

        <!-- 8. Project Progress -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/progress.php" class="admin-menu-link <?php echo ($currentPage === 'progress.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-bars-progress"></i>
                <span>Project Progress</span>
            </a>
        </li>

        <li class="admin-menu-header">Media & Careers</li>

        <!-- Posts & Showcase (1080x1080) -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/posts.php" class="admin-menu-link <?php echo ($currentPage === 'posts.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-photo-film text-warning"></i>
                <span>Posts & Showcase (1:1)</span>
            </a>
        </li>

        <!-- Job Applications -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/careers.php" class="admin-menu-link <?php echo ($currentPage === 'careers.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-tie text-info"></i>
                <span>Career Applicants</span>
            </a>
        </li>

        <li class="admin-menu-header">Communication & System</li>

        <!-- 9. WhatsApp Notifications -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/whatsapp.php" class="admin-menu-link <?php echo ($currentPage === 'whatsapp.php') ? 'active' : ''; ?>">
                <i class="fa-brands fa-whatsapp text-success"></i>
                <span>WhatsApp Log</span>
            </a>
        </li>

        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/emails.php" class="admin-menu-link <?php echo ($currentPage === 'emails.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-envelope text-primary"></i>
                <span>Email Log</span>
            </a>
        </li>

        <!-- 10. Reports -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/reports.php" class="admin-menu-link <?php echo ($currentPage === 'reports.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-chart-line"></i>
                <span>Reports & Analytics</span>
            </a>
        </li>

        <!-- 11. Settings -->
        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/admin/settings.php" class="admin-menu-link <?php echo ($currentPage === 'settings.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </li>

        <!-- 12. Logout -->
        <li class="admin-menu-item mt-3 pt-2 border-top border-secondary border-opacity-25">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="admin-menu-link text-danger admin-logout-trigger" data-confirm-logout="true" title="Log out of session">
                <i class="fa-solid fa-arrow-right-from-bracket text-danger"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <!-- Sidebar User Footer -->
    <div class="admin-sidebar-footer">
        <div class="user-avatar-circle">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-dark small fw-bold text-truncate"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
            <div class="text-muted small" style="font-size: 0.72rem;">Administrator</div>
        </div>
    </div>
</aside>

<!-- Instant Synchronous Sidebar Scroll Position Handler -->
<script>
(function() {
    var sidebar = document.querySelector('.admin-sidebar');
    if (!sidebar) return;

    var storageKey = 'chandila_admin_sidebar_scroll';

    // 1. Immediate Synchronous Scroll Restoration (Zero Visual Jump)
    function restoreSidebarPos() {
        var saved = sessionStorage.getItem(storageKey);
        if (saved !== null && saved !== '' && !isNaN(saved)) {
            var target = parseInt(saved, 10);
            if (sidebar.scrollTop !== target) {
                sidebar.scrollTop = target;
            }
        } else {
            // If direct visit or no stored position, ensure active menu item is scrolled into view
            var activeLink = sidebar.querySelector('.admin-menu-link.active');
            if (activeLink) {
                var linkRect = activeLink.getBoundingClientRect();
                var sidebarRect = sidebar.getBoundingClientRect();
                if (linkRect.top < (sidebarRect.top + 60) || linkRect.bottom > (sidebarRect.bottom - 60)) {
                    activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
                    sessionStorage.setItem(storageKey, sidebar.scrollTop);
                }
            }
        }
    }

    // Run right now as HTML is parsed
    restoreSidebarPos();

    // Run again once DOM is ready and when window loads (after web fonts load)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', restoreSidebarPos);
    }
    window.addEventListener('load', restoreSidebarPos);

    // Save position continuously on scroll (debounced)
    var scrollTimer = null;
    sidebar.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            sessionStorage.setItem(storageKey, sidebar.scrollTop);
        }, 40);
    }, { passive: true });

    // Instantly save on any link click
    sidebar.addEventListener('click', function(e) {
        var link = e.target.closest('a');
        if (link) {
            sessionStorage.setItem(storageKey, sidebar.scrollTop);
        }
    });

    // Save on beforeunload
    window.addEventListener('beforeunload', function() {
        if (sidebar && sidebar.scrollTop > 0) {
            sessionStorage.setItem(storageKey, sidebar.scrollTop);
        }
    });
})();
</script>

