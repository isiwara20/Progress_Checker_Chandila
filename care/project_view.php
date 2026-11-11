<?php
$careTitle = 'Project Details & Status Inspector';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';
require_once __DIR__ . '/../config/email_config.php';

// Strict Customer Care Gate
requireCustomerCare();

$pdo = getDB();
$currentUser = getCurrentUser();
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId) {
    setFlash('error', 'Invalid Project identifier.');
    header("Location: " . BASE_URL . "/care/projects.php");
    exit;
}

// Fetch Master Project Info
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.email AS customer_email, 
           c.phone AS customer_phone, 
           c.whatsapp_number, 
           c.address AS customer_address,
           u.full_name AS dev_name,
           u.email AS dev_email,
           u.phone AS dev_phone,
           u.skills AS dev_skills
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('error', 'Project not found.');
    header("Location: " . BASE_URL . "/care/projects.php");
    exit;
}

// Fetch Progress History logged by Developer
$stmtProg = $pdo->prepare("
    SELECT pg.*, u.full_name AS updated_by_name
    FROM project_progress pg
    LEFT JOIN users u ON pg.updated_by = u.id
    WHERE pg.project_id = ?
    ORDER BY pg.created_at DESC
");
$stmtProg->execute([$projectId]);
$progressLogs = $stmtProg->fetchAll();

// Fetch Payment History
$stmtPay = $pdo->prepare("
    SELECT pm.*, u.full_name AS verified_by_name
    FROM payments pm
    LEFT JOIN users u ON pm.verified_by = u.id
    WHERE pm.project_id = ?
    ORDER BY pm.payment_date DESC
");
$stmtPay->execute([$projectId]);
$payments = $stmtPay->fetchAll();

// Fetch Audit Activities
$stmtAct = $pdo->prepare("
    SELECT * FROM project_activities
    WHERE project_id = ?
    ORDER BY created_at DESC
    LIMIT 30
");
$stmtAct->execute([$projectId]);
$activities = $stmtAct->fetchAll();

// Tracking link
$clientTrackingUrl = BASE_URL . '/track_project.php?job_id=' . urlencode($project['job_id']);

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="<?php echo BASE_URL; ?>/care/projects.php" class="btn btn-sm btn-light border py-0 px-2 text-secondary">
                <i class="fa-solid fa-arrow-left me-1"></i> Back
            </a>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 font-monospace fs-6">
                <?php echo htmlspecialchars($project['job_id']); ?>
            </span>
            <?php echo getStatusBadge($project['status']); ?>
        </div>
        <h3 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($project['project_title']); ?></h3>
        <span class="text-muted small">Customer: <strong><?php echo htmlspecialchars($project['customer_name']); ?></strong> (<?php echo htmlspecialchars($project['company_name'] ?: 'Individual'); ?>)</span>
    </div>

    <!-- Direct Action Bar -->
    <div class="d-flex flex-wrap gap-2">
        <!-- Direct WhatsApp Chat -->
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $project['whatsapp_number'] ?: $project['customer_phone']); ?>" target="_blank" class="btn btn-sm btn-success text-white">
            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Client
        </a>

        <!-- Mark Payment Done -->
        <a href="<?php echo BASE_URL; ?>/care/payments.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-primary">
            <i class="fa-solid fa-credit-card me-1"></i> Mark Payment Done
        </a>

        <!-- Assign / Reassign Developer -->
        <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-info text-white">
            <i class="fa-solid fa-user-check me-1"></i> <?php echo empty($project['assigned_developer_id']) ? 'Assign Developer' : 'Reassign Dev'; ?>
        </a>

        <!-- Official Branded Invoice -->
        <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($project['job_id']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="fa-solid fa-file-invoice me-1"></i> View Invoice
        </a>

        <!-- Copy Tracking Link -->
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCopyTrackLink" data-url="<?php echo $clientTrackingUrl; ?>">
            <i class="fa-solid fa-link me-1"></i> Copy Client Tracker
        </button>
    </div>
</div>

<!-- Progress Bar Banner -->
<div class="admin-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-dark fw-bold"><i class="fa-solid fa-gauge-high text-primary me-2"></i> Current Development Progress</span>
        <span class="fw-bold fs-5 text-primary"><?php echo (int)$project['progress_percentage']; ?>% Complete</span>
    </div>
    <div class="progress" style="height: 12px; border-radius: 6px;">
        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated" role="progressbar" style="width: <?php echo (int)$project['progress_percentage']; ?>%;"></div>
    </div>
    <div class="d-flex justify-content-between text-muted small mt-2">
        <span>Order Logged</span>
        <span>Advance Cleared</span>
        <span>50% Milestone</span>
        <span>Review & Staging</span>
        <span>Live Deployed</span>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Left Column: Scope, Features & Timeline -->
    <div class="col-lg-8">
        <!-- Project Scope Details -->
        <div class="admin-card p-4 mb-4">
            <h5 class="text-dark fw-bold mb-3 pb-2 border-bottom">
                <i class="fa-solid fa-file-lines text-primary me-2"></i> Scope & Requirements
            </h5>
            <div class="mb-3">
                <div class="text-secondary small fw-semibold">Project Category:</div>
                <span class="badge bg-light text-primary border fs-6 mt-1"><?php echo htmlspecialchars($project['project_type']); ?></span>
            </div>
            <div class="mb-3">
                <div class="text-secondary small fw-semibold">Public Description:</div>
                <p class="text-dark mt-1 mb-0" style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
            </div>
            <?php if (!empty($project['required_features'])): ?>
                <div class="mb-3">
                    <div class="text-secondary small fw-semibold">Feature Checklist:</div>
                    <p class="text-dark mt-1 mb-0" style="white-space: pre-line;"><?php echo nl2br(htmlspecialchars($project['required_features'])); ?></p>
                </div>
            <?php endif; ?>
            <?php if (!empty($project['reference_url'])): ?>
                <div class="mb-3">
                    <div class="text-secondary small fw-semibold">Client Reference Link:</div>
                    <a href="<?php echo htmlspecialchars($project['reference_url']); ?>" target="_blank" class="text-primary text-break">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i><?php echo htmlspecialchars($project['reference_url']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Developer Milestone Progress Updates -->
        <div class="admin-card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="text-dark fw-bold mb-0">
                    <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Developer Progress Milestone Logs
                </h5>
                <span class="badge bg-light text-dark border"><?php echo count($progressLogs); ?> Updates Logged</span>
            </div>

            <?php if (empty($progressLogs)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-hourglass-start fa-2x mb-2 d-block"></i>
                    No developer progress entries logged yet.
                </div>
            <?php else: ?>
                <div class="timeline-stream">
                    <?php foreach ($progressLogs as $p): ?>
                        <div class="p-3 mb-3 border rounded bg-light">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary"><?php echo (int)$p['percentage']; ?>%</span>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($p['status_stage']); ?></span>
                                </div>
                                <span class="text-secondary small">
                                    <i class="fa-regular fa-clock me-1"></i><?php echo formatDateTime($p['created_at']); ?>
                                </span>
                            </div>
                            <p class="text-dark small mb-1"><?php echo nl2br(htmlspecialchars($p['description'])); ?></p>
                            <?php if (!empty($p['internal_note'])): ?>
                                <div class="small text-muted fst-italic">
                                    <i class="fa-solid fa-lock me-1"></i> Internal: <?php echo htmlspecialchars($p['internal_note']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Payments Ledger -->
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="text-dark fw-bold mb-0">
                    <i class="fa-solid fa-receipt text-success me-2"></i> Payment Records & Settlements
                </h5>
                <a href="<?php echo BASE_URL; ?>/care/payments.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-success text-white">
                    <i class="fa-solid fa-plus me-1"></i> Mark Payment Done
                </a>
            </div>

            <?php if (empty($payments)): ?>
                <div class="text-center py-4 text-muted">No payment records found for this project.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-chandila mb-0">
                        <thead>
                            <tr>
                                <th>Milestone Stage</th>
                                <th>Amount</th>
                                <th>Method & Ref</th>
                                <th>Status</th>
                                <th>Recorded By</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pm): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($pm['payment_stage']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatCurrency($pm['amount']); ?></td>
                                    <td>
                                        <div class="small"><?php echo htmlspecialchars($pm['payment_method']); ?></div>
                                        <?php if (!empty($pm['transaction_reference'])): ?>
                                            <div class="small text-muted font-monospace"><?php echo htmlspecialchars($pm['transaction_reference']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pm['status'] === 'Payment Received'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle">Payment Received</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><?php echo htmlspecialchars($pm['status']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?php echo htmlspecialchars($pm['verified_by_name'] ?? 'Care Agent'); ?></td>
                                    <td class="small text-secondary"><?php echo formatDateOnly($pm['payment_date']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Right Column: Customer Info, Financial Summary & Developer Info -->
    <div class="col-lg-4">
        <!-- Customer Profile Card -->
        <div class="admin-card p-4 mb-4">
            <h5 class="text-dark fw-bold mb-3 pb-2 border-bottom">
                <i class="fa-solid fa-user text-primary me-2"></i> Customer Profile
            </h5>
            <div class="d-flex align-items-center gap-3 mb-3">
                <div class="user-avatar-circle" style="width: 48px; height: 48px; font-size: 1.2rem; background: #e0e7ff; color: #3730a3;">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <h6 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($project['customer_name']); ?></h6>
                    <div class="text-muted small"><?php echo htmlspecialchars($project['company_name'] ?: 'Individual'); ?></div>
                </div>
            </div>
            <ul class="list-unstyled text-dark small mb-3">
                <li class="mb-2"><i class="fa-solid fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($project['customer_email']); ?></li>
                <li class="mb-2"><i class="fa-solid fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($project['customer_phone']); ?></li>
                <li class="mb-2">
                    <i class="fa-brands fa-whatsapp me-2 text-success"></i>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $project['whatsapp_number'] ?: $project['customer_phone']); ?>" target="_blank" class="text-success text-decoration-none fw-semibold">
                        <?php echo htmlspecialchars($project['whatsapp_number'] ?: $project['customer_phone']); ?> (Chat)
                    </a>
                </li>
                <?php if (!empty($project['customer_address'])): ?>
                    <li><i class="fa-solid fa-location-dot me-2 text-muted"></i><?php echo htmlspecialchars($project['customer_address']); ?></li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Financial Summary Card -->
        <div class="admin-card p-4 mb-4">
            <h5 class="text-dark fw-bold mb-3 pb-2 border-bottom">
                <i class="fa-solid fa-wallet text-success me-2"></i> Financial Milestones
            </h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small">Total Budget:</span>
                <span class="fw-bold text-dark fs-5"><?php echo formatCurrency($project['total_cost']); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small">Advance Deposit:</span>
                <span class="text-dark fw-semibold"><?php echo formatCurrency($project['advance_amount']); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small">50% Milestone:</span>
                <span class="text-dark fw-semibold"><?php echo formatCurrency($project['milestone2_amount']); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-secondary small">Final Balance:</span>
                <span class="text-dark fw-semibold"><?php echo formatCurrency($project['final_amount']); ?></span>
            </div>
            <div class="pt-2 border-top d-flex justify-content-between align-items-center mb-2">
                <span class="text-success small fw-bold">Total Paid:</span>
                <span class="fw-bold text-success fs-6"><?php echo formatCurrency($project['total_paid']); ?></span>
            </div>
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-danger small fw-bold">Remaining Due:</span>
                <span class="fw-bold text-danger fs-6"><?php echo formatCurrency($project['balance_due']); ?></span>
            </div>
        </div>

        <!-- Assigned Developer Card -->
        <div class="admin-card p-4 mb-4">
            <h5 class="text-dark fw-bold mb-3 pb-2 border-bottom">
                <i class="fa-solid fa-laptop-code text-info me-2"></i> Assigned Developer
            </h5>
            <?php if (!empty($project['dev_name'])): ?>
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="user-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem; background: #e0f2fe; color: #0369a1;">
                        <i class="fa-solid fa-code"></i>
                    </div>
                    <div>
                        <h6 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($project['dev_name']); ?></h6>
                        <div class="text-muted small"><?php echo htmlspecialchars($project['dev_skills'] ?? 'Developer'); ?></div>
                    </div>
                </div>
                <div class="small text-secondary mb-2">
                    <i class="fa-solid fa-calendar-check me-2 text-muted"></i>Assigned: <?php echo formatDateOnly($project['assigned_date']); ?>
                </div>
                <div class="small text-secondary mb-3">
                    <i class="fa-solid fa-hourglass-end me-2 text-muted"></i>Target Deadline: <?php echo formatDateOnly($project['expected_completion_date']); ?>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-outline-info w-100">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Reassign Dev
                    </a>
                    <?php if (!empty($project['dev_phone'])): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $project['dev_phone']); ?>" target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-3">
                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle p-2 mb-2 d-inline-block">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i> Developer Unassigned
                    </span>
                    <p class="text-secondary small mb-3">Assign a developer to start work on this customer order.</p>
                    <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $project['id']; ?>" class="btn btn-sm btn-electric w-100">
                        <i class="fa-solid fa-user-plus me-1"></i> Assign Developer Now
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick 24/7 Public Client Tracking Link Card -->
        <div class="admin-card p-4">
            <h6 class="text-dark fw-bold mb-2"><i class="fa-solid fa-satellite-dish text-info me-2"></i> Client Live Tracking Link</h6>
            <p class="text-secondary small mb-3">Share this self-service tracking link with the customer to check real-time progress anytime:</p>
            <div class="input-group input-group-sm mb-2">
                <input type="text" class="form-control font-monospace text-truncate" id="trackUrlInput" value="<?php echo $clientTrackingUrl; ?>" readonly>
                <button class="btn btn-primary" type="button" id="btnCopyTrackLink2"><i class="fa-solid fa-copy"></i></button>
            </div>
            <a href="<?php echo $clientTrackingUrl; ?>" target="_blank" class="btn btn-sm btn-outline-secondary w-100">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open Tracker as Client
            </a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function copyTracker(url) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showChandilaToast('success', 'Client Live Tracker link copied to clipboard!');
            });
        } else {
            prompt('Copy this link:', url);
        }
    }

    var btn1 = document.getElementById('btnCopyTrackLink');
    if (btn1) {
        btn1.addEventListener('click', function() { copyTracker(this.dataset.url); });
    }

    var btn2 = document.getElementById('btnCopyTrackLink2');
    var input2 = document.getElementById('trackUrlInput');
    if (btn2 && input2) {
        btn2.addEventListener('click', function() { copyTracker(input2.value); });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

