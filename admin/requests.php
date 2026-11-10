<?php
$adminTitle = 'New Project Requests';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Review / Approval / Rejection Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please try again.');
    } else {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $action = $_POST['action'];

        if ($action === 'approve_project' && $projectId) {
            $totalCost   = (float)($_POST['total_cost'] ?? 0);
            $advanceAmt  = (float)($_POST['advance_amount'] ?? 0);
            $milestone2  = (float)($_POST['milestone2_amount'] ?? 0);
            $finalAmt    = (float)($_POST['final_amount'] ?? 0);
            $targetDate  = $_POST['expected_completion_date'] ?? null;
            $developerId = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;
            $advancePaid = !empty($_POST['advance_paid']) ? 1 : 0;

            if ($totalCost <= 0) {
                setFlash('error', 'Total project cost must be greater than zero.');
            } else {
                // Determine new status
                if ($advancePaid) {
                    $newStatus = 'Advance Payment Confirmed';
                    $totalPaid = $advanceAmt;
                } else {
                    $newStatus = 'Advance Payment Pending';
                    $totalPaid = 0.00;
                }
                $balanceDue = max(0, $totalCost - $totalPaid);

                // Fetch Project & Customer details for WhatsApp
                $stmtP = $pdo->prepare("SELECT p.*, c.phone, c.whatsapp_number FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
                $stmtP->execute([$projectId]);
                $proj = $stmtP->fetch();

                if ($proj) {
                    $upStmt = $pdo->prepare("
                        UPDATE projects SET 
                            total_cost = ?,
                            advance_amount = ?,
                            milestone2_amount = ?,
                            final_amount = ?,
                            total_paid = ?,
                            balance_due = ?,
                            expected_completion_date = ?,
                            assigned_developer_id = ?,
                            assigned_date = ?,
                            status = ?,
                            is_approved = 1
                        WHERE id = ?
                    ");
                    $assignedDate = $developerId ? date('Y-m-d H:i:s') : null;
                    $upStmt->execute([
                        $totalCost,
                        $advanceAmt,
                        $milestone2,
                        $finalAmt,
                        $totalPaid,
                        $balanceDue,
                        $targetDate,
                        $developerId,
                        $assignedDate,
                        $newStatus,
                        $projectId
                    ]);

                    // If advance is verified right away, record payment in payments table
                    if ($advancePaid && $advanceAmt > 0) {
                        $payStmt = $pdo->prepare("
                            INSERT INTO payments (project_id, payment_stage, amount, payment_method, status, admin_notes, verified_by, verified_at, payment_date, created_at)
                            VALUES (?, 'Advance Payment', ?, 'Bank Transfer', 'Payment Received', 'Verified upon project approval.', ?, NOW(), NOW(), NOW())
                        ");
                        $payStmt->execute([$projectId, $advanceAmt, $currentUser['id']]);

                        // Log activity
                        logActivity($pdo, $proj['job_id'], $projectId, 'Advance Payment Confirmed', "Advance payment of " . formatCurrency($advanceAmt) . " verified by Admin.", $currentUser['full_name']);

                        // Send WhatsApp Event 3: Advance Payment Confirmed
                        WhatsAppService::send($pdo, $proj['whatsapp_number'] ?: $proj['phone'], 'Advance Payment Confirmed', ['job_id' => $proj['job_id']], $projectId, $proj['job_id']);
                    } else {
                        // Log activity
                        logActivity($pdo, $proj['job_id'], $projectId, 'Project Approved', "Project approved. Total cost agreed at " . formatCurrency($totalCost) . ". Advance payment pending.", $currentUser['full_name']);

                        // Send WhatsApp Event 2: Project Approved
                        WhatsAppService::send($pdo, $proj['whatsapp_number'] ?: $proj['phone'], 'Project Approved', ['job_id' => $proj['job_id']], $projectId, $proj['job_id']);
                    }

                    // If developer is assigned, send WhatsApp Event 4: Project Assigned
                    if ($developerId) {
                        WhatsAppService::send($pdo, $proj['whatsapp_number'] ?: $proj['phone'], 'Project Assigned', ['job_id' => $proj['job_id']], $projectId, $proj['job_id']);
                        logActivity($pdo, $proj['job_id'], $projectId, 'Developer Assigned', "Project assigned to developer.", $currentUser['full_name']);
                    }

                    setFlash('success', "Project {$proj['job_id']} has been successfully approved and updated!");
                    header("Location: " . BASE_URL . "/admin/requests.php");
                    exit;
                }
            }
        } elseif ($action === 'reject_project' && $projectId) {
            $reason = trim($_POST['reject_reason'] ?? 'Requirements do not align with company capacity.');
            
            $stmtP = $pdo->prepare("SELECT job_id FROM projects WHERE id = ?");
            $stmtP->execute([$projectId]);
            $jobId = $stmtP->fetchColumn();

            $upStmt = $pdo->prepare("UPDATE projects SET status = 'Cancelled', internal_notes = CONCAT(COALESCE(internal_notes,''), '\nRejected: ', ?) WHERE id = ?");
            $upStmt->execute([$reason, $projectId]);

            logActivity($pdo, $jobId, $projectId, 'Project Rejected', "Project request was rejected: " . $reason, $currentUser['full_name']);
            setFlash('info', "Project {$jobId} has been marked as cancelled.");
            header("Location: " . BASE_URL . "/admin/requests.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Developers for Assignment Dropdown
$developers = $pdo->query("SELECT id, full_name, skills FROM users WHERE role = 'developer' AND status = 'active' ORDER BY full_name ASC")->fetchAll();

// Fetch Requests
$requests = $pdo->query("
    SELECT p.*, c.full_name AS customer_name, c.company_name, c.email AS customer_email, c.phone AS customer_phone, c.whatsapp_number
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.status IN ('Request Submitted', 'Request Reviewed', 'Advance Payment Pending')
    ORDER BY p.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-inbox text-warning me-2"></i> Inbound Project Requests</h3>
        <p class="text-secondary small mb-0">Review submitted requirements, quote pricing, define milestone schedules, and assign developers.</p>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Info</th>
                    <th>Project Specifications</th>
                    <th>Expected Budget</th>
                    <th>Target Date</th>
                    <th>Current Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-folder-open fa-3x text-secondary mb-2 d-block"></i>
                            No pending project requests awaiting review at this time.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $req['id']; ?>" class="fw-bold font-monospace text-primary">
                                    <?php echo htmlspecialchars($req['job_id']); ?>
                                </a>
                                <div class="text-secondary small"><?php echo formatDateOnly($req['created_at']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($req['customer_name']); ?></div>
                                <?php if (!empty($req['company_name'])): ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($req['company_name']); ?></div>
                                <?php endif; ?>
                                <div class="small text-secondary mt-1">
                                    <i class="fa-brands fa-whatsapp text-success me-1"></i> <?php echo htmlspecialchars($req['whatsapp_number'] ?: $req['customer_phone']); ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($req['project_title']); ?>">
                                    <?php echo htmlspecialchars($req['project_title']); ?>
                                </div>
                                <span class="badge bg-secondary small mt-1"><?php echo htmlspecialchars($req['project_type']); ?></span>
                            </td>
                            <td>
                                <span class="text-dark fw-semibold small"><?php echo htmlspecialchars($req['expected_budget'] ?: 'Not specified'); ?></span>
                            </td>
                            <td>
                                <span class="small text-secondary"><?php echo formatDateOnly($req['expected_completion_date']); ?></span>
                            </td>
                            <td>
                                <?php echo getStatusBadge($req['status']); ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-info me-1" title="View Full Details">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-electric" data-bs-toggle="modal" data-bs-target="#modalApprove<?php echo $req['id']; ?>" title="Approve & Schedule">
                                    <i class="fa-solid fa-check-double me-1"></i> Review & Approve
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger ms-1" data-bs-toggle="modal" data-bs-target="#modalReject<?php echo $req['id']; ?>" title="Reject Request">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Modal: Review & Approve Request -->
                        <div class="modal fade" id="modalApprove<?php echo $req['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content chandila-modal">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="approve_project">
                                        <input type="hidden" name="project_id" value="<?php echo $req['id']; ?>">

                                        <div class="modal-header">
                                            <h5 class="modal-title text-dark fw-bold">
                                                <i class="fa-solid fa-check-double text-primary me-2"></i> Review & Approve Project <?php echo htmlspecialchars($req['job_id']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body p-4">
                                            <div class="p-3 rounded-3 mb-4" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                                <div class="row">
                                                    <div class="col-md-6 mb-2">
                                                        <span class="text-muted small">Customer:</span>
                                                        <div class="text-dark fw-bold"><?php echo htmlspecialchars($req['customer_name']); ?></div>
                                                    </div>
                                                    <div class="col-md-6 mb-2">
                                                        <span class="text-muted small">Project Type:</span>
                                                        <div class="text-primary fw-bold"><?php echo htmlspecialchars($req['project_type']); ?></div>
                                                    </div>
                                                    <div class="col-12 mb-2">
                                                        <span class="text-muted small">Project Title:</span>
                                                        <div class="text-dark"><?php echo htmlspecialchars($req['project_title']); ?></div>
                                                    </div>
                                                    <div class="col-12">
                                                        <span class="text-muted small">Customer Expected Budget:</span>
                                                        <div class="text-warning fw-semibold"><?php echo htmlspecialchars($req['expected_budget'] ?: 'Not specified'); ?></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <h6 class="text-primary fw-bold mb-3">Set Agreed Pricing & Milestone Schedule</h6>

                                            <div class="row g-3 mb-3">
                                                <!-- Total Cost -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Estimated Total Project Cost (Rs.) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" class="form-control form-control-chandila modal-calc-total" name="total_cost" required value="<?php echo $req['total_cost'] > 0 ? $req['total_cost'] : ''; ?>" placeholder="e.g. 150000">
                                                </div>

                                                <!-- Advance Payment -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Advance Payment Amount (Rs.) <span class="text-danger">*</span></label>
                                                    <input type="number" step="0.01" class="form-control form-control-chandila modal-calc-adv" name="advance_amount" required value="<?php echo $req['advance_amount'] > 0 ? $req['advance_amount'] : ''; ?>" placeholder="e.g. 45000 (30%)">
                                                </div>

                                                <!-- Milestone 2 (50%) -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Second Payment Amount (50% Milestone) (Rs.)</label>
                                                    <input type="number" step="0.01" class="form-control form-control-chandila modal-calc-m2" name="milestone2_amount" value="<?php echo $req['milestone2_amount'] > 0 ? $req['milestone2_amount'] : ''; ?>" placeholder="e.g. 75000 (50%)">
                                                </div>

                                                <!-- Final Payment -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Final Outstanding Amount (Hosting/Delivery) (Rs.)</label>
                                                    <input type="number" step="0.01" class="form-control form-control-chandila modal-calc-final" name="final_amount" value="<?php echo $req['final_amount'] > 0 ? $req['final_amount'] : ''; ?>" placeholder="e.g. 30000 (Remaining)">
                                                </div>

                                                <!-- Expected Completion Date -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Expected Completion Date <span class="text-danger">*</span></label>
                                                    <input type="date" class="form-control form-control-chandila" name="expected_completion_date" value="<?php echo htmlspecialchars($req['expected_completion_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>" required>
                                                </div>

                                                <!-- Assign Developer -->
                                                <div class="col-md-6">
                                                    <label class="form-label text-dark small fw-semibold">Assign Developer</label>
                                                    <select class="form-select form-select-chandila" name="developer_id">
                                                        <option value="">-- Assign Later --</option>
                                                        <?php foreach ($developers as $dev): ?>
                                                            <option value="<?php echo $dev['id']; ?>" <?php echo ($req['assigned_developer_id'] == $dev['id']) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($dev['full_name']); ?> (<?php echo htmlspecialchars($dev['skills']); ?>)
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Advance Payment Verification Checkbox -->
                                            <div class="p-3 rounded-3 bg-light border mt-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="advance_paid" value="1" id="advCheck<?php echo $req['id']; ?>">
                                                    <label class="form-check-label text-dark fw-bold" for="advCheck<?php echo $req['id']; ?>">
                                                        Mark Advance Payment as Received & Verified Now
                                                    </label>
                                                </div>
                                                <div class="text-muted small ms-4 mt-1">
                                                    If checked, the project will immediately progress to <strong>'Advance Payment Confirmed'</strong> and <strong>'Development Started'</strong>. An automated confirmation WhatsApp message will be dispatched.
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-electric">
                                                <i class="fa-solid fa-check-double me-1"></i> Confirm & Dispatch WhatsApp Update
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Modal: Reject Request -->
                        <div class="modal fade" id="modalReject<?php echo $req['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content chandila-modal">
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="reject_project">
                                        <input type="hidden" name="project_id" value="<?php echo $req['id']; ?>">

                                        <div class="modal-header border-danger">
                                            <h5 class="modal-title text-danger fw-bold"><i class="fa-solid fa-ban me-2"></i> Reject Project Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4">
                                            <p class="text-dark">Are you sure you want to reject project request <strong><?php echo htmlspecialchars($req['job_id']); ?></strong>?</p>
                                            <div class="mb-3">
                                                <label class="form-label text-dark small fw-semibold">Rejection Reason</label>
                                                <textarea class="form-control form-control-chandila" name="reject_reason" rows="3" placeholder="Provide reason for internal records..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="submit" class="btn btn-danger">Confirm Rejection</button>
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

