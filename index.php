<?php
$pageTitle = 'Chandila Web Development – Where Innovation Meets Growth';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section with Integrated Quick Tracking (Light Theme) -->
<section class="hero-section">
    <!-- 3D Rotating Earth Background Animation Canvas -->
    <div class="earth-canvas-wrapper" aria-hidden="true">
        <canvas id="earthGlobeCanvas"></canvas>
    </div>

    <div class="container">
        <div class="row align-items-center g-4 g-lg-5">
            <!-- Left Hero Content -->
            <div class="col-lg-7 hero-content-col">
                <div class="hero-badge anim-fade-down">
                    <i class="fa-solid fa-trophy me-2 text-warning"></i> Over 500+ Successful Projects Delivered
                </div>
                <h1 class="hero-title anim-fade-up anim-delay-1">
                    Where Innovation <span class="text-gradient">Meets Growth</span>
                </h1>
                <p class="hero-subtitle anim-fade-up anim-delay-2">
                    At <strong>Chandila Web Development</strong>, we engineer tailored business websites, high-conversion e-commerce stores, cloud POS systems, and custom web portals with transparent milestone escrow and real-time customer WhatsApp tracking.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4 anim-fade-up anim-delay-3 hero-action-buttons">
                    <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-chandila-primary btn-lg shadow-sm">
                        <i class="fa-solid fa-paper-plane me-2"></i> Request a Project
                    </a>
                    <a href="<?php echo BASE_URL; ?>/portfolio.php" class="btn btn-outline-electric btn-lg">
                        <i class="fa-solid fa-laptop-code me-2"></i> View 500+ Portfolio
                    </a>
                </div>

                <!-- Trust Badges (Fully Responsive on All Screen Sizes) -->
                <div class="hero-trust-badges pt-3 mt-2 border-top border-secondary border-opacity-15 anim-fade-up anim-delay-4">
                    <div class="row g-2 g-md-3">
                        <div class="col-4">
                            <div class="trust-badge-card">
                                <div class="trust-badge-num text-dark">500+</div>
                                <div class="trust-badge-label">Delivered Projects</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="trust-badge-card">
                                <div class="trust-badge-num text-primary">100%</div>
                                <div class="trust-badge-label">WhatsApp Live</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="trust-badge-card">
                                <div class="trust-badge-num text-success">3-Stage</div>
                                <div class="trust-badge-label">Milestone Escrow</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Hero: Quick Track My Project Card -->
            <div class="col-lg-5">
                <div class="track-widget anim-slide-right anim-delay-2">
                    <div class="mb-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 36px; height: 36px; background: #eff6ff; color: #2563eb;">
                                <i class="fa-solid fa-crosshairs"></i>
                            </div>
                            <h4 class="mb-0 text-dark fw-bold">Track Your Project</h4>
                        </div>
                        <p class="text-secondary small mb-0 ms-1">Live sprint status, timeline & milestone progress</p>
                    </div>

                    <form id="quickTrackForm" action="<?php echo BASE_URL; ?>/track_project.php" method="GET">
                        <div class="mb-3">
                            <label for="quick_job_id" class="form-label text-dark small fw-semibold">
                                <i class="fa-solid fa-barcode text-primary me-1"></i> Enter Your Job ID:
                            </label>
                            <input type="text" class="form-control form-control-chandila" id="quick_job_id" name="job_id" placeholder="e.g. CHW-2026-0001" required>
                            <div class="form-text text-secondary small">Received via WhatsApp and email upon request.</div>
                        </div>

                        <div class="mb-4">
                            <label for="quick_phone" class="form-label text-dark small fw-semibold">
                                <i class="fa-solid fa-phone text-primary me-1"></i> Registered WhatsApp / Phone:
                            </label>
                            <input type="text" class="form-control form-control-chandila" id="quick_phone" name="phone" placeholder="e.g. 0768433340 or +9476843340" required>
                            <div class="form-text text-secondary small">Verified against registered phone for dual-factor privacy.</div>
                        </div>

                        <button type="submit" class="btn btn-chandila-primary w-100 py-3 fw-bold shadow-sm">
                            <i class="fa-solid fa-magnifying-glass me-2"></i> Track Progress Now
                        </button>
                    </form>

                    <div class="mt-3 p-2 rounded bg-light text-center small text-secondary border border-secondary border-opacity-15">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i> Customer data protected with encrypted dual verification.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Client Brands Carousel / Showcase Section (Featuring Real 500+ Projects) -->
