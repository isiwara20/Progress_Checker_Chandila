<?php
$adminTitle = 'Career Applicants';
require_once __DIR__ . '/../includes/admin_header.php';

// Ensure table exists
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
} catch (Exception $e) {}

$successMsg = '';
$errorMsg = '';

// Handle Status or Note Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_applicant_status') {
    $applicantId = (int)($_POST['applicant_id'] ?? 0);
    $newStatus   = sanitize($_POST['status'] ?? 'New');
    $notes       = sanitize($_POST['admin_notes'] ?? '');

    if ($applicantId > 0) {
        $updateStmt = $pdo->prepare("UPDATE career_applications SET status = ?, admin_notes = ? WHERE id = ?");
        $updateStmt->execute([$newStatus, $notes, $applicantId]);
        $successMsg = "Applicant status updated successfully.";
    }
}

// Fetch applicants
$statusFilter = sanitize($_GET['status'] ?? '');
if (!empty($statusFilter)) {
    $stmt = $pdo->prepare("SELECT * FROM career_applications WHERE status = ? ORDER BY created_at DESC");
    $stmt->execute([$statusFilter]);
    $applicants = $stmt->fetchAll();
} else {
    $applicants = $pdo->query("SELECT * FROM career_applications ORDER BY created_at DESC")->fetchAll();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-user-tie text-primary me-2"></i> Career Applicants</h3>
        <p class="text-secondary small mb-0">Total Applicants: <strong><?php echo count($applicants); ?></strong> • Review developer submissions and schedule interviews</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>/careers.php" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> View Public Careers Page
        </a>
    </div>
</div>

<?php if (!empty($successMsg)): ?>
    <div class="alert alert-success border-success text-success bg-success bg-opacity-10 mb-4">
        <i class="fa-solid fa-circle-check me-2"></i> <?php echo htmlspecialchars($successMsg); ?>
    </div>
<?php endif; ?>

<!-- Status Filter Tabs -->
<div class="mb-4 d-flex gap-2 flex-wrap">
    <a href="careers.php" class="btn btn-sm <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-dark'; ?>">All Applicants</a>
    <a href="careers.php?status=New" class="btn btn-sm <?php echo ($statusFilter === 'New') ? 'btn-primary' : 'btn-dark'; ?>">New</a>
    <a href="careers.php?status=Shortlisted" class="btn btn-sm <?php echo ($statusFilter === 'Shortlisted') ? 'btn-primary' : 'btn-dark'; ?>">Shortlisted</a>
    <a href="careers.php?status=Interviewed" class="btn btn-sm <?php echo ($statusFilter === 'Interviewed') ? 'btn-primary' : 'btn-dark'; ?>">Interviewed</a>
    <a href="careers.php?status=Hired" class="btn btn-sm <?php echo ($statusFilter === 'Hired') ? 'btn-primary' : 'btn-dark'; ?>">Hired</a>
    <a href="careers.php?status=Rejected" class="btn btn-sm <?php echo ($statusFilter === 'Rejected') ? 'btn-primary' : 'btn-dark'; ?>">Rejected</a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Candidate</th>
                    <th>Position Applied</th>
                    <th>Experience</th>
                    <th>WhatsApp / Phone</th>
                    <th>Resume / Portfolio</th>
                    <th>Status</th>
                    <th>Applied Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($applicants)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-folder-open fs-2 mb-2 d-block text-secondary opacity-50"></i>
                            No job applications found in this category.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($applicants as $app): 
                        $statusBadgeClass = match($app['status']) {
                            'New' => 'bg-info text-white',
                            'Reviewed' => 'bg-secondary text-white',
                            'Shortlisted' => 'bg-primary text-white',
                            'Interviewed' => 'bg-warning text-dark',
                            'Hired' => 'bg-success text-white',
                            'Rejected' => 'bg-danger text-white',
                            default => 'bg-secondary text-white'
                        };
                        $cleanPhone = preg_replace('/[^0-9]/', '', $app['phone']);
                        if (str_starts_with($cleanPhone, '0')) {
                            $cleanPhone = '94' . substr($cleanPhone, 1);
                        }
                    ?>
                        <tr>
                            <td>
                                <span class="badge bg-dark border border-secondary font-monospace"><?php echo htmlspecialchars($app['application_no']); ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                <div class="text-secondary small"><?php echo htmlspecialchars($app['email']); ?></div>
                            </td>
                            <td>
                                <span class="fw-semibold text-primary"><?php echo htmlspecialchars($app['position']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($app['experience']); ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-dark small"><?php echo htmlspecialchars($app['phone']); ?></span>
                                    <a href="https://wa.me/<?php echo $cleanPhone; ?>?text=Hello%20<?php echo urlencode($app['full_name']); ?>,%20this%20is%20Chandila%20Web%20Development%20HR%20regarding%20your%20application%20for%20<?php echo urlencode($app['position']); ?>." target="_blank" class="btn btn-sm btn-outline-success p-1" title="WhatsApp Candidate">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    <?php if (!empty($app['resume_file'])): ?>
                                        <a href="<?php echo BASE_URL . '/' . htmlspecialchars($app['resume_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="Download Resume">
                                            <i class="fa-solid fa-file-pdf me-1"></i> CV
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">No file</span>
                                    <?php endif; ?>

                                    <?php if (!empty($app['portfolio_url'])): ?>
                                        <a href="<?php echo htmlspecialchars($app['portfolio_url']); ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2" title="View Portfolio">
                                            <i class="fa-solid fa-link me-1"></i> Link
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $statusBadgeClass; ?>"><?php echo htmlspecialchars($app['status']); ?></span>
                            </td>
                            <td>
                                <span class="text-secondary small"><?php echo date('M d, Y', strtotime($app['created_at'])); ?></span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-chandila-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#applicantModal<?php echo $app['id']; ?>">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Review
                                </button>
                            </td>
                        </tr>

                        <!-- Review Modal -->
                        <div class="modal fade" id="applicantModal<?php echo $app['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content border-0 shadow">
                                    <form method="POST" action="careers.php">
                                        <input type="hidden" name="action" value="update_applicant_status">
                                        <input type="hidden" name="applicant_id" value="<?php echo $app['id']; ?>">

                                        <div class="modal-header border-bottom">
                                            <h5 class="modal-title fw-bold text-dark">
                                                Review Candidate: <?php echo htmlspecialchars($app['full_name']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-dark">
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Application Number</label>
                                                    <div class="fw-bold font-monospace text-dark"><?php echo htmlspecialchars($app['application_no']); ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Position Applied</label>
                                                    <div class="fw-bold text-primary"><?php echo htmlspecialchars($app['position']); ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">Experience Level</label>
                                                    <div class="text-dark"><?php echo htmlspecialchars($app['experience']); ?></div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="text-muted small">WhatsApp & Phone</label>
                                                    <div class="text-dark"><?php echo htmlspecialchars($app['phone']); ?></div>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="text-muted small">Cover Letter / Candidate Introduction</label>
                                                    <div class="p-3 bg-light rounded border text-dark small">
                                                        <?php echo nl2br(htmlspecialchars($app['cover_letter'] ?: 'No introduction submitted.')); ?>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-bold">Update Candidate Status</label>
                                                    <select class="form-select border" name="status">
                                                        <?php foreach (['New', 'Reviewed', 'Shortlisted', 'Interviewed', 'Rejected', 'Hired'] as $st): ?>
                                                            <option value="<?php echo $st; ?>" <?php echo ($app['status'] === $st) ? 'selected' : ''; ?>><?php echo $st; ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-12">
                                                    <label class="form-label text-muted small">Internal HR / Interview Notes</label>
                                                    <textarea class="form-control border" name="admin_notes" rows="3" placeholder="Enter interview feedback, technical ratings, or interview date..."><?php echo htmlspecialchars($app['admin_notes'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-primary btn-sm">Save Candidate Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

