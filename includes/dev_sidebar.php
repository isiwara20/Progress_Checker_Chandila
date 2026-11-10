<?php
/**
 * Chandila Web Development – Developer Sidebar Component
 */
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="admin-sidebar">
    <div class="admin-sidebar-brand">
        <a href="<?php echo BASE_URL; ?>/developer/index.php" class="d-flex align-items-center justify-content-center text-decoration-none">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="admin-logo-img">
        </a>
    </div>

    <ul class="admin-sidebar-menu">
        <li class="admin-menu-header">My Workspace</li>

        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/developer/index.php" class="admin-menu-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-gauge-high"></i>
                <span>Developer Dashboard</span>
            </a>
        </li>

        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/developer/my_projects.php" class="admin-menu-link <?php echo in_array($currentPage, ['my_projects.php', 'project_view.php']) ? 'active' : ''; ?>">
                <i class="fa-solid fa-laptop-code"></i>
                <span>My Assigned Projects</span>
            </a>
        </li>

        <li class="admin-menu-header">Account</li>

        <li class="admin-menu-item">
            <a href="<?php echo BASE_URL; ?>/developer/profile.php" class="admin-menu-link <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>">
                <i class="fa-solid fa-user-gear"></i>
                <span>My Profile & Skills</span>
            </a>
        </li>

        <li class="admin-menu-item mt-4 pt-2 border-top border-secondary border-opacity-25">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="admin-menu-link text-danger">
                <i class="fa-solid fa-arrow-right-from-bracket text-danger"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>

    <div class="admin-sidebar-footer">
        <div class="user-avatar-circle" style="background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
            <i class="fa-solid fa-code"></i>
        </div>
        <div class="overflow-hidden">
            <div class="text-dark small fw-bold text-truncate"><?php echo htmlspecialchars($currentUser['full_name']); ?></div>
            <div class="text-muted small" style="font-size: 0.72rem;">@<?php echo htmlspecialchars($currentUser['username']); ?></div>
        </div>
    </div>
</aside>

