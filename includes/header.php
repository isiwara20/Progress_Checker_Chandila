<?php
/**
 * Chandila Web Development – Public Header Component (Light Theme)
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'Chandila Web Development – Where Innovation Meets Growth';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
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
    <!-- Custom Public CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
    <!-- Client Assistant & Order Tracking Chatbot CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/chatbot.css?v=<?php echo file_exists(__DIR__ . '/../assets/css/chatbot.css') ? filemtime(__DIR__ . '/../assets/css/chatbot.css') : time(); ?>">
    <!-- Toast Notification System (Early Load) -->
    <script src="<?php echo BASE_URL; ?>/assets/js/toast.js"></script>
</head>
<body>

    <?php if (empty($disablePreloader)): ?>
    <!-- Site Animated Preloader (Initial Page Load Animation) -->
    <div id="sitePreloader" class="site-preloader">
        <div class="preloader-content">
            <div class="preloader-logo-wrapper">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="preloader-logo">
            </div>
            <div class="preloader-progress-bar">
                <div class="preloader-progress-line"></div>
            </div>
            <div class="preloader-tagline">Where Innovation Meets Growth</div>
        </div>
    </div>
    <script>
    (function () {
        var preloader = document.getElementById('sitePreloader');
        if (!preloader) return;
        function hide() {
            if (preloader && !preloader.classList.contains('fade-out')) {
                preloader.classList.add('fade-out');
                setTimeout(function () {
                    preloader.style.display = 'none';
                }, 500);
            }
        }
        window.addEventListener('load', function () {
            setTimeout(hide, 300);
        });
        setTimeout(hide, 1200);
    })();
    </script>
    <?php endif; ?>

    <!-- Top Contact Bar -->
    <div class="top-contact-bar py-2 d-none d-md-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-4">
                <span><i class="fa-solid fa-location-dot text-warning me-1"></i> Colombo, Sri Lanka</span>
                <span><i class="fa-solid fa-phone text-warning me-1"></i> <a href="tel:0768433340" class="text-white text-decoration-none">076 843 3340</a></span>
                <span><i class="fa-solid fa-envelope text-warning me-1"></i> contact@chandilaweb.com</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="https://wa.me/9476843340" target="_blank" class="badge bg-success text-white px-2 py-1 text-decoration-none">
                    <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp: 076 843 3340
                </a>
                <a href="<?php echo BASE_URL; ?>/customer_login.php">
                    <i class="fa-solid fa-user-lock me-1"></i> Customer Login
                </a>
                <span class="text-secondary opacity-50">|</span>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo getUserRole() === 'admin' ? BASE_URL . '/admin/' : BASE_URL . '/developer/'; ?>" class="text-warning fw-semibold">
                        <i class="fa-solid fa-gauge me-1"></i> My Portal (<?php echo htmlspecialchars(getUserName()); ?>)
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php">
                        <i class="fa-solid fa-lock me-1"></i> Staff Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Main Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light navbar-chandila sticky-top">
        <div class="container">
            <a class="navbar-brand py-1" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="brand-logo-img">
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'about.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'services.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/services.php">Our Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'portfolio.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/portfolio.php">
                            Portfolio <span class="badge bg-warning text-dark font-monospace" style="font-size: 0.7rem;">500+</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'careers.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/careers.php">
                            Careers <span class="badge bg-primary text-white font-monospace" style="font-size: 0.65rem;">Hiring</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'track_project.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/track_project.php">
                            <i class="fa-solid fa-crosshairs text-primary me-1"></i> Track My Project
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($currentPage === 'contact.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/contact.php">Contact</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2">
                    <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-gold d-flex align-items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Request a Project</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>
