<?php
/**
 * Chandila Web Development – Admin Posts & Showcase Management (1080x1080 Square Format)
 */
$adminTitle = 'Posts & Showcase (1080x1080)';
require_once __DIR__ . '/../includes/admin_header.php';

// 1. Ensure posts table exists & auto-seed if empty
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
          INDEX `idx_post_category` (`category`),
          CONSTRAINT `fk_post_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // Check if table is empty, auto-seed default 6 showcase items
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
} catch (Exception $e) {}

$successMsg = '';
$errorMsg = '';

/**
 * Image processing helper: processes & center-crops/resizes image to 1080x1080 px
 */
function processSquare1080Image(string $sourceFile, string $destFile, string $ext): bool {
    if (!extension_loaded('gd')) {
        return move_uploaded_file($sourceFile, $destFile);
    }

    $ext = strtolower($ext);
    $srcImg = match ($ext) {
        'jpg', 'jpeg' => @imagecreatefromjpeg($sourceFile),
        'png'         => @imagecreatefrompng($sourceFile),
        'webp'        => @imagecreatefromwebp($sourceFile),
        default       => null
    };

    if (!$srcImg) {
        return move_uploaded_file($sourceFile, $destFile);
    }

    $origW = imagesx($srcImg);
    $origH = imagesy($srcImg);

    // Target dimensions: 1080x1080
    $targetSize = 1080;
    $dstImg = imagecreatetruecolor($targetSize, $targetSize);

    // Preserve alpha transparency for PNG/WebP
    if (in_array($ext, ['png', 'webp'])) {
        imagealphablending($dstImg, false);
        imagesavealpha($dstImg, true);
        $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
        imagefilledrectangle($dstImg, 0, 0, $targetSize, $targetSize, $transparent);
    } else {
        $bg = imagecolorallocate($dstImg, 255, 255, 255);
        imagefilledrectangle($dstImg, 0, 0, $targetSize, $targetSize, $bg);
    }

    // Calculate center-crop box to achieve perfect 1:1 square
    $minDim = min($origW, $origH);
    $srcX = (int)(($origW - $minDim) / 2);
    $srcY = (int)(($origH - $minDim) / 2);

    imagecopyresampled($dstImg, $srcImg, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $minDim, $minDim);

    $saved = match ($ext) {
        'jpg', 'jpeg' => imagejpeg($dstImg, $destFile, 92),
        'png'         => imagepng($dstImg, $destFile, 7),
        'webp'        => imagewebp($dstImg, $destFile, 90),
        default       => false
    };

    imagedestroy($srcImg);
    imagedestroy($dstImg);
    return $saved;
}

// 2. Handle Actions (Add, Edit, Delete, Toggle Status)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');

    // Add New 1080x1080 Post
    if ($action === 'create_post') {
        $title       = sanitize($_POST['title'] ?? '');
        $category    = sanitize($_POST['category'] ?? 'Project Showcase');
        $description = trim($_POST['description'] ?? '');
        $externalUrl = sanitize($_POST['external_url'] ?? '');
        $status      = sanitize($_POST['status'] ?? 'published');
        $isFeatured  = isset($_POST['is_featured']) ? 1 : 0;

        if (empty($title) || empty($description)) {
            $errorMsg = "Please provide both a Post Title and Description.";
        } elseif (empty($_FILES['post_image']['name'])) {
            $errorMsg = "Please select a 1080x1080 image for this post.";
        } else {
            $fileInfo = pathinfo($_FILES['post_image']['name']);
            $ext = strtolower($fileInfo['extension'] ?? '');
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($ext, $allowedExts)) {
                $errorMsg = "Only JPG, PNG, and WebP images are allowed.";
            } elseif ($_FILES['post_image']['size'] > 12 * 1024 * 1024) {
                $errorMsg = "Image exceeds 12MB limit.";
            } else {
                $postDir = ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'posts' . DIRECTORY_SEPARATOR;
                if (!is_dir($postDir)) {
                    mkdir($postDir, 0777, true);
                }

                $newFileName = 'post_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                $destPath = $postDir . $newFileName;
                $relPath = 'uploads/posts/' . $newFileName;

                if (processSquare1080Image($_FILES['post_image']['tmp_name'], $destPath, $ext)) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO `posts` (`title`, `category`, `image_path`, `description`, `external_url`, `aspect_ratio`, `status`, `is_featured`, `created_by`, `created_at`)
                        VALUES (?, ?, ?, ?, ?, '1080x1080', ?, ?, ?, NOW())
                    ");
                    $insertStmt->execute([
                        $title,
                        $category,
                        $relPath,
                        $description,
                        $externalUrl,
                        $status,
                        $isFeatured,
                        $_SESSION['user_id'] ?? null
                    ]);
                    $successMsg = "1080x1080 Square Post uploaded and published successfully!";
                } else {
                    $errorMsg = "Failed to process and save the uploaded image.";
                }
            }
        }
    }

    // Delete Post
    if ($action === 'delete_post') {
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($postId > 0) {
            $post = $pdo->prepare("SELECT image_path FROM posts WHERE id = ?");
            $post->execute([$postId]);
            $item = $post->fetch();
            if ($item) {
                if (!empty($item['image_path']) && file_exists(ROOT_PATH . DIRECTORY_SEPARATOR . $item['image_path'])) {
                    @unlink(ROOT_PATH . DIRECTORY_SEPARATOR . $item['image_path']);
                }
                $del = $pdo->prepare("DELETE FROM posts WHERE id = ?");
                $del->execute([$postId]);
                $successMsg = "Post deleted successfully.";
            }
        }
    }

    // Toggle Status
    if ($action === 'toggle_status') {
        $postId = (int)($_POST['post_id'] ?? 0);
        $newStatus = sanitize($_POST['new_status'] ?? 'published');
        if ($postId > 0) {
            $up = $pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
            $up->execute([$newStatus, $postId]);
            $successMsg = "Post visibility updated to " . ucfirst($newStatus) . ".";
        }
    }
}

// Fetch all posts
$posts = $pdo->query("SELECT * FROM posts ORDER BY is_featured DESC, created_at DESC")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="text-dark fw-bold mb-1">
            <i class="fa-solid fa-photo-film text-warning me-2"></i> Posts & Showcase Management
        </h3>
        <p class="text-secondary small mb-0">
            Upload <strong>1080x1080 square format</strong> posts with descriptions. Displayed on homepage and showcase gallery.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>/posts.php" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Live Showcase Feed
        </a>
        <button class="btn btn-gold btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#newPostCollapse">
            <i class="fa-solid fa-plus-circle me-1"></i> Upload New 1080x1080 Post
        </button>
    </div>
</div>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success border-success text-success bg-success bg-opacity-10 mb-4">
        <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errorMsg)): ?>
    <div class="alert alert-danger border-danger text-danger bg-danger bg-opacity-10 mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMsg); ?>
    </div>
<?php endif; ?>

<!-- 1080x1080 Upload Form (Collapsible) -->
<div class="collapse mb-4 <?php echo !empty($errorMsg) ? 'show' : ''; ?>" id="newPostCollapse">
    <div class="admin-card border border-warning border-opacity-50">
        <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
            <h4 class="text-dark fw-bold mb-0">
                <i class="fa-solid fa-cloud-arrow-up text-warning me-2"></i> Upload New 1080x1080 Post & Description
            </h4>
            <span class="badge bg-warning text-dark font-monospace fw-bold">1:1 ASPECT RATIO (1080 x 1080 PX)</span>
        </div>

        <form action="posts.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="create_post">

            <div class="row g-4">
                <!-- Left Column: Inputs -->
                <div class="col-lg-7">
                    <div class="mb-3">
                        <label for="title" class="form-label text-dark small fw-bold">Post Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" placeholder="e.g. JaffnaAds Regional Classifieds Platform Launch" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="category" class="form-label text-dark small fw-bold">Category / Tag</label>
                            <select class="form-select" id="category" name="category">
                                <option value="Project Showcase">Project Showcase</option>
                                <option value="Product Launch">Product Launch</option>
                                <option value="Company Milestone">Company Milestone</option>
                                <option value="Client Review">Client Review / Success</option>
                                <option value="Special Offer">Special Offer / Promotion</option>
                                <option value="Tech Announcement">Tech Announcement</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="status" class="form-label text-dark small fw-bold">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="published">Published (Visible Publicly)</option>
                                <option value="draft">Draft (Saved Internally)</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="external_url" class="form-label text-dark small fw-bold">Target / Project Website URL (Optional)</label>
                        <input type="url" class="form-control" id="external_url" name="external_url" placeholder="https://jaffnaads.lk or client site">
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label text-dark small fw-bold">Description / Caption <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" placeholder="Write a detailed description, features, client impact, tech stack used, or announcement details..." required></textarea>
                    </div>

                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" value="1" checked>
                        <label class="form-check-label text-dark small fw-semibold" for="is_featured">
                            Pin / Feature on Homepage Showcase Grid
                        </label>
                    </div>
                </div>

                <!-- Right Column: 1080x1080 Image Upload & Live Square Preview -->
                <div class="col-lg-5">
                    <label class="form-label text-dark small fw-bold">Upload 1080x1080 Image <span class="text-danger">*</span></label>
                    <input type="file" class="form-control mb-3" id="postImageInput" name="post_image" accept="image/png,image/jpeg,image/webp" required>
                    
                    <div class="text-secondary small mb-2">
                        <i class="fa-solid fa-circle-info text-info me-1"></i> Optimized for Instagram-style 1:1 square feeds (1080x1080 px). Auto-centered and fitted with zero distortion.
                    </div>

                    <!-- Live 1:1 Square Preview Box -->
                    <div class="border rounded-3 overflow-hidden bg-light text-center position-relative d-flex align-items-center justify-content-center" style="width: 100%; aspect-ratio: 1 / 1; max-width: 380px; margin: 0 auto;">
                        <img id="squareImagePreview" src="" alt="Preview" class="w-100 h-100 object-fit-cover d-none">
                        <div id="previewPlaceholder" class="p-4 text-secondary">
                            <i class="fa-solid fa-image fs-1 mb-2 opacity-50 d-block"></i>
                            <span class="small fw-semibold text-dark">1:1 Square Preview Box</span>
                            <div class="font-monospace small text-primary mt-1">1080 x 1080 px</div>
                            <div class="small text-muted mt-2">Select an image above to see live preview</div>
                        </div>
                    </div>
                    <div id="imageDimensionTag" class="text-center mt-2 small font-monospace text-primary d-none"></div>
                </div>

                <div class="col-12 text-end pt-3 border-top">
                    <button type="button" class="btn btn-secondary me-2" data-bs-toggle="collapse" data-bs-target="#newPostCollapse">Cancel</button>
                    <button type="submit" class="btn btn-gold px-4 fw-bold">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i> Save & Publish 1080x1080 Post
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Posts Showcase Grid (1:1 Square Cards) -->
<div class="row g-4 mb-4">
    <?php if (empty($posts)): ?>
        <div class="col-12">
            <div class="admin-card text-center py-5">
                <i class="fa-solid fa-photo-film fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                <h5 class="text-dark fw-bold">No 1080x1080 posts found</h5>
                <p class="text-secondary small mb-3">Click the "Upload New 1080x1080 Post" button above to publish your first post.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): 
            $statusClass = match($post['status']) {
                'published' => 'bg-success text-white',
                'draft'     => 'bg-secondary text-white',
                'archived'  => 'bg-dark text-muted border',
                default     => 'bg-info text-white'
            };
        ?>
            <div class="col-lg-4 col-md-6">
                <div class="admin-card h-100 d-flex flex-column p-3">
                    <!-- Square Image Container (1:1 Aspect Ratio) -->
                    <div class="position-relative rounded-3 overflow-hidden bg-black mb-3" style="aspect-ratio: 1 / 1;">
                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($post['image_path']); ?>" 
                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                             class="w-100 h-100 object-fit-cover">
                        
                        <div class="position-absolute top-0 start-0 m-2">
                            <span class="badge bg-dark bg-opacity-75 text-warning font-monospace border border-warning border-opacity-50">
                                1080x1080
                            </span>
                        </div>

                        <div class="position-absolute top-0 end-0 m-2">
                            <span class="badge <?php echo $statusClass; ?>">
                                <?php echo ucfirst($post['status']); ?>
                            </span>
                        </div>

                        <?php if ($post['is_featured']): ?>
                            <div class="position-absolute bottom-0 start-0 m-2">
                                <span class="badge bg-warning text-dark fw-bold">
                                    <i class="fa-solid fa-star me-1"></i> Featured on Home
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Post Info -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                            <?php echo htmlspecialchars($post['category']); ?>
                        </span>
                        <span class="text-secondary small">
                            <?php echo date('M d, Y', strtotime($post['created_at'])); ?>
                        </span>
                    </div>

                    <h5 class="text-dark fw-bold mb-2 text-truncate" title="<?php echo htmlspecialchars($post['title']); ?>">
                        <?php echo htmlspecialchars($post['title']); ?>
                    </h5>

                    <p class="text-secondary small mb-3 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                        <?php echo htmlspecialchars($post['description']); ?>
                    </p>

                    <?php if (!empty($post['external_url'])): ?>
                        <div class="mb-3">
                            <a href="<?php echo htmlspecialchars($post['external_url']); ?>" target="_blank" class="text-info small text-decoration-none">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> <?php echo htmlspecialchars($post['external_url']); ?>
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary border-opacity-25 mt-auto">
                        <form method="POST" action="posts.php" class="d-inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo ($post['status'] === 'published') ? 'draft' : 'published'; ?>">
                            <button type="submit" class="btn btn-sm <?php echo ($post['status'] === 'published') ? 'btn-outline-warning' : 'btn-outline-success'; ?>" title="Toggle Visibility">
                                <i class="fa-solid <?php echo ($post['status'] === 'published') ? 'fa-eye-slash' : 'fa-eye'; ?> me-1"></i>
                                <?php echo ($post['status'] === 'published') ? 'Unpublish' : 'Publish'; ?>
                            </button>
                        </form>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $post['id']; ?>" title="Quick View">
                                <i class="fa-solid fa-expand"></i>
                            </button>

                            <form method="POST" action="posts.php" onsubmit="return confirm('Are you sure you want to delete this post?');" class="d-inline">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Post">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Full View Modal -->
            <div class="modal fade" id="viewModal<?php echo $post['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title fw-bold text-dark"><?php echo htmlspecialchars($post['title']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4 text-dark">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="rounded-3 overflow-hidden bg-light border" style="aspect-ratio: 1 / 1;">
                                        <img src="<?php echo BASE_URL . '/' . htmlspecialchars($post['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['title']); ?>" 
                                             class="w-100 h-100 object-fit-cover">
                                    </div>
                                    <div class="text-center text-muted small mt-2 font-monospace">
                                        Resolution: 1080 x 1080 px (1:1 Square)
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex flex-column">
                                    <div class="d-flex gap-2 align-items-center mb-3">
                                        <span class="badge bg-primary"><?php echo htmlspecialchars($post['category']); ?></span>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($post['status']); ?></span>
                                    </div>

                                    <h4 class="fw-bold text-dark mb-3"><?php echo htmlspecialchars($post['title']); ?></h4>

                                    <div class="p-3 bg-light rounded border text-dark small mb-3 flex-grow-1">
                                        <?php echo nl2br(htmlspecialchars($post['description'])); ?>
                                    </div>

                                    <?php if (!empty($post['external_url'])): ?>
                                        <div class="mb-3">
                                            <a href="<?php echo htmlspecialchars($post['external_url']); ?>" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Visit Client / Project Site
                                            </a>
                                        </div>
                                    <?php endif; ?>

                                    <div class="text-secondary small">
                                        Created: <?php echo date('M d, Y h:i A', strtotime($post['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-top">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Live 1:1 Preview JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('postImageInput');
    const previewImg = document.getElementById('squareImagePreview');
    const placeholder = document.getElementById('previewPlaceholder');
    const tag = document.getElementById('imageDimensionTag');

    if (fileInput) {
        fileInput.addEventListener('change', function (e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    previewImg.src = event.target.result;
                    previewImg.classList.remove('d-none');
                    placeholder.classList.add('d-none');

                    // Detect natural dimensions
                    const temp = new Image();
                    temp.src = event.target.result;
                    temp.onload = function () {
                        tag.classList.remove('d-none');
                        tag.innerHTML = '<i class="fa-solid fa-check-circle text-success me-1"></i> Original: ' + this.naturalWidth + 'x' + this.naturalHeight + 'px (Processed to 1080x1080)';
                    };
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