<section class="py-4 bg-white border-bottom border-secondary border-opacity-10">
    <div class="container">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3 mb-3">
            <div>
                <span class="badge bg-warning text-dark font-monospace text-uppercase px-2 py-1">Trusted By 500+ Businesses</span>
                <span class="text-secondary small ms-2">Real deployed websites powered by Chandila Web Development:</span>
            </div>
            <a href="<?php echo BASE_URL; ?>/portfolio.php" class="text-primary fw-semibold small">
                Explore All 500+ Success Stories &rarr;
            </a>
        </div>

        <div class="row g-2 text-center">
            <?php 
            $featuredLive = [
                ['name' => 'Aqua Super', 'url' => 'https://aquasuper.lk', 'type' => 'E-Commerce'],
                ['name' => 'Arigato', 'url' => 'https://arigato.lk', 'type' => 'Retail Fashion'],
                ['name' => 'Ceylon Therapist', 'url' => 'https://ceylontherapist.lk', 'type' => 'Healthcare Booking'],
                ['name' => 'Chanu Gems', 'url' => 'https://chanugems.com', 'type' => 'Gems & Jewelry'],
                ['name' => 'Lesi POS', 'url' => 'https://lesipos.lk', 'type' => 'Cloud POS Engine'],
                ['name' => 'Lasana', 'url' => 'https://lasana.com', 'type' => 'Luxury Flowers'],
                ['name' => 'Himara Travels', 'url' => 'https://himaratravels.lk', 'type' => 'Tourism & Tours'],
                ['name' => 'WW Pawns', 'url' => 'https://wwpawns.com', 'type' => 'Financial Portal']
            ];
            foreach ($featuredLive as $fl): ?>
                <div class="col-6 col-md-3">
                    <a href="<?php echo $fl['url']; ?>" target="_blank" rel="noopener noreferrer" class="p-3 rounded-3 d-block text-decoration-none bg-light border border-secondary border-opacity-10 hover-shadow transition">
                        <div class="fw-bold text-dark mb-0"><?php echo htmlspecialchars($fl['name']); ?></div>
                        <span class="text-secondary small font-monospace" style="font-size: 0.75rem;"><?php echo htmlspecialchars($fl['type']); ?></span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 4-Step Project Lifecycle Section -->
<section class="py-5" style="background: #f8fafc;">
    <div class="container py-4">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning px-3 py-2 mb-2 fw-bold">
                TRANSPARENT WORKFLOW
            </span>
            <h2 class="text-dark fw-bold display-6">Our 4-Stage Project Lifecycle</h2>
            <p class="text-secondary">From initial specification to cloud hosting and official delivery, every step is tracked live.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="chandila-card h-100 text-center">
                    <div class="service-icon-box mx-auto">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <span class="badge bg-secondary mb-2">Stage 01</span>
                    <h5 class="text-dark mb-2">Submit Request</h5>
                    <p class="text-secondary small mb-0">Fill out our comprehensive project request form. A unique Job ID is generated and sent to your WhatsApp instantly.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="chandila-card h-100 text-center">
                    <div class="service-icon-box mx-auto">
                        <i class="fa-solid fa-handshake-angle"></i>
                    </div>
                    <span class="badge bg-primary mb-2">Stage 02</span>
                    <h5 class="text-dark mb-2">Advance & Confirm</h5>
                    <p class="text-secondary small mb-0">Admin reviews scope, agrees on project cost, and confirms advance payment before development officially starts.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="chandila-card h-100 text-center">
                    <div class="service-icon-box mx-auto">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <span class="badge bg-warning text-dark mb-2">Stage 03</span>
                    <h5 class="text-dark mb-2">Agile Development</h5>
                    <p class="text-secondary small mb-0">Dedicated developers build your system. Receive automated WhatsApp updates at 25%, 50%, and 80% milestones.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="chandila-card h-100 text-center">
                    <div class="service-icon-box mx-auto">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                    </div>
                    <span class="badge bg-success mb-2">Stage 04</span>
                    <h5 class="text-dark mb-2">Final & Launch</h5>
                    <p class="text-secondary small mb-0">Upon development completion and final payment verification, we deploy your project live to production hosting.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Services Preview -->
