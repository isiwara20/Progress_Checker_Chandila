<?php
$pageTitle = 'Track My Project – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$jobId = trim($_GET['job_id'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$project = null;
$customer = null;
$progressLogs = [];
$milestones = [];
$payments = [];
$searchAttempted = false;
$errorMessage = '';

if (!empty($jobId)) {
    $searchAttempted = true;

    // Security check: Phone verification
    if (empty($phone)) {
        $errorMessage = "For your security, please provide your registered WhatsApp or Contact Number along with your Job ID.";
    } else {
        // Normalize phone number for matching
        $cleanPhoneInput = preg_replace('/[^0-9]/', '', $phone);
        if (str_starts_with($cleanPhoneInput, '0')) {
            $cleanPhoneInput = substr($cleanPhoneInput, 1);
        }

        try {
            // Find project by Job ID and match customer phone number
            $stmt = $pdo->prepare("
                SELECT p.*, 
                       c.full_name AS customer_name, 
                       c.company_name, 
                       c.phone AS customer_phone, 
                       c.whatsapp_number,
                       u.full_name AS dev_name
                FROM projects p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.assigned_developer_id = u.id
                WHERE p.job_id = ?
                LIMIT 1
            ");
            $stmt->execute([$jobId]);
            $foundProject = $stmt->fetch();

            if (!$foundProject) {
                $errorMessage = "No project found with Job ID '{$jobId}'. Please verify your Job ID.";
            } else {
                // Verify phone matches customer's phone or whatsapp
                $cleanDbPhone = preg_replace('/[^0-9]/', '', $foundProject['customer_phone']);
                $cleanDbWa = preg_replace('/[^0-9]/', '', $foundProject['whatsapp_number']);

                // Match last 7 digits to accommodate different country codes
                $match = false;
                $inputTail = substr($cleanPhoneInput, -7);
                if (strlen($inputTail) >= 6) {
                    if (str_contains($cleanDbPhone, $inputTail) || str_contains($cleanDbWa, $inputTail)) {
                        $match = true;
                    }
                }

                if (!$match) {
                    $errorMessage = "The provided contact number does not match the registered customer for Job ID '{$jobId}'.";
                } else {
                    $project = $foundProject;

                    // Fetch Public Progress Logs (Note: internal_note is strictly EXCLUDED)
                    $progStmt = $pdo->prepare("
                        SELECT percentage, status_stage, description, created_at 
                        FROM project_progress 
                        WHERE project_id = ? 
                        ORDER BY created_at DESC
                    ");
                    $progStmt->execute([$project['id']]);
                    $progressLogs = $progStmt->fetchAll();

                    // Fetch Public Milestones
                    $mileStmt = $pdo->prepare("
                        SELECT milestone_name, target_percentage, is_completed, completed_at, notes
                        FROM project_milestones 
                        WHERE project_id = ? 
                        ORDER BY target_percentage ASC
                    ");
                    $mileStmt->execute([$project['id']]);
                    $milestones = $mileStmt->fetchAll();

                    // Fetch Verified Payment History (Summary only)
                    $payStmt = $pdo->prepare("
                        SELECT payment_stage, amount, payment_method, status, payment_date
                        FROM payments
                        WHERE project_id = ?
                        ORDER BY payment_date ASC
                    ");
                    $payStmt->execute([$project['id']]);
                    $payments = $payStmt->fetchAll();
                }
            }
        } catch (Exception $e) {
            $errorMessage = "System error retrieving project tracking details.";
        }
    }
}
?>

<div class="py-5" style="background: radial-gradient(circle at 50% 0%, rgba(245, 158, 11, 0.05) 0%, transparent 50%), #f8fafc;">
    <div class="container py-3">

        <!-- Tracking Search Form -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="track-widget p-4 p-md-5">
                    <div class="text-center mb-4">
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-1 mb-2 fw-bold">
                            <i class="fa-solid fa-barcode me-1"></i> PUBLIC PROJECT TRACKER
                        </span>
                        <h2 class="text-dark fw-bold mb-1 display-6">Track Your Web Project Progress</h2>
                        <p class="text-secondary small mb-0">Enter your official Job ID and registered phone number to view live status.</p>
                    </div>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger text-danger mb-4">
                            <i class="fa-solid fa-circle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-3">
                        <div class="col-md-6">
                            <label for="job_id" class="form-label text-dark small fw-semibold">Job ID</label>
                            <input type="text" class="form-control form-control-chandila" id="job_id" name="job_id" placeholder="e.g. CHW-2026-0001" value="<?php echo htmlspecialchars($jobId); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="phone" class="form-label text-dark small fw-semibold">Registered Phone / WhatsApp</label>
                            <input type="text" class="form-control form-control-chandila" id="phone" name="phone" placeholder="e.g. 0768433340 or +9476843340" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-chandila-primary w-100 py-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-magnifying-glass me-2"></i> Verify & View Project Dashboard
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <?php if ($project): ?>
            <!-- Project Overview Header Card -->
            <div class="chandila-card p-4 mb-4">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 border-bottom border-secondary border-opacity-15 pb-4 mb-4">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-light border border-secondary border-opacity-50 text-dark px-3 py-1 font-monospace fs-6">
                                <i class="fa-solid fa-barcode me-1 text-primary"></i> <?php echo htmlspecialchars($project['job_id']); ?>
                            </span>
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type']); ?></span>
                            <?php echo getStatusBadge($project['status']); ?>
                        </div>
                        <h2 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($project['project_title']); ?></h2>
                        <div class="text-secondary small">
                            Client: <strong><?php echo htmlspecialchars($project['customer_name']); ?></strong>
                            <?php if (!empty($project['company_name'])): ?>
                                (<?php echo htmlspecialchars($project['company_name']); ?>)
                            <?php endif; ?>
                            • Submitted on: <?php echo formatDateOnly($project['created_at']); ?>
                        </div>
                    </div>

                    <div class="text-lg-end">
                        <div class="text-secondary small text-uppercase fw-semibold mb-1">Target Completion Date</div>
                        <div class="fs-5 fw-bold text-primary">
                            <i class="fa-regular fa-calendar-check me-1"></i> <?php echo formatDateOnly($project['expected_completion_date']); ?>
                        </div>
                        <div class="small text-secondary mt-1">
                            Team: <strong><?php echo htmlspecialchars($project['dev_name'] ?? 'Chandila Core Dev Team'); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Progress Bar Section -->
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-dark fw-bold">Overall Project Completion</span>
                        <span class="text-primary fw-bold fs-5"><?php echo (int)$project['progress_percentage']; ?>%</span>
                    </div>
                    <div class="progress" style="height: 16px; background-color: #f1f5f9; border-radius: 20px; overflow: hidden; border: 1px solid #cbd5e1;">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" 
                             style="width: <?php echo (int)$project['progress_percentage']; ?>%; background: linear-gradient(90deg, #d97706, #2563eb);" 
                             aria-valuenow="<?php echo (int)$project['progress_percentage']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two-Column Grid: Timeline Stepper & Financial/Milestone Summary -->
            <div class="row g-4">
                <!-- Left Column: 12-Stage Visual Stepper Timeline -->
                <div class="col-lg-7">
                    <div class="chandila-card h-100">
                        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-secondary border-opacity-15">
                            <h4 class="text-dark mb-0">
                                <i class="fa-solid fa-list-check text-primary me-2"></i> Project Development Journey
                            </h4>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                Stage <?php echo getStageStepNumber($project['status']); ?> of <?php echo count(PROJECT_STAGES); ?>
                            </span>
                        </div>

                        <?php
                        $currentStepIndex = getStageStepNumber($project['status']);
                        ?>
                        <div class="timeline-stepper">
                            <?php foreach (PROJECT_STAGES as $index => $stageName): 
                                $stepNum = $index + 1;
                                $isCompleted = ($stepNum < $currentStepIndex) || ($project['status'] === 'Project Delivered');
                                $isActive = ($stepNum === $currentStepIndex) && ($project['status'] !== 'Project Delivered');
                                $isPending = ($stepNum > $currentStepIndex);

                                $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : 'pending');
                            ?>
                                <div class="timeline-step <?php echo $stepClass; ?>">
                                    <div class="step-icon-bubble">
                                        <?php if ($isCompleted): ?>
                                            <i class="fa-solid fa-check"></i>
                                        <?php elseif ($isActive): ?>
                                            <i class="fa-solid fa-play"></i>
                                        <?php else: ?>
                                            <?php echo $stepNum; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ms-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-bold <?php echo $isActive ? 'text-primary fs-6' : ($isCompleted ? 'text-dark' : 'text-secondary'); ?>">
                                                <?php echo htmlspecialchars($stageName); ?>
                                            </span>
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-primary text-white font-monospace py-0 px-2" style="font-size: 0.7rem;">CURRENT STAGE</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-secondary small mt-1">
                                            <?php 
                                            // Stage descriptive explanations
                                            switch ($stageName) {
                                                case 'Request Submitted': echo "Requirement received with official Job ID."; break;
                                                case 'Request Reviewed': echo "Scope analyzed and payment schedule agreed."; break;
                                                case 'Project Confirmed': echo "Project approved for development queue."; break;
                                                case 'Advance Payment Pending': echo "Awaiting advance payment verification."; break;
                                                case 'Advance Payment Confirmed': echo "Advance received. Environment setup initiated."; break;
                                                case 'Development Started': echo "Core architecture & database modeling started."; break;
                                                case 'Development in Progress': echo "Sprint modules & frontend development underway."; break;
                                                case 'Second Payment Pending': echo "50% development milestone reached. Awaiting second milestone payment settlement."; break;
                                                case 'Client Review / Revisions': echo "Preview environment shared for client feedback."; break;
                                                case 'Final Payment Pending': echo "Development completed. Awaiting final balance."; break;
                                                case 'Hosting & Deployment': echo "Deploying files, SSL, database & live DNS."; break;
                                                case 'Project Completed': echo "Quality assurance and testing verified."; break;
                                                case 'Project Delivered': echo "Live website handed over to client with credentials."; break;
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Payment Summary & Latest Updates -->
                <div class="col-lg-5">
                    <!-- 3-Stage Payment Summary Card -->
                    <div class="chandila-card mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom border-secondary border-opacity-15">
                            <h4 class="text-dark mb-0">
                                <i class="fa-solid fa-receipt text-primary me-2"></i> Payment Milestone Summary
                            </h4>
                        </div>

                        <div class="p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small">Total Agreed Cost:</span>
                                <span class="text-dark fw-bold fs-5"><?php echo formatCurrency($project['total_cost']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="text-secondary small">Total Verified Paid:</span>
                                <span class="text-success fw-bold"><?php echo formatCurrency($project['total_paid']); ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary border-opacity-15">
                                <span class="text-secondary small">Remaining Balance:</span>
                                <span class="text-warning fw-bold fs-6"><?php echo formatCurrency($project['balance_due']); ?></span>
                            </div>
                        </div>

                        <!-- 3 Milestone Breakdown -->
                        <div class="list-group list-group-flush bg-transparent">
                            <!-- Stage 1: Advance -->
                            <div class="list-group-item bg-transparent text-dark border-secondary border-opacity-15 px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">1. Advance Payment</div>
                                    <div class="text-secondary" style="font-size: 0.75rem;">Due prior to development kickoff</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold small"><?php echo formatCurrency($project['advance_amount']); ?></div>
                                    <?php if ($project['advance_amount'] > 0 && $project['total_paid'] >= $project['advance_amount']): ?>
                                        <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($project['job_id']); ?>&stage=advance" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none" style="font-size: 0.7rem;"><i class="fa-solid fa-check me-1"></i> Paid (Receipt)</a>
                                    <?php else: ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle" style="font-size: 0.7rem;"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stage 2: 50% Milestone -->
                            <div class="list-group-item bg-transparent text-dark border-secondary border-opacity-15 px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">2. Second Payment (50% Milestone)</div>
                                    <div class="text-secondary" style="font-size: 0.75rem;">Due before scheduled completion</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold small"><?php echo formatCurrency($project['milestone2_amount']); ?></div>
                                    <?php if ($project['total_paid'] >= ($project['advance_amount'] + $project['milestone2_amount']) && $project['milestone2_amount'] > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($project['job_id']); ?>&stage=milestone2" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none" style="font-size: 0.7rem;"><i class="fa-solid fa-check me-1"></i> Paid (Receipt)</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Stage 3: Final Payment -->
                            <div class="list-group-item bg-transparent text-dark border-secondary border-opacity-15 px-0 py-2 d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold small">3. Final Payment & Hosting</div>
                                    <div class="text-secondary" style="font-size: 0.75rem;">Due upon review, before live deployment</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-bold small"><?php echo formatCurrency($project['final_amount']); ?></div>
                                    <?php if ($project['balance_due'] <= 0 && $project['total_cost'] > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($project['job_id']); ?>&stage=final" target="_blank" class="badge bg-success-subtle text-success border border-success-subtle text-decoration-none" style="font-size: 0.7rem;"><i class="fa-solid fa-certificate me-1"></i> Settled (PAID)</a>
                                    <?php else: ?>
                                        <span class="badge bg-secondary" style="font-size: 0.7rem;"><i class="fa-solid fa-lock me-1"></i> Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($project['job_id']); ?>" target="_blank" class="btn btn-electric w-100 fw-bold mt-3">
                            <i class="fa-solid fa-file-invoice-dollar me-1"></i> View Official Invoices &amp; Receipts
                        </a>

                        <!-- Live Hosting Status if Deployed -->
                        <?php if (!empty($project['hosting_url'])): ?>
                            <div class="mt-3 p-3 rounded-3 bg-success bg-opacity-10 border border-success text-center">
                                <div class="text-success fw-bold small mb-1"><i class="fa-solid fa-globe me-1"></i> Live Website Deployed</div>
                                <a href="<?php echo htmlspecialchars($project['hosting_url']); ?>" target="_blank" class="text-primary fw-bold text-decoration-underline">
                                    <?php echo htmlspecialchars($project['hosting_url']); ?> <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Latest Progress Updates Feed -->
                    <div class="chandila-card">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom border-secondary border-opacity-15">
                            <h4 class="text-dark mb-0">
                                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Latest Developer Updates
                            </h4>
                        </div>

                        <?php if (empty($progressLogs)): ?>
                            <div class="text-secondary text-center py-4 small">
                                <i class="fa-solid fa-hourglass-start fa-2x mb-2 d-block text-secondary"></i>
                                No detailed progress logs posted yet. Development kickoff in progress.
                            </div>
                        <?php else: ?>
                            <div class="d-flex flex-column gap-3">
                                <?php foreach ($progressLogs as $log): ?>
                                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1.5px solid #e2e8f0;">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span class="badge bg-primary text-white"><?php echo (int)$log['percentage']; ?>% Completed</span>
                                            <small class="text-secondary"><?php echo formatDateTime($log['created_at']); ?></small>
                                        </div>
                                        <div class="text-dark small mt-2">
                                            <?php echo nl2br(htmlspecialchars($log['description'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        <?php elseif ($searchAttempted): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-magnifying-glass fa-3x text-secondary mb-3"></i>
                <h4 class="text-dark">Project Not Found</h4>
                <p class="text-secondary">Please verify your Job ID and registered contact number, or contact support at <strong>076 843 3340</strong>.</p>
            </div>
        <?php else: ?>
            <!-- Project Lifecycle Visual Guide Image -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-dark">
                        <img src="<?php echo BASE_URL; ?>/assets/images/track_hero.svg" alt="Project Lifecycle Tracking" class="img-fluid w-100">
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

