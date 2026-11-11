<?php
$careTitle = 'Developer Task Assignment Desk';
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

// Handle Developer Assignment Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_developer') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security session expired. Please try again.');
    } else {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $developerId = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;
        $targetDate = !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null;
        $instructions = trim($_POST['instructions'] ?? '');

        if ($projectId) {
            $stmtP = $pdo->prepare("
                SELECT p.*, c.full_name AS customer_name, c.email AS customer_email, c.phone, c.whatsapp_number 
                FROM projects p 
                JOIN customers c ON p.customer_id = c.id 
                WHERE p.id = ?
            ");
            $stmtP->execute([$projectId]);
            $proj = $stmtP->fetch();

            if ($proj) {
                $assignedDate = $developerId ? date('Y-m-d H:i:s') : null;

                // Advance status to 'Development Started' if advance was confirmed
                $newStatus = $proj['status'];
                if ($developerId && $proj['status'] === 'Advance Payment Confirmed') {
                    $newStatus = 'Development Started';
                }

                $up = $pdo->prepare("
                    UPDATE projects SET 
                        assigned_developer_id = ?, 
                        assigned_date = ?, 
                        expected_completion_date = COALESCE(?, expected_completion_date),
                        status = ?
                    WHERE id = ?
                ");
                $up->execute([$developerId, $assignedDate, $targetDate, $newStatus, $projectId]);

                if ($developerId) {
                    $dev = $pdo->query("SELECT full_name, email FROM users WHERE id = {$developerId}")->fetch();
                    $devName = $dev['full_name'] ?? "Developer #{$developerId}";

                    // Log activity
                    $note = "Assigned to {$devName} by Customer Care agent {$currentUser['full_name']}.";
                    if (!empty($instructions)) {
                        $note .= " Handover notes: {$instructions}";
                    }
                    logActivity($pdo, $proj['job_id'], $projectId, 'Developer Assigned', $note, $currentUser['full_name']);

                    // Dispatch WhatsApp to customer
                    $clientPhone = $proj['whatsapp_number'] ?: $proj['phone'];
                    WhatsAppService::send($pdo, $clientPhone, 'Project Assigned', [
                        'job_id'   => $proj['job_id'],
                        'dev_name' => $devName
                    ], $projectId, $proj['job_id']);

                    // Dispatch Email to customer
                    if (!empty($proj['customer_email'])) {
                        EmailService::send($pdo, $proj['customer_email'], 'Developer Assigned to Your Project', [
                            'customer_name' => $proj['customer_name'],
                            'job_id'        => $proj['job_id'],
                            'project_title' => $proj['project_title'],
                            'dev_name'      => $devName,
                            'target_date'   => $targetDate ?: 'As agreed',
                            'tracking_url'  => BASE_URL . '/track_project.php?job_id=' . urlencode($proj['job_id'])
                        ], $projectId, $proj['job_id'], false, $proj['customer_name']);
                    }

                    setFlash('success', "Project {$proj['job_id']} assigned to {$devName}. Customer notified via WhatsApp & Email!");
                } else {
                    logActivity($pdo, $proj['job_id'], $projectId, 'Developer Unassigned', "Developer unassigned by Customer Care.", $currentUser['full_name']);
                    setFlash('info', "Project {$proj['job_id']} is now unassigned.");
                }

                header("Location: " . BASE_URL . "/care/assign.php");
                exit;
            }
        }
    }
}

// Fetch Developers with Workload
$developers = $pdo->query("
    SELECT u.*,
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions') THEN 1 END) AS ongoing_count,
           COUNT(CASE WHEN p.status IN ('Project Completed', 'Project Delivered') THEN 1 END) AS completed_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY ongoing_count ASC, u.full_name ASC
")->fetchAll();

// Fetch Unassigned Projects
$unassigned = $pdo->query("
    SELECT p.*, c.full_name AS customer_name, c.company_name, c.phone, c.whatsapp_number
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.assigned_developer_id IS NULL AND p.status != 'Cancelled'
    ORDER BY p.created_at ASC
")->fetchAll();

// Fetch Currently Assigned Projects
$assigned = $pdo->query("
    SELECT p.*, c.full_name AS customer_name, c.company_name, u.full_name AS dev_name, u.phone AS dev_phone
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.status != 'Cancelled'
    ORDER BY p.updated_at DESC
    LIMIT 40
")->fetchAll();

// Pre-selected project from GET if any
$selectedProjectId = (int)($_GET['project_id'] ?? 0);

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-user-check text-primary me-2"></i> Developer Task Assignment Desk</h3>
        <p class="text-secondary small mb-0">Balance engineering workloads, assign developers to incoming customer orders, and set target delivery timelines.</p>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-electric btn-sm">
            <i class="fa-solid fa-cart-plus me-1"></i> Enter New Order
        </a>
    </div>
</div>

<!-- Developer Workload Cards -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <h5 class="text-dark fw-bold mb-3"><i class="fa-solid fa-users-gear text-primary me-2"></i> Engineering Team Workload & Skillsets</h5>
    </div>
    <?php foreach ($developers as $dev): ?>
        <div class="col-md-6 col-lg-4">
            <div class="admin-card p-3 h-100 d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar-circle" style="width: 40px; height: 40px; font-size: 1rem; background: #e0f2fe; color: #0369a1;">
                                <i class="fa-solid fa-laptop-code"></i>
                            </div>
                            <div>
                                <h6 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($dev['full_name']); ?></h6>
                                <span class="text-muted small">@<?php echo htmlspecialchars($dev['username']); ?></span>
                            </div>
                        </div>
                        <?php if ($dev['ongoing_count'] == 0): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">Available</span>
                        <?php elseif ($dev['ongoing_count'] <= 2): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle">Normal Load</span>
                        <?php else: ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Heavy Load</span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <div class="text-secondary small" style="font-size: 0.75rem;">Tech Skills:</div>
                        <span class="badge bg-light text-dark border text-truncate d-inline-block" style="max-width: 100%; font-size: 0.72rem;">
                            <?php echo htmlspecialchars($dev['skills']); ?>
                        </span>
                    </div>
                </div>

                <div class="pt-2 border-top d-flex justify-content-between align-items-center">
                    <div class="small">
                        <strong class="text-primary"><?php echo (int)$dev['ongoing_count']; ?></strong> ongoing • 
                        <span class="text-secondary"><?php echo (int)$dev['completed_count']; ?> done</span>
                    </div>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $dev['phone']); ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" style="font-size: 0.75rem;" title="Message Developer">
                        <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Unassigned Projects Queue (Needs Allocation) -->
<div class="admin-card mb-4" style="border: 1.5px solid #f59e0b; border-radius: 16px;">
    <div class="p-3 bg-warning bg-opacity-10 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-warning text-dark"><i class="fa-solid fa-exclamation-triangle"></i></span>
            <h5 class="text-dark fw-bold mb-0">Unassigned Projects Queue (<?php echo count($unassigned); ?>)</h5>
        </div>
        <span class="text-muted small">These projects require developer assignment to commence development</span>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Name</th>
                    <th>Project Title & Scope</th>
                    <th>Target Date</th>
                    <th>Status</th>
                    <th>Total Cost</th>
                    <th class="text-end">Assign Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($unassigned)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-success py-4">
                            <i class="fa-solid fa-circle-check fa-2x mb-2 d-block"></i>
                            All customer orders have been assigned to developers! Great job.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($unassigned as $u): ?>
                        <tr class="<?php echo ($selectedProjectId === (int)$u['id']) ? 'table-warning' : ''; ?>">
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($u['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($u['customer_name']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($u['company_name'] ?: 'Individual'); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 260px;" title="<?php echo htmlspecialchars($u['project_title']); ?>">
                                    <?php echo htmlspecialchars($u['project_title']); ?>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;"><?php echo htmlspecialchars($u['project_type']); ?></span>
                            </td>
                            <td class="small text-secondary">
                                <?php echo formatDateOnly($u['expected_completion_date']); ?>
                            </td>
                            <td><?php echo getStatusBadge($u['status']); ?></td>
                            <td class="fw-bold text-dark"><?php echo formatCurrency($u['total_cost']); ?></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-electric btn-open-assign" 
                                    data-id="<?php echo $u['id']; ?>"
                                    data-jobid="<?php echo htmlspecialchars($u['job_id']); ?>"
                                    data-title="<?php echo htmlspecialchars($u['project_title']); ?>"
                                    data-target="<?php echo htmlspecialchars($u['expected_completion_date'] ?? ''); ?>"
                                    data-bs-toggle="modal" data-bs-target="#modalAssignDev">
                                    <i class="fa-solid fa-user-plus me-1"></i> Assign Developer
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- All Assigned Projects (For Reassignment / Review) -->
<div class="admin-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="text-dark fw-bold mb-0"><i class="fa-solid fa-diagram-project text-primary me-2"></i> Currently Assigned Projects</h5>
        <span class="text-secondary small">Reassign or adjust deadlines if developer capacity shifts</span>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer</th>
                    <th>Project</th>
                    <th>Assigned Developer</th>
                    <th>Stage</th>
                    <th>Progress</th>
                    <th>Target Date</th>
                    <th class="text-end">Manage</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assigned)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-4">No active assignments found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assigned as $a): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($a['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?php echo htmlspecialchars($a['customer_name']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate small" style="max-width: 220px;" title="<?php echo htmlspecialchars($a['project_title']); ?>">
                                    <?php echo htmlspecialchars($a['project_title']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="fa-solid fa-code me-1"></i> <?php echo htmlspecialchars($a['dev_name']); ?>
                                </span>
                            </td>
                            <td><?php echo getStatusBadge($a['status']); ?></td>
                            <td style="min-width: 100px;">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span><?php echo (int)$a['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo (int)$a['progress_percentage']; ?>%;"></div>
                                </div>
                            </td>
                            <td class="small text-secondary"><?php echo formatDateOnly($a['expected_completion_date']); ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-open-assign"
                                        data-id="<?php echo $a['id']; ?>"
                                        data-jobid="<?php echo htmlspecialchars($a['job_id']); ?>"
                                        data-title="<?php echo htmlspecialchars($a['project_title']); ?>"
                                        data-devid="<?php echo $a['assigned_developer_id']; ?>"
                                        data-target="<?php echo htmlspecialchars($a['expected_completion_date'] ?? ''); ?>"
                                        data-bs-toggle="modal" data-bs-target="#modalAssignDev"
                                        title="Reassign or Change Deadline">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <a href="<?php echo BASE_URL; ?>/care/project_view.php?id=<?php echo $a['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Full Project Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Assign Developer -->
<div class="modal fade" id="modalAssignDev" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="assign_developer">
                <input type="hidden" name="project_id" id="assignProjectId" value="">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title text-dark fw-bold">
                        <i class="fa-solid fa-user-check text-primary me-2"></i> Assign Developer to Project
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3 p-3 bg-light rounded border">
                        <div class="text-muted small font-monospace" id="assignJobIdBadge">CHW-....</div>
                        <h6 class="text-dark fw-bold mb-0" id="assignProjectTitleDisplay">Project Title</h6>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Select Developer <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="developer_id" id="assignDevSelect" required>
                            <option value="">-- Choose Developer --</option>
                            <?php foreach ($developers as $dev): ?>
                                <option value="<?php echo $dev['id']; ?>">
                                    <?php echo htmlspecialchars($dev['full_name']); ?> (<?php echo (int)$dev['ongoing_count']; ?> Ongoing) • <?php echo htmlspecialchars($dev['skills']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Target Completion Deadline</label>
                        <input type="date" class="form-control form-control-chandila" name="expected_completion_date" id="assignTargetDate">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Briefing / Instructions for Developer</label>
                        <textarea class="form-control form-control-chandila" name="instructions" rows="3" placeholder="Provide notes on client expectations, priority features, or special delivery requirements..."></textarea>
                    </div>

                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i> The customer will automatically receive WhatsApp & Email notifications confirming their assigned engineer.
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-electric px-4">Confirm Assignment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var modalEl = document.getElementById('modalAssignDev');
    var pIdInput = document.getElementById('assignProjectId');
    var jobIdBadge = document.getElementById('assignJobIdBadge');
    var titleDisplay = document.getElementById('assignProjectTitleDisplay');
    var devSelect = document.getElementById('assignDevSelect');
    var targetInput = document.getElementById('assignTargetDate');

    document.querySelectorAll('.btn-open-assign').forEach(function(btn) {
        btn.addEventListener('click', function() {
            pIdInput.value = this.dataset.id || '';
            jobIdBadge.textContent = this.dataset.jobid || '';
            titleDisplay.textContent = this.dataset.title || '';
            if (this.dataset.devid) {
                devSelect.value = this.dataset.devid;
            } else {
                devSelect.value = '';
            }
            if (this.dataset.target) {
                targetInput.value = this.dataset.target.substring(0, 10);
            }
        });
    });

    <?php if ($selectedProjectId): ?>
        var targetBtn = document.querySelector('.btn-open-assign[data-id="<?php echo $selectedProjectId; ?>"]');
        if (targetBtn) {
            targetBtn.click();
        }
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

