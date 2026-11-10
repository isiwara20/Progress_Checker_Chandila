<?php
/**
 * Chandila Web Development – Public Footer Component (Light & Slate Contrast)
 */
?>
    <!-- Floating WhatsApp Contact Button -->
    <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20would%20like%20to%20inquire%20about%20a%20web%20development%20project." 
       target="_blank" 
       class="floating-whatsapp" 
       title="Chat with us on WhatsApp (076 843 3340)" 
       aria-label="WhatsApp Chat">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <!-- Footer -->
    <footer class="footer-chandila">
        <div class="container">
            <div class="row g-3 g-md-4 mb-3 mb-md-4 footer-content-row">
                <!-- Col 1: Brand Info -->
                <div class="col-12 col-lg-4 mb-2 mb-lg-0 footer-brand-col">
                    <div class="d-flex align-items-center justify-content-between justify-content-md-start mb-2">
                        <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Web Development" class="footer-logo-img">
                        <!-- Mobile Quick Contact Icons Inline -->
                        <div class="d-flex d-md-none gap-2">
                            <a href="https://wa.me/9476843340" target="_blank" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp text-success"></i></a>
                            <a href="tel:0768433340" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="Call Us"><i class="fa-solid fa-phone text-warning"></i></a>
                        </div>
                    </div>
                    <p class="text-secondary small mb-2 mb-md-3 footer-brand-desc">
                        <strong>Chandila Web Development</strong> – Where Innovation Meets Growth. High-performance web applications, e-commerce stores & custom management systems.
                        <span class="d-none d-md-inline">Delivering over 500+ successful projects across Sri Lanka and globally.</span>
                    </p>
                    <div class="d-none d-md-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-trophy me-1"></i> 500+ Delivered Projects</span>
                        <span class="badge bg-success text-white px-2 py-1"><i class="fa-solid fa-shield-check me-1"></i> Verified Agency</span>
                    </div>
                    <div class="d-none d-md-flex gap-2">
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
                        <a href="https://wa.me/9476843340" target="_blank" class="btn btn-sm btn-outline-secondary rounded-circle footer-social-btn" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp text-success"></i></a>
                    </div>
                </div>

                <!-- Col 2: Services (col-6 on mobile to sit side-by-side with Quick Links) -->
                <div class="col-6 col-md-3 col-lg-2">
                    <h5 class="footer-heading">Services</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>/services.php">Websites</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/services.php">E-Commerce</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/services.php">Web Portals</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/services.php">Student LMS</a></li>
                        <li class="d-none d-md-block"><a href="<?php echo BASE_URL; ?>/services.php">Booking Engines</a></li>
                        <li class="d-none d-md-block"><a href="<?php echo BASE_URL; ?>/services.php">Cloud & Hosting</a></li>
                    </ul>
                </div>

                <!-- Col 3: Quick Navigation (col-6 on mobile, pairing with Services) -->
                <div class="col-6 col-md-3 col-lg-3">
                    <h5 class="footer-heading">Quick Links</h5>
                    <ul class="footer-links">
                        <li><a href="<?php echo BASE_URL; ?>/track_project.php"><i class="fa-solid fa-magnifying-glass me-1 text-warning"></i> Track Project</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/portfolio.php"><i class="fa-solid fa-laptop-code me-1 text-warning"></i> Portfolio (500+)</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/careers.php"><i class="fa-solid fa-briefcase me-1 text-warning"></i> Careers</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/about.php"><i class="fa-solid fa-circle-info me-1 text-warning"></i> About Us</a></li>
                        <li class="d-none d-md-block"><a href="<?php echo BASE_URL; ?>/customer_login.php"><i class="fa-solid fa-user-lock me-1 text-warning"></i> Customer Login</a></li>
                        <li class="d-none d-md-block"><a href="<?php echo BASE_URL; ?>/login.php"><i class="fa-solid fa-lock me-1 text-secondary"></i> Staff Portal</a></li>
                    </ul>
                </div>

                <!-- Col 4: Contact & Location (Compact format on mobile) -->
                <div class="col-12 col-md-6 col-lg-3 mt-1 mt-md-0">
                    <h5 class="footer-heading">Contact</h5>
                    <div class="footer-contact-info text-secondary small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-phone text-warning"></i>
                            <a href="tel:0768433340" class="text-light text-decoration-none fw-semibold">076 843 3340</a>
                            <span class="text-secondary opacity-50">/</span>
                            <a href="https://wa.me/9476843340" target="_blank" class="text-success text-decoration-none fw-semibold"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="fa-solid fa-envelope text-warning"></i>
                            <a href="mailto:contact@chandilaweb.com" class="text-secondary text-decoration-none">contact@chandilaweb.com</a>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2">
                            <i class="fa-solid fa-map-location-dot text-warning mt-1"></i>
                            <span>Galle Road, Colombo 03, Sri Lanka</span>
                        </div>
                        <div class="d-none d-md-flex align-items-center gap-2">
                            <i class="fa-solid fa-clock text-warning"></i>
                            <span>Mon - Sat: 9:00 AM - 6:00 PM (IST)</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Disclaimer -->
            <div class="pt-3 border-top border-secondary border-opacity-25 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2 small text-secondary text-center text-sm-start">
                <div>
                    &copy; <?php echo date('Y'); ?> <strong>Chandila Web Development</strong>. All rights reserved.
                </div>
                <div class="d-none d-sm-block">
                    <span class="badge bg-dark border border-secondary text-warning">500+ Projects Milestone</span>
                    <span class="badge bg-dark border border-secondary text-light ms-1">Colombo, LK</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <?php echo renderFlashAlerts(); ?>

    <!-- Bootstrap 5.3.3 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toast Notification Scripts -->
    <script src="<?php echo BASE_URL; ?>/assets/js/toast.js"></script>
    <!-- Custom Public Main JS -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>
</body>
</html>
