<?php
/**
 * Chandila Web Development – Public Posts & Project Highlights (1080x1080 Showcase)
 * Timezone: Asia/Colombo (Sri Lanka)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Project Highlights & Posts – Chandila Web Development";

$pdo = Database::getConnection();

// Ensure posts table exists & seed if needed
$posts = [];
if ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `posts` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `title` VARCHAR(255) NOT NULL,
              `category` VARCHAR(100) NOT NULL DEFAULT 'Project Showcase',
              `image_path` VARCHAR(255) NOT NULL,
              `description` TEXT NOT NULL,
              `external_url` VARCHAR(255) DEFAULT NULL,
              `aspect_ratio` VARCHAR(20) NOT NULL DEFAULT '1080x1080',
              `status` ENUM('published', 'draft', 'archived') NOT NULL DEFAULT 'published',
              `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
              `views_count` INT(11) NOT NULL DEFAULT 0,
              `created_by` INT(11) DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_post_status` (`status`),
              INDEX `idx_post_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $count = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
        if ($count === 0) {
            $pdo->exec("
                INSERT INTO `posts` (`id`, `title`, `category`, `image_path`, `description`, `external_url`, `aspect_ratio`, `status`, `is_featured`, `created_by`, `created_at`) VALUES
                (1, 'Aqua Super – Next-Gen Water Solutions E-Commerce', 'Project Showcase', 'uploads/posts/post_aquasuper.png', 'Engineered a lightning-fast responsive e-commerce web platform for Aqua Super with real-time inventory management, automated order routing, and secure online checkout. Resulted in 180% growth in online inquiries.', 'https://aquasuper.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 5 DAY),
                (2, 'Jaffna Ads – High-Traffic Regional Classifieds Portal', 'Project Showcase', 'uploads/posts/post_jaffnaads.png', 'Engineered high-scale classified advertising platform serving Northern Sri Lanka with multi-category listings, instant WhatsApp contact integration, location-based filtering, and automated ad expiration schedules.', 'https://jaffnaads.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 4 DAY),
                (3, 'Chanu Gems – Luxury Gemstone & International Catalog', 'Project Showcase', 'uploads/posts/post_chanugems.png', 'Bespoke gemstone and precious jewelry luxury catalog featuring high-definition zoom galleries, multi-currency display (USD, EUR, LKR), and direct VIP buyer WhatsApp reservation engine.', 'https://chanugems.com', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 3 DAY),
                (4, 'Ceylon Therapist – Healthcare & Therapy Booking Platform', 'Project Showcase', 'uploads/posts/post_ceylontherapist.png', 'Complete patient appointment reservation and therapist schedule management system with SMS/WhatsApp notifications and automated timeslot availability calendar.', 'https://ceylontherapist.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 2 DAY),
                (5, 'Lesi POS – Cloud Restaurant & Retail Management', 'Product Launch', 'uploads/posts/post_lesipos.png', 'Full-stack cloud-native Point of Sale system supporting thermal bill printing, barcode scanning, shift registers, and granular real-time profit/loss reporting.', 'https://lesipos.lk', '1080x1080', 'published', 1, 1, NOW() - INTERVAL 1 DAY),
                (6, 'Chandila 500+ Milestone: Digital Transformation Sri Lanka', 'Company Milestone', 'uploads/posts/post_milestone500.png', 'Celebrating over 500+ commercial websites and bespoke web applications successfully deployed worldwide! We thank all our clients, partners, and our engineering team for driving innovation.', 'https://chandilaweb.com', '1080x1080', 'published', 1, 1, NOW());
            ");
        }

        $category = sanitize($_GET['category'] ?? '');
        if (!empty($category)) {
            $stmt = $pdo->prepare("SELECT * FROM posts WHERE status = 'published' AND category = ? ORDER BY is_featured DESC, created_at DESC");
            $stmt->execute([$category]);
            $posts = $stmt->fetchAll();
        } else {
            $posts = $pdo->query("SELECT * FROM posts WHERE status = 'published' ORDER BY is_featured DESC, created_at DESC")->fetchAll();
        }
    } catch (Exception $e) {
        $posts = [];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Showcase Hero Header -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10 position-relative overflow-hidden">
    <div class="container py-4 text-center">
        <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-3">
            <i class="fa-solid fa-photo-film me-1"></i> OFFICIAL SOCIAL & PROJECT FEED (1080 x 1080)
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            Project Highlights & <span class="text-gradient">Featured Posts</span>
        </h1>
        <p class="text-secondary mx-auto fs-5 mb-4" style="max-width: 720px;">
            Explore our square-format project launches, engineering milestones, and client success stories from over 500+ systems delivered across Sri Lanka and globally.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20am%20inquiring%20about%20your%20project%20showcase." target="_blank" class="btn btn-outline-success btn-lg">
                <i class="fa-brands fa-whatsapp me-2"></i> Inquire on WhatsApp: 076 843 3340
            </a>
            <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-paper-plane me-2"></i> Request Your Project
            </a>
        </div>
    </div>
</section>

<!-- Filter Pills -->
<section class="py-4 bg-light border-bottom border-secondary border-opacity-10">
    <div class="container">
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <?php 
            $currentCat = $_GET['category'] ?? '';
            $cats = [
                '' => 'All Showcases',
                'Project Showcase' => 'Project Showcases',
                'Product Launch' => 'Product Launches',
                'Company Milestone' => 'Company Milestones',
                'Client Review' => 'Client Success',
                'Special Offer' => 'Special Offers'
            ];
            foreach ($cats as $key => $lbl):
                $active = ($currentCat === $key) ? 'btn-chandila-primary' : 'btn-outline-secondary bg-white';
            ?>
                <a href="<?php echo BASE_URL . '/posts.php' . ($key ? '?category=' . urlencode($key) : ''); ?>" class="btn btn-sm <?php echo $active; ?> px-3 py-2 fw-semibold shadow-sm">
                    <?php echo $lbl; ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 1080x1080 Square Posts Grid -->
<section class="py-5 bg-white">
    <div class="container py-2">
        <div class="row g-4">
            <?php if (empty($posts)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fa-solid fa-images fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                    <h4 class="text-dark fw-bold">No posts in this category yet.</h4>
                    <p class="text-secondary small">Check back soon for new project highlights and announcements.</p>
                </div>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 border border-secondary border-opacity-25 rounded-4 overflow-hidden shadow-sm hover-elevate bg-white">
                            <!-- 1:1 Aspect Ratio Square Image -->
                            <div class="position-relative overflow-hidden bg-light" style="aspect-ratio: 1 / 1;">
                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($post['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                     class="w-100 h-100 object-fit-cover transition-transform">
                                
                                <div class="position-absolute top-0 start-0 m-3">
                                    <span class="badge bg-dark bg-opacity-75 text-warning font-monospace py-1 px-2 border border-warning border-opacity-50">
                                        1080x1080
                                    </span>
                                </div>

                                <div class="position-absolute top-0 end-0 m-3">
                                    <span class="badge bg-primary bg-opacity-90 text-white px-2 py-1">
                                        <?php echo htmlspecialchars($post['category']); ?>
                                    </span>
                                </div>

                                <?php if ($post['is_featured']): ?>
                                    <div class="position-absolute bottom-0 start-0 m-3">
                                        <span class="badge bg-warning text-dark fw-bold px-2 py-1">
                                            <i class="fa-solid fa-award me-1"></i> Featured
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Card Body -->
                            <div class="card-body p-4 d-flex flex-column">
                                <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($post['title']); ?></h5>
                                
                                <p class="text-secondary small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?php echo htmlspecialchars($post['description']); ?>
                                </p>

                                <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                    <button type="button" class="btn btn-sm btn-outline-dark px-3" data-bs-toggle="modal" data-bs-target="#postModal<?php echo $post['id']; ?>">
                                        <i class="fa-solid fa-expand me-1"></i> Read Details
                                    </button>

                                    <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20saw%20your%20post%20about%20<?php echo urlencode($post['title']); ?>%20and%20would%20like%20to%20discuss%20a%20similar%20project." target="_blank" class="btn btn-sm btn-success px-3 fw-bold" title="Inquire on WhatsApp">
                                        <i class="fa-brands fa-whatsapp me-1"></i> Inquire
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal for Full 1080x1080 Post Preview -->
                    <div class="modal fade" id="postModal<?php echo $post['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                                <div class="modal-header bg-light border-bottom border-secondary border-opacity-10">
                                    <h5 class="modal-title fw-bold text-dark mb-0"><?php echo htmlspecialchars($post['title']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="row g-4 align-items-center">
                                        <div class="col-md-6">
                                            <div class="rounded-3 overflow-hidden shadow-sm bg-black" style="aspect-ratio: 1 / 1;">
                                                <img src="<?php echo BASE_URL . '/' . htmlspecialchars($post['image_path']); ?>" 
                                                     alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                                     class="w-100 h-100 object-fit-cover">
                                            </div>
                                            <div class="text-center text-muted small mt-2 font-monospace">
                                                Square Social Format • 1080 x 1080 px
                                            </div>
                                        </div>
                                        <div class="col-md-6 d-flex flex-column">
                                            <div class="mb-3">
                                                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill">
                                                    <?php echo htmlspecialchars($post['category']); ?>
                                                </span>
                                                <span class="text-muted small ms-2">
                                                    Published: <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                                                </span>
                                            </div>

                                            <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($post['title']); ?></h4>

                                            <div class="text-secondary mb-4" style="line-height: 1.6;">
                                                <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                                            </div>

                                            <div class="d-flex flex-column gap-2 mt-auto">
                                                <?php if (!empty($post['external_url'])): ?>
                                                    <a href="<?php echo htmlspecialchars($post['external_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Visit Client / Live System
                                                    </a>
                                                <?php endif; ?>

                                                <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20am%20interested%20in%20a%20project%20like%20<?php echo urlencode($post['title']); ?>." target="_blank" class="btn btn-success fw-bold">
                                                    <i class="fa-brands fa-whatsapp me-2"></i> Inquire on WhatsApp: 076 843 3340
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

