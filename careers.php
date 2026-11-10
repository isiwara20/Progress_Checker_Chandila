<?php
/**
 * Chandila Web Development – Public Careers Page
 * Timezone: Asia/Colombo (Sri Lanka)
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = "Careers – Join Chandila Web Development";
$successMessage = '';
$errorMessage = '';
$applicationNo = '';

$pdo = Database::getConnection();

// Ensure career_applications table exists
if ($pdo) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `career_applications` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `application_no` VARCHAR(50) NOT NULL UNIQUE,
              `full_name` VARCHAR(120) NOT NULL,
              `email` VARCHAR(120) NOT NULL,
              `phone` VARCHAR(30) NOT NULL,
              `position` VARCHAR(150) NOT NULL,
              `experience` VARCHAR(50) NOT NULL,
              `portfolio_url` VARCHAR(255) DEFAULT NULL,
              `expected_salary` VARCHAR(100) DEFAULT NULL,
              `cover_letter` TEXT DEFAULT NULL,
              `resume_file` VARCHAR(255) DEFAULT NULL,
              `status` ENUM('New', 'Reviewed', 'Shortlisted', 'Interviewed', 'Rejected', 'Hired') NOT NULL DEFAULT 'New',
              `admin_notes` TEXT DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              INDEX `idx_career_app_no` (`application_no`),
              INDEX `idx_career_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (Exception $e) {
        // Table create check handled
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_job') {
    $fullName        = sanitize($_POST['full_name'] ?? '');
    $email           = sanitize($_POST['email'] ?? '');
    $phone           = sanitize($_POST['phone'] ?? '');
    $position        = sanitize($_POST['position'] ?? '');
    $experience      = sanitize($_POST['experience'] ?? '');
    $portfolioUrl    = sanitize($_POST['portfolio_url'] ?? '');
    $expectedSalary  = sanitize($_POST['expected_salary'] ?? '');
    $coverLetter     = sanitize($_POST['cover_letter'] ?? '');

    if (empty($fullName) || empty($email) || empty($phone) || empty($position)) {
        $errorMessage = "Please fill in all mandatory fields marked with an asterisk (*).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        $resumeFilePath = null;
        
        // Handle Resume Upload
        if (!empty($_FILES['resume']['name'])) {
            $resumeDir = __DIR__ . '/uploads/resumes/';
            if (!is_dir($resumeDir)) {
                mkdir($resumeDir, 0777, true);
            }

            $allowedExts = ['pdf', 'doc', 'docx'];
            $fileInfo = pathinfo($_FILES['resume']['name']);
            $fileExt = strtolower($fileInfo['extension'] ?? '');

            if (!in_array($fileExt, $allowedExts)) {
                $errorMessage = "Invalid resume format. Only PDF, DOC, and DOCX files up to 10MB are permitted.";
            } elseif ($_FILES['resume']['size'] > MAX_UPLOAD_SIZE) {
                $errorMessage = "Resume file exceeds the 10MB file limit.";
            } else {
                $cleanName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fileInfo['filename']);
                $newFileName = 'resume_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $destPath = $resumeDir . $newFileName;

                if (move_uploaded_file($_FILES['resume']['tmp_name'], $destPath)) {
                    $resumeFilePath = 'uploads/resumes/' . $newFileName;
                } else {
                    $errorMessage = "Failed to upload resume file. Please try again.";
                }
            }
        }

        if (empty($errorMessage)) {
            // Generate Application Number: CAR-2026-XXXX
            $randomPart = strtoupper(bin2hex(random_bytes(2)));
            $applicationNo = 'CAR-' . date('Y') . '-' . rand(1000, 9999);

            if ($pdo) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO `career_applications` 
                        (`application_no`, `full_name`, `email`, `phone`, `position`, `experience`, `portfolio_url`, `expected_salary`, `cover_letter`, `resume_file`, `status`, `created_at`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'New', NOW())
                    ");
                    $stmt->execute([
                        $applicationNo,
                        $fullName,
                        $email,
                        $phone,
                        $position,
                        $experience,
                        $portfolioUrl,
                        $expectedSalary,
                        $coverLetter,
                        $resumeFilePath
                    ]);

                    $successMessage = "Congratulations! Your application has been successfully submitted under Reference ID <strong>{$applicationNo}</strong>. Our engineering leads will review your portfolio and reach out via WhatsApp/Phone.";
                } catch (Exception $e) {
                    $errorMessage = "Could not save your application. Database error: " . $e->getMessage();
                }
            } else {
                $errorMessage = "Database is currently unreachable. Please connect directly via WhatsApp at 076 843 3340.";
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Careers Hero Banner -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10 position-relative overflow-hidden">
    <div class="container py-4 text-center">
        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2 rounded-pill mb-3">
            <i class="fa-solid fa-rocket me-1"></i> WE ARE HIRING CREATIVE MINDS
        </span>
        <h1 class="display-5 fw-bold text-dark mb-3">
            Build Your Career at <span class="text-gradient">Chandila Web Development</span>
        </h1>
        <p class="text-secondary mx-auto fs-5 mb-4" style="max-width: 720px;">
            Where Innovation Meets Growth. Join an agile engineering team delivering scalable, high-performance web systems, e-commerce stores, and cloud applications for 500+ clients across Sri Lanka and globally.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="#openPositions" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-briefcase me-2"></i> View Open Positions
            </a>
            <a href="#applicationForm" class="btn btn-outline-dark btn-lg">
                <i class="fa-solid fa-paper-plane me-2"></i> Submit Resume
            </a>
            <a href="https://wa.me/9476843340?text=Hello%20Chandila%20HR,%20I%20am%20interested%20in%20career%20opportunities%20at%20Chandila%20Web%20Development." target="_blank" class="btn btn-outline-success btn-lg">
                <i class="fa-brands fa-whatsapp me-2"></i> Chat HR: 076 843 3340
            </a>
        </div>
    </div>
</section>

<!-- Company Perks & Culture -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="text-dark fw-bold">Why You Will Love Working Here</h2>
            <p class="text-secondary">We believe great developers build exceptional products when given autonomy, trust, and modern tools.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 52px; height: 52px; background: #eff6ff; color: #2563eb; font-size: 1.4rem;">
                        <i class="fa-solid fa-laptop-code"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Modern Tech Stack</h5>
                    <p class="text-secondary small mb-0">
                        Work with PHP 8+, MySQL, REST APIs, Bootstrap 5, Vue/React, PayHere, Stripe, and cloud automation without legacy constraints.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 52px; height: 52px; background: #fef3c7; color: #d97706; font-size: 1.4rem;">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Milestone Bonuses</h5>
                    <p class="text-secondary small mb-0">
                        Competitive base salary plus direct project milestone bonuses for timely delivery and clean, maintainable architecture.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-3" style="width: 52px; height: 52px; background: #ecfdf5; color: #059669; font-size: 1.4rem;">
                        <i class="fa-solid fa-house-laptop"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Flexible & Hybrid Work</h5>
                    <p class="text-secondary small mb-0">
                        Flexible Colombo office hours paired with remote work days so you maintain work-life balance and creative energy.
                    </p>
                </div>
            </div>

            <!-- Engineering Culture Showcase Graphic (High-Res Stock Photo) -->
            <div class="col-12 mt-4">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative hover-elevate">
                    <img src="<?php echo BASE_URL; ?>/assets/images/careers_culture.jpg" alt="Chandila Engineering Culture" class="img-fluid w-100 object-fit-cover" style="max-height: 380px; min-height: 250px;">
                    <div class="position-absolute bottom-0 start-0 m-4 p-3 bg-dark bg-opacity-90 rounded-3 text-white border border-info shadow">
                        <div class="h5 fw-bold text-info mb-1"><i class="fa-solid fa-laptop-code me-2"></i> Agile Development Culture</div>
                        <div class="small text-light">Pair Programming • Modern Tools • Milestone Bonuses • Colombo, Sri Lanka</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Open Positions Section -->
<section id="openPositions" class="py-5 bg-white border-top border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-5">
            <div>
                <span class="badge bg-warning text-dark fw-bold px-3 py-2 rounded-pill mb-2">CURRENT OPPORTUNITIES</span>
                <h2 class="text-dark fw-bold mb-0">Explore Available Roles</h2>
            </div>
            <span class="text-secondary">5 Active Openings in Colombo & Remote</span>
        </div>

        <div class="row g-4">
            <!-- Job 1 -->
            <div class="col-lg-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm hover-elevate">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-primary bg-opacity-10 text-primary mb-2">FULL TIME / HYBRID</span>
                            <h4 class="fw-bold text-dark mb-1">Full-Stack Web Developer</h4>
                            <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Colombo 03 / Hybrid</p>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2">Urgent</span>
                    </div>
                    <p class="text-secondary small mb-3">
                        Develop and scale core web applications, e-commerce engines, and custom portal APIs using PHP, MySQL, Bootstrap 5, and JavaScript.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark border">PHP 8+</span>
                        <span class="badge bg-light text-dark border">MySQL</span>
                        <span class="badge bg-light text-dark border">RESTful APIs</span>
                        <span class="badge bg-light text-dark border">Bootstrap 5</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-muted small fw-semibold">Experience: 1 - 4 Years</span>
                        <a href="#applicationForm" class="btn btn-chandila-primary btn-sm px-3 select-role-btn" data-role="Full-Stack Web Developer">
                            Apply for this Role <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Job 2 -->
            <div class="col-lg-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm hover-elevate">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-info bg-opacity-10 text-info mb-2">FULL TIME / REMOTE</span>
                            <h4 class="fw-bold text-dark mb-1">Frontend UI/UX Developer</h4>
                            <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Remote / Sri Lanka</p>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">Open</span>
                    </div>
                    <p class="text-secondary small mb-3">
                        Craft pixel-perfect, responsive web interfaces, interactive dashboards with Chart.js, and smooth micro-animations.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark border">HTML5 & CSS3</span>
                        <span class="badge bg-light text-dark border">Bootstrap 5</span>
                        <span class="badge bg-light text-dark border">JavaScript (ES6+)</span>
                        <span class="badge bg-light text-dark border">UI Prototyping</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-muted small fw-semibold">Experience: 1 - 3 Years</span>
                        <a href="#applicationForm" class="btn btn-chandila-primary btn-sm px-3 select-role-btn" data-role="Frontend UI/UX Developer">
                            Apply for this Role <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Job 3 -->
            <div class="col-lg-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm hover-elevate">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-warning bg-opacity-10 text-warning mb-2">FULL TIME</span>
                            <h4 class="fw-bold text-dark mb-1">E-Commerce & WordPress Specialist</h4>
                            <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Colombo / Hybrid</p>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-2">Open</span>
                    </div>
                    <p class="text-secondary small mb-3">
                        Build and customize high-converting WooCommerce online stores, payment gateways, and custom WordPress plugins.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark border">WooCommerce</span>
                        <span class="badge bg-light text-dark border">Custom Themes</span>
                        <span class="badge bg-light text-dark border">PayHere / Stripe</span>
                        <span class="badge bg-light text-dark border">Speed Optimization</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-muted small fw-semibold">Experience: 1 - 3 Years</span>
                        <a href="#applicationForm" class="btn btn-chandila-primary btn-sm px-3 select-role-btn" data-role="E-Commerce & WordPress Specialist">
                            Apply for this Role <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Job 4 -->
            <div class="col-lg-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm hover-elevate">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <span class="badge bg-success bg-opacity-10 text-success mb-2">INTERNSHIP (PAID)</span>
                            <h4 class="fw-bold text-dark mb-1">Associate / Intern Web Developer</h4>
                            <p class="text-muted small mb-0"><i class="fa-solid fa-location-dot me-1 text-danger"></i> Colombo 03</p>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-2">Hiring</span>
                    </div>
                    <p class="text-secondary small mb-3">
                        Designed for undergraduates or recent IT graduates seeking hands-on industry experience building live commercial web systems under senior mentorship.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-light text-dark border">Core PHP</span>
                        <span class="badge bg-light text-dark border">SQL Queries</span>
                        <span class="badge bg-light text-dark border">HTML & CSS</span>
                        <span class="badge bg-light text-dark border">Quick Learner</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <span class="text-muted small fw-semibold">Experience: Freshers Welcome</span>
                        <a href="#applicationForm" class="btn btn-chandila-primary btn-sm px-3 select-role-btn" data-role="Associate / Intern Web Developer">
                            Apply for this Role <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Job Application Form Section -->
<section id="applicationForm" class="py-5 bg-light border-top border-secondary border-opacity-10">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill mb-2">SUBMIT APPLICATION</span>
                        <h3 class="fw-bold text-dark">Send Your Resume to Chandila HR</h3>
                        <p class="text-secondary small">Direct inquiries & fast-track review via WhatsApp at <strong>076 843 3340</strong></p>
                    </div>

                    <?php if (!empty($successMessage)): ?>
                        <div class="alert alert-success border-success text-success bg-success bg-opacity-10 p-4 rounded-3 mb-4">
                            <div class="d-flex align-items-start gap-3">
                                <i class="fa-solid fa-circle-check fs-2 text-success mt-1"></i>
                                <div>
                                    <h5 class="fw-bold text-success mb-1">Application Received Successfully!</h5>
                                    <p class="mb-3"><?php echo $successMessage; ?></p>
                                    <a href="https://wa.me/9476843340?text=Hello%20Chandila%20HR,%20I%20have%20submitted%20my%20job%20application%20under%20Ref:%20<?php echo urlencode($applicationNo); ?>" target="_blank" class="btn btn-success btn-sm fw-bold">
                                        <i class="fa-brands fa-whatsapp me-1"></i> Confirm with HR via WhatsApp (076 843 3340)
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger border-danger text-danger bg-danger bg-opacity-10 p-3 rounded-3 mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo BASE_URL; ?>/careers.php#applicationForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="apply_job">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="full_name" class="form-label text-dark small fw-semibold">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-chandila" id="full_name" name="full_name" placeholder="e.g. Kasun Silva" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label text-dark small fw-semibold">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-chandila" id="email" name="email" placeholder="e.g. kasun@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label text-dark small fw-semibold">WhatsApp / Phone Number <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border text-success"><i class="fa-brands fa-whatsapp"></i></span>
                                    <input type="text" class="form-control form-control-chandila" id="phone" name="phone" placeholder="e.g. 076 843 3340" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="form-text text-muted small">We communicate directly via WhatsApp for interview scheduling.</div>
                            </div>

                            <div class="col-md-6">
                                <label for="position" class="form-label text-dark small fw-semibold">Position Applying For <span class="text-danger">*</span></label>
                                <select class="form-select form-control-chandila" id="position" name="position" required>
                                    <option value="">-- Choose a Role --</option>
                                    <option value="Full-Stack Web Developer">Full-Stack Web Developer (PHP / MySQL / JS)</option>
                                    <option value="Frontend UI/UX Developer">Frontend UI/UX Developer (Bootstrap 5 / CSS)</option>
                                    <option value="E-Commerce & WordPress Specialist">E-Commerce & WordPress Specialist</option>
                                    <option value="Associate / Intern Web Developer">Associate / Intern Web Developer (Paid)</option>
                                    <option value="QA & Testing Specialist">QA & Testing Specialist</option>
                                    <option value="Other / General Application">Other / General Technical Application</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="experience" class="form-label text-dark small fw-semibold">Years of Relevant Experience <span class="text-danger">*</span></label>
                                <select class="form-select form-control-chandila" id="experience" name="experience" required>
                                    <option value="Fresher / Undergrad">Student / Fresh Graduate</option>
                                    <option value="1 - 2 Years">1 - 2 Years</option>
                                    <option value="3 - 5 Years">3 - 5 Years (Mid Level)</option>
                                    <option value="5+ Years">5+ Years (Senior Lead)</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="portfolio_url" class="form-label text-dark small fw-semibold">Portfolio / GitHub / LinkedIn URL</label>
                                <input type="url" class="form-control form-control-chandila" id="portfolio_url" name="portfolio_url" placeholder="https://github.com/yourprofile" value="<?php echo htmlspecialchars($_POST['portfolio_url'] ?? ''); ?>">
                            </div>

                            <div class="col-md-12">
                                <label for="resume" class="form-label text-dark small fw-semibold">Upload Resume / CV (PDF, DOC, DOCX - Max 10MB)</label>
                                <input type="file" class="form-control form-control-chandila" id="resume" name="resume" accept=".pdf,.doc,.docx">
                                <div class="form-text text-muted small">Please attach your most recent CV with project references.</div>
                            </div>

                            <div class="col-md-12">
                                <label for="cover_letter" class="form-label text-dark small fw-semibold">Short Introduction & Key Skills</label>
                                <textarea class="form-control form-control-chandila" id="cover_letter" name="cover_letter" rows="4" placeholder="Highlight your favorite projects, tech stack experience, and why you would like to build with Chandila Web Development..."><?php echo htmlspecialchars($_POST['cover_letter'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12 mt-4 text-center">
                                <button type="submit" class="btn btn-gold btn-lg px-5 fw-bold shadow-sm">
                                    <i class="fa-solid fa-paper-plane me-2"></i> Submit My Application
                                </button>
                                <div class="text-muted small mt-2">
                                    Need urgent assistance? WhatsApp our hiring lead at <strong>076 843 3340</strong>.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Auto Select Role Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleButtons = document.querySelectorAll('.select-role-btn');
    const positionSelect = document.getElementById('position');

    roleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const role = this.getAttribute('data-role');
            if (positionSelect && role) {
                positionSelect.value = role;
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