<section class="py-5 bg-white border-top border-bottom border-secondary border-opacity-10">
    <div class="container py-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-end mb-5">
            <div>
                <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning px-3 py-2 mb-2 fw-bold">
                    CORE SPECIALIZATIONS
                </span>
                <h2 class="text-dark fw-bold display-6">Web Solutions Built For Scale & Growth</h2>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="<?php echo BASE_URL; ?>/services.php" class="btn btn-outline-electric">
                    View All Services <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Service 1 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-briefcase"></i>
                    </div>
                    <h4 class="text-dark mb-2">Corporate & Business Websites</h4>
                    <p class="text-secondary small mb-3">
                        Ultra-fast, SEO-optimized business websites designed to build brand credibility, capture qualified leads, and represent your brand with perfection.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=Business+Website" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Service 2 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <h4 class="text-dark mb-2">E-Commerce Platforms</h4>
                    <p class="text-secondary small mb-3">
                        Full-scale online stores with multi-currency checkout, local payment gateways (PayHere, Commercial Bank, BOC, Stripe), order tracking, and stock management.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=E-commerce+Website" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Service 3 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-cash-register"></i>
                    </div>
                    <h4 class="text-dark mb-2">Cloud POS & Billing Systems</h4>
                    <p class="text-secondary small mb-3">
                        Integrated retail point-of-sale software like our live <a href="https://lesipos.lk" target="_blank" class="text-primary fw-bold">Lesi POS</a>, featuring barcode scanning, multi-branch stock transfers, and invoice printing.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=Custom+Web+Application" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Service 4 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-graduation-cap"></i>
                    </div>
                    <h4 class="text-dark mb-2">Student Management Systems</h4>
                    <p class="text-secondary small mb-3">
                        Institutional software featuring student registrations, batch schedules, attendance management, examination grading, fee vouchers, and parent notifications.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=Student+Management+System" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Service 5 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h4 class="text-dark mb-2">Online Booking Systems</h4>
                    <p class="text-secondary small mb-3">
                        Real-time appointment scheduling platforms for medical clinics, healthcare practitioners (like <a href="https://ceylontherapist.lk" target="_blank" class="text-primary fw-bold">Ceylon Therapist</a>), and holiday tours.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=Booking+System" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Service 6 -->
            <div class="col-lg-4 col-md-6">
                <div class="chandila-card h-100">
                    <div class="service-icon-box">
                        <i class="fa-solid fa-server"></i>
                    </div>
                    <h4 class="text-dark mb-2">Website Hosting & Deployment</h4>
                    <p class="text-secondary small mb-3">
                        End-to-end cloud server configuration, cPanel / DirectAdmin deployment, SSL installation, CDN speed acceleration, and 99.9% uptime maintenance.
                    </p>
                    <a href="<?php echo BASE_URL; ?>/request_project.php?type=Other" class="text-primary fw-semibold small">
                        Request Quote <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 1080x1080 Project & Social Highlights Showcase Section -->
<?php
// Query published featured posts
$featuredPosts = [];
$pdo = Database::getConnection();
if ($pdo) {
    try {
        $featuredPosts = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY is_featured DESC, created_at DESC LIMIT 3")->fetchAll();
    } catch (Exception $e) {
        $featuredPosts = [];
    }
}
?>
<?php if (!empty($featuredPosts)): ?>
<section class="py-5 bg-white border-top border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-5">
            <div>
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-2">
                    <i class="fa-solid fa-photo-film me-1"></i> 1080x1080 PROJECT HIGHLIGHTS & UPDATES
                </span>
                <h2 class="text-dark fw-bold mb-0">Latest Showcases & Social Posts</h2>
            </div>
            <a href="<?php echo BASE_URL; ?>/posts.php" class="btn btn-outline-dark fw-semibold">
                View All Showcases <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            <?php foreach ($featuredPosts as $fp): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100 border border-secondary border-opacity-25 rounded-4 overflow-hidden shadow-sm hover-elevate bg-white">
                        <div class="position-relative overflow-hidden bg-light" style="aspect-ratio: 1 / 1;">
                            <img src="<?php echo BASE_URL . '/' . htmlspecialchars($fp['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($fp['title']); ?>" 
                                 class="w-100 h-100 object-fit-cover transition-transform">
                            
                            <div class="position-absolute top-0 start-0 m-3">
                                <span class="badge bg-dark bg-opacity-75 text-warning font-monospace py-1 px-2 border border-warning border-opacity-50">
                                    1080x1080
                                </span>
                            </div>

                            <div class="position-absolute top-0 end-0 m-3">
                                <span class="badge bg-primary bg-opacity-90 text-white px-2 py-1">
                                    <?php echo htmlspecialchars($fp['category']); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card-body p-4 d-flex flex-column">
                            <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($fp['title']); ?></h5>
                            <p class="text-secondary small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars($fp['description']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <a href="<?php echo BASE_URL; ?>/posts.php" class="btn btn-sm btn-outline-dark px-3">
                                    <i class="fa-solid fa-expand me-1"></i> View Details
                                </a>
                                <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20saw%20your%20post%20about%20<?php echo urlencode($fp['title']); ?>%20and%20would%20like%20to%20discuss%20a%20similar%20project." target="_blank" class="btn btn-sm btn-success px-3 fw-bold">
                                    <i class="fa-brands fa-whatsapp me-1"></i> Inquire (076 843 3340)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Call to Action Banner (Clean Light with Gold Accent) -->
<section class="py-5 bg-light border-top border-secondary border-opacity-10">
    <div class="container py-4 text-center">
        <h2 class="text-dark fw-bold mb-3 display-6">Ready to Build Your Next Web Project?</h2>
        <p class="text-secondary mb-4 mx-auto fs-5" style="max-width: 600px;">
            Join our 500+ successful clients today. Submit your requirements and get an official Job ID with instant WhatsApp confirmation.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-paper-plane me-2"></i> Submit Project Requirement
            </a>
            <a href="https://wa.me/9476843340" target="_blank" class="btn btn-outline-success btn-lg">
                <i class="fa-brands fa-whatsapp me-2"></i> Chat with Us Directly (076 843 3340)
            </a>
        </div>
    </div>
</section>

<!-- Three.js Library & Realistic 3D Rotating Earth Globe Script -->
<script src="<?php echo BASE_URL; ?>/assets/js/three.min.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/earth_globe.js?v=<?php echo filemtime(__DIR__ . '/assets/js/earth_globe.js'); ?>"></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
