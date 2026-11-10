<?php
$adminTitle = 'Project Management Command Center';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

$projectId = (int)($_GET['id'] ?? 0);
if (!$projectId) {
    setFlash('error', 'Project not found.');
    header("Location: " . BASE_URL . "/admin/projects.php");
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
           u.email AS dev_email
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.id = ?
");
$stmt->execute([$projectId]);
$project = $stmt->fetch();

if (!$project) {
    setFlash('error', 'Project record does not exist.');
    header("Location: " . BASE_URL . "/admin/projects.php");
    exit;
}

// Handle Form Submissions (Status update, financials, payments, developer assignment, hosting)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        // 1. Update Scope & Pricing Schedule
        if ($action === 'update_pricing_schedule') {
            $totalCost   = (float)($_POST['total_cost'] ?? 0);
            $advanceAmt  = (float)($_POST['advance_amount'] ?? 0);
            $milestone2  = (float)($_POST['milestone2_amount'] ?? 0);
            $finalAmt    = (float)($_POST['final_amount'] ?? 0);
            $targetDate  = $_POST['expected_completion_date'] ?? null;
            $devId       = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;

            $totalPaid = (float)$project['total_paid'];
            $balanceDue = max(0, $totalCost - $totalPaid);

            $up = $pdo->prepare("
                UPDATE projects SET 
                    total_cost = ?,
                    advance_amount = ?,
                    milestone2_amount = ?,
                    final_amount = ?,
                    balance_due = ?,
                    expected_completion_date = ?,
                    assigned_developer_id = ?
                WHERE id = ?
            ");
            $up->execute([$totalCost, $advanceAmt, $milestone2, $finalAmt, $balanceDue, $targetDate, $devId, $projectId]);

            // If developer changed
            if ($devId && $devId != $project['assigned_developer_id']) {
                $pdo->prepare("UPDATE projects SET assigned_date = NOW() WHERE id = ?")->execute([$projectId]);
                WhatsAppService::send($pdo, $project['whatsapp_number'] ?: $project['customer_phone'], 'Project Assigned', ['job_id' => $project['job_id']], $projectId, $project['job_id']);
                logActivity($pdo, $project['job_id'], $projectId, 'Developer Assigned', "Assigned to developer ID {$devId}.", $currentUser['full_name']);
            }

            logActivity($pdo, $project['job_id'], $projectId, 'Pricing Updated', "Updated project cost to " . formatCurrency($totalCost), $currentUser['full_name']);
            setFlash('success', 'Project pricing and schedule updated successfully.');
            header("Location: " . BASE_URL . "/admin/project_view.php?id=" . $projectId);
            exit;
        }

        // 2. Update 12-Stage Project Status
        if ($action === 'update_project_status') {
            $newStatus = trim($_POST['status'] ?? '');
            $progressPct = (int)($_POST['progress_percentage'] ?? $project['progress_percentage']);
            $statusNote = trim($_POST['status_note'] ?? '');
            $notifyWa = !empty($_POST['notify_whatsapp']);

            if (in_array($newStatus, PROJECT_STAGES, true) || $newStatus === 'Cancelled') {
                $up = $pdo->prepare("
                    UPDATE projects SET 
                        status = ?, 
                        progress_percentage = ?,
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $up->execute([$newStatus, $progressPct, $projectId]);

                // Record progress log
                $progStmt = $pdo->prepare("
                    INSERT INTO project_progress (project_id, percentage, status_stage, description, internal_note, updated_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $desc = !empty($statusNote) ? $statusNote : "Status updated to {$newStatus}. Progress at {$progressPct}%.";
                $progStmt->execute([$projectId, $progressPct, $newStatus, $desc, "Admin status change.", $currentUser['id']]);

                // Log activity
                logActivity($pdo, $project['job_id'], $projectId, 'Status Updated', "Status changed to '{$newStatus}' ({$progressPct}%).", $currentUser['full_name']);

                // WhatsApp Event Triggers based on stage
                $recipientPhone = $project['whatsapp_number'] ?: $project['customer_phone'];
                if ($newStatus === 'Development Completed') {
                    WhatsAppService::send($pdo, $recipientPhone, 'Development Completed', ['job_id' => $project['job_id']], $projectId, $project['job_id']);
                } elseif ($notifyWa) {
                    WhatsAppService::send($pdo, $recipientPhone, 'Project Progress Updated', [
                        'job_id'              => $project['job_id'],
                        'progress_percentage' => $progressPct,
                        'latest_update'       => $desc
                    ], $projectId, $project['job_id']);
                }

                setFlash('success', "Project status updated to '{$newStatus}'.");
                header("Location: " . BASE_URL . "/admin/project_view.php?id=" . $projectId);
                exit;
            }
        }

        // 3. Record / Verify Payment
        if ($action === 'record_payment') {
            $stage     = trim($_POST['payment_stage'] ?? 'Advance Payment');
            $amount    = (float)($_POST['amount'] ?? 0);
            $method    = trim($_POST['payment_method'] ?? 'Bank Transfer');
            $ref       = trim($_POST['transaction_reference'] ?? '');
            $status    = trim($_POST['payment_status'] ?? 'Payment Received');
            $adminNote = trim($_POST['admin_notes'] ?? '');

            if ($amount <= 0) {
                setFlash('error', 'Payment amount must be greater than zero.');
            } else {
                $receiptPath = null;
                if (isset($_FILES['receipt']) && $_FILES['receipt']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $upRes = handleSecureUpload($_FILES['receipt'], $projectId, 'admin');
                    if ($upRes['success']) {
                        $receiptPath = $upRes['file_path'];
                    }
                }

                $verifiedBy = ($status === 'Payment Received') ? $currentUser['id'] : null;
                $verifiedAt = ($status === 'Payment Received') ? date('Y-m-d H:i:s') : null;

                $insPay = $pdo->prepare("
                    INSERT INTO payments (project_id, payment_stage, amount, payment_method, transaction_reference, receipt_file, status, admin_notes, verified_by, verified_at, payment_date, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $insPay->execute([$projectId, $stage, $amount, $method, $ref, $receiptPath, $status, $adminNote, $verifiedBy, $verifiedAt]);

                // Recalculate Project total_paid and balance_due
                $calcStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE project_id = ? AND status = 'Payment Received'");
                $calcStmt->execute([$projectId]);
                $newTotalPaid = (float)$calcStmt->fetchColumn();
                $newBalanceDue = max(0, $project['total_cost'] - $newTotalPaid);

                // Auto update status if advance verified
                $newProjStatus = $project['status'];
                if ($stage === 'Advance Payment' && $status === 'Payment Received') {
                    if (in_array($project['status'], ['Request Submitted', 'Request Reviewed', 'Project Confirmed', 'Advance Payment Pending'])) {
                        $newProjStatus = 'Advance Payment Confirmed';
                    }
                    // Send WhatsApp Event 3
                    WhatsAppService::send($pdo, $project['whatsapp_number'] ?: $project['customer_phone'], 'Advance Payment Confirmed', ['job_id' => $project['job_id']], $projectId, $project['job_id']);
                }

                $upP = $pdo->prepare("UPDATE projects SET total_paid = ?, balance_due = ?, status = ? WHERE id = ?");
                $upP->execute([$newTotalPaid, $newBalanceDue, $newProjStatus, $projectId]);

                logActivity($pdo, $project['job_id'], $projectId, 'Payment Recorded', "Payment of " . formatCurrency($amount) . " recorded for {$stage} ({$status}).", $currentUser['full_name']);

                setFlash('success', "Payment of " . formatCurrency($amount) . " recorded and verified successfully.");
                header("Location: " . BASE_URL . "/admin/project_view.php?id=" . $projectId);
                exit;
            }
        }

        // 4. Send WhatsApp Reminders (Milestone 2 or Final Payment)
        if ($action === 'send_whatsapp_reminder') {
            $reminderType = $_POST['reminder_type'] ?? '';
            $phone = $project['whatsapp_number'] ?: $project['customer_phone'];

            if ($reminderType === 'milestone_2') {
                WhatsAppService::send($pdo, $phone, 'Second Payment Reminder', ['job_id' => $project['job_id']], $projectId, $project['job_id'], true);
                logActivity($pdo, $project['job_id'], $projectId, 'WhatsApp Reminder', 'Dispatched Second Payment Milestone WhatsApp reminder to customer.', $currentUser['full_name']);
                setFlash('success', 'Second milestone payment WhatsApp reminder sent!');
            } elseif ($reminderType === 'final_payment') {
                WhatsAppService::send($pdo, $phone, 'Final Payment Reminder', ['job_id' => $project['job_id']], $projectId, $project['job_id'], true);
                logActivity($pdo, $project['job_id'], $projectId, 'WhatsApp Reminder', 'Dispatched Final Payment WhatsApp reminder to customer.', $currentUser['full_name']);
                setFlash('success', 'Final payment WhatsApp reminder sent!');
            }
            header("Location: " . BASE_URL . "/admin/project_view.php?id=" . $projectId);
            exit;
        }

        // 5. Website Hosting & Final Delivery
        if ($action === 'complete_hosting_delivery') {
            $hostingUrl = trim($_POST['hosting_url'] ?? '');
            $isDelivered = !empty($_POST['mark_delivered']) ? 1 : 0;

            if (empty($hostingUrl)) {
                setFlash('error', 'Please provide the live hosting URL.');
            } else {
                $statusToSet = $isDelivered ? 'Project Delivered' : 'Hosting & Deployment';
                $deployedDate = date('Y-m-d H:i:s');
                $deliveredDate = $isDelivered ? date('Y-m-d H:i:s') : null;

                $up = $pdo->prepare("
                    UPDATE projects SET 
                        hosting_url = ?,
                        hosting_deployed_date = ?,
                        delivered_date = ?,
                        status = ?,
                        progress_percentage = 100
                    WHERE id = ?
                ");
                $up->execute([$hostingUrl, $deployedDate, $deliveredDate, $statusToSet, $projectId]);

                // Send WhatsApp Event 9: Hosting Completed
                $phone = $project['whatsapp_number'] ?: $project['customer_phone'];
                WhatsAppService::send($pdo, $phone, 'Hosting Completed', ['job_id' => $project['job_id'], 'hosting_url' => $hostingUrl], $projectId, $project['job_id']);

                logActivity($pdo, $project['job_id'], $projectId, 'Hosting Deployed', "Website hosted live at {$hostingUrl}. Status: {$statusToSet}.", $currentUser['full_name']);
                setFlash('success', "Hosting details saved and WhatsApp notification sent to customer!");
                header("Location: " . BASE_URL . "/admin/project_view.php?id=" . $projectId);
                exit;
            }
        }
    }
}

// Fetch Related Records
$developers = $pdo->query("SELECT id, full_name, skills FROM users WHERE role = 'developer' AND status = 'active' ORDER BY full_name ASC")->fetchAll();

$milestones = $pdo->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY target_percentage ASC");
$milestones->execute([$projectId]);
$milestoneList = $milestones->fetchAll();

$progressList = $pdo->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM project_progress p 
    LEFT JOIN users u ON p.updated_by = u.id 
    WHERE p.project_id = ? 
    ORDER BY p.created_at DESC
");
$progressList->execute([$projectId]);
$progressHistory = $progressList->fetchAll();

$paymentList = $pdo->prepare("
    SELECT p.*, u.full_name as verified_by_name 
    FROM payments p 
    LEFT JOIN users u ON p.verified_by = u.id 
    WHERE p.project_id = ? 
    ORDER BY p.payment_date DESC
");
$paymentList->execute([$projectId]);
$payments = $paymentList->fetchAll();

$activities = $pdo->prepare("SELECT * FROM project_activities WHERE project_id = ? ORDER BY created_at DESC");
$activities->execute([$projectId]);
$activityList = $activities->fetchAll();

$files = $pdo->prepare("SELECT * FROM uploaded_files WHERE project_id = ? ORDER BY created_at DESC");
$files->execute([$projectId]);
$fileList = $files->fetchAll();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Project Command Header -->
<div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace fs-6">
                <i class="fa-solid fa-barcode me-1"></i> <?php echo htmlspecialchars($project['job_id']); ?>
            </span>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type']); ?></span>
            <?php echo getStatusBadge($project['status']); ?>
        </div>
        <h2 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($project['project_title']); ?></h2>
        <div class="text-muted small">
            Customer: <strong><?php echo htmlspecialchars($project['customer_name']); ?></strong> 
            (<?php echo htmlspecialchars($project['customer_phone']); ?>) • Target: <?php echo formatDateOnly($project['expected_completion_date']); ?>
        </div>
    </div>

    <!-- Quick Action Modals Triggers -->
    <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalStatusUpdate">
            <i class="fa-solid fa-arrows-spin me-1"></i> Update Status
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" data-bs-toggle="modal" data-bs-target="#modalRecordPayment">
            <i class="fa-solid fa-money-bill-wave me-1"></i> Record Payment
        </button>
        <button type="button" class="btn btn-electric btn-sm" data-bs-toggle="modal" data-bs-target="#modalHostingDeployment">
            <i class="fa-solid fa-server me-1"></i> Hosting & Launch
        </button>
    </div>
</div>

<!-- Progress Bar Banner -->
<div class="admin-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-dark fw-bold"><i class="fa-solid fa-gauge text-primary me-2"></i> Current Development Progress</span>
        <span class="text-primary fw-bold fs-5"><?php echo (int)$project['progress_percentage']; ?>% Completed</span>
    </div>
    <div class="progress" style="height: 14px; background-color: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 20px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" 
             style="width: <?php echo (int)$project['progress_percentage']; ?>%; background: linear-gradient(90deg, #d97706, #2563eb);" 
             role="progressbar"></div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Requirements, Attachments, Progress History, Audit Log -->
    <div class="col-lg-8">
        <!-- Project Scope Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-file-lines text-primary"></i> Project Specifications & Scope</h5>
                <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type']); ?></span>
            </div>
            <div class="admin-card-body">
                <div class="mb-4">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2">Detailed Project Description</h6>
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; font-size: 0.92rem; white-space: pre-line;">
                        <?php echo htmlspecialchars($project['description']); ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2">Required Features & Modules</h6>
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; font-size: 0.92rem; white-space: pre-line;">
                        <?php echo htmlspecialchars($project['required_features']); ?>
                    </div>
                </div>

                <div class="row g-3">
                    <?php if (!empty($project['reference_url'])): ?>
                        <div class="col-md-6">
                            <span class="text-secondary small">Reference Website:</span>
                            <div>
                                <a href="<?php echo htmlspecialchars($project['reference_url']); ?>" target="_blank" class="text-info fw-semibold text-break">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> <?php echo htmlspecialchars($project['reference_url']); ?>
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($project['additional_requirements'])): ?>
                        <div class="col-12 mt-3">
                            <span class="text-secondary small">Additional Requirements:</span>
                            <div class="text-dark small mt-1"><?php echo nl2br(htmlspecialchars($project['additional_requirements'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Attachments Section -->
                <div class="mt-4 pt-3 border-top">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2"><i class="fa-solid fa-paperclip text-primary me-1"></i> Uploaded Project Attachments</h6>
                    <?php if (empty($fileList)): ?>
                        <div class="text-secondary small fst-italic">No attachments uploaded for this project.</div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($fileList as $f): ?>
                                <a href="<?php echo BASE_URL . '/' . htmlspecialchars($f['file_path']); ?>" target="_blank" download class="btn btn-sm btn-outline-primary">
                                    <i class="fa-solid fa-download me-1"></i> <?php echo htmlspecialchars($f['original_name']); ?> 
                                    <span class="text-secondary" style="font-size: 0.7rem;">(<?php echo round($f['file_size'] / 1024); ?> KB)</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 3-Stage Payment History & Verification -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-receipt text-info"></i> Payment Transactions & Milestone History</h5>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalRecordPayment">
                    <i class="fa-solid fa-plus me-1"></i> Record Payment
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-chandila mb-0">
                    <thead>
                        <tr>
                            <th>Payment Stage</th>
                            <th>Amount</th>
                            <th>Method & Ref</th>
                            <th>Status</th>
                            <th>Verified By</th>
                            <th>Date</th>
                            <th>Slip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payments)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">No payment transactions recorded for this project yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($pay['payment_stage']); ?></td>
                                    <td class="fw-bold text-success"><?php echo formatCurrency($pay['amount']); ?></td>
                                    <td>
                                        <div class="text-dark small"><?php echo htmlspecialchars($pay['payment_method']); ?></div>
                                        <?php if (!empty($pay['transaction_reference'])): ?>
                                            <div class="text-secondary" style="font-size: 0.75rem;"><?php echo htmlspecialchars($pay['transaction_reference']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($pay['status'] === 'Payment Received'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Received</span>
                                        <?php elseif ($pay['status'] === 'Pending'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-hourglass-half me-1"></i> Pending</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-xmark me-1"></i> Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?php echo htmlspecialchars($pay['verified_by_name'] ?? 'System/Unverified'); ?></td>
                                    <td class="small text-secondary"><?php echo formatDateOnly($pay['payment_date']); ?></td>
                                    <td>
                                        <?php if (!empty($pay['receipt_file'])): ?>
                                            <a href="<?php echo BASE_URL . '/' . htmlspecialchars($pay['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-outline-info py-0 px-2">
                                                <i class="fa-solid fa-file-arrow-down"></i> Slip
                                            </a>
                                        <?php else: ?>
                                            <span class="text-secondary small">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Progress History Log & Developer Internal Notes -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-timeline text-info"></i> Progress Updates & Developer Notes</h5>
            </div>
            <div class="admin-card-body">
                <?php if (empty($progressHistory)): ?>
                    <div class="text-secondary text-center py-4 small">No progress logs added yet.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($progressHistory as $prog): ?>
                            <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-primary"><?php echo (int)$prog['percentage']; ?>%</span>
                                        <span class="fw-bold text-dark small"><?php echo htmlspecialchars($prog['status_stage']); ?></span>
                                    </div>
                                    <span class="text-muted small"><?php echo formatDateTime($prog['created_at']); ?></span>
                                </div>
                                <div class="text-dark small mb-2"><?php echo nl2br(htmlspecialchars($prog['description'])); ?></div>
                                <?php if (!empty($prog['internal_note'])): ?>
                                    <div class="p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-30 text-dark small">
                                        <i class="fa-solid fa-lock text-warning me-1"></i> <strong>Internal Note:</strong> <?php echo htmlspecialchars($prog['internal_note']); ?>
                                    </div>
                                <?php endif; ?>
                                <div class="text-muted small mt-1" style="font-size: 0.72rem;">
                                    By: <?php echo htmlspecialchars($prog['author_name'] ?? 'Admin'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Complete Audit Activity Log -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Project Activity Audit History</h5>
            </div>
            <div class="admin-card-body p-3">
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($activityList as $act): ?>
                        <div class="p-2 rounded d-flex justify-content-between align-items-center" style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #2563eb;">
                            <div>
                                <span class="badge bg-secondary font-monospace me-2" style="font-size: 0.7rem;"><?php echo htmlspecialchars($act['activity_type']); ?></span>
                                <span class="text-dark small"><?php echo htmlspecialchars($act['description']); ?></span>
                                <span class="text-muted small ms-2">· By <?php echo htmlspecialchars($act['updated_by']); ?></span>
                            </div>
                            <span class="text-muted small text-nowrap"><?php echo formatDateTime($act['created_at'], 'M d, h:i A'); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Financial Dashboard, Customer Info, Developer Assignment, Reminders -->
    <div class="col-lg-4">
        <!-- Customer Details Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-user-circle text-primary"></i> Customer Information</h5>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <span class="text-muted small">Full Name:</span>
                    <div class="text-dark fw-bold"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                </div>
                <?php if (!empty($project['company_name'])): ?>
                    <div class="mb-3">
                        <span class="text-muted small">Company:</span>
                        <div class="text-dark"><?php echo htmlspecialchars($project['company_name']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <span class="text-muted small">Email Address:</span>
                    <div><a href="mailto:<?php echo htmlspecialchars($project['customer_email']); ?>" class="text-primary"><?php echo htmlspecialchars($project['customer_email']); ?></a></div>
                </div>
                <div class="mb-3">
                    <span class="text-muted small">WhatsApp / Contact:</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-dark fw-semibold"><?php echo htmlspecialchars($project['whatsapp_number'] ?: $project['customer_phone']); ?></span>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $project['whatsapp_number'] ?: $project['customer_phone']); ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="Chat on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                </div>
                <?php if (!empty($project['customer_address'])): ?>
                    <div>
                        <span class="text-muted small">Billing Address:</span>
                        <div class="text-muted small"><?php echo nl2br(htmlspecialchars($project['customer_address'])); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Financial Summary Card (3-Stage Milestones) -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-wallet text-primary"></i> Financial Breakdown</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEditPricing">
                    <i class="fa-solid fa-pen"></i> Edit
                </button>
            </div>
            <div class="admin-card-body">
                <div class="p-3 rounded-3 mb-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Project Cost:</span>
                        <span class="text-dark fw-bold fs-5"><?php echo formatCurrency($project['total_cost']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted small">Total Verified Paid:</span>
                        <span class="text-success fw-bold"><?php echo formatCurrency($project['total_paid']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between pt-2 border-top">
                        <span class="text-muted small">Balance Due:</span>
                        <span class="text-warning fw-bold fs-6"><?php echo formatCurrency($project['balance_due']); ?></span>
                    </div>
                </div>

                <!-- 3 Stages Breakdown -->
                <div class="d-flex flex-column gap-2 small">
                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
                        <div>
                            <span class="text-dark fw-semibold">1. Advance Amount:</span>
                            <div class="text-muted" style="font-size: 0.72rem;">Due before dev kickoff</div>
                        </div>
                        <span class="text-dark fw-bold"><?php echo formatCurrency($project['advance_amount']); ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
                        <div>
                            <span class="text-dark fw-semibold">2. Second Payment (50%):</span>
                            <div class="text-muted" style="font-size: 0.72rem;">Milestone completion</div>
                        </div>
                        <span class="text-dark fw-bold"><?php echo formatCurrency($project['milestone2_amount']); ?></span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center p-2 rounded bg-light border">
                        <div>
                            <span class="text-dark fw-semibold">3. Final Outstanding:</span>
                            <div class="text-muted" style="font-size: 0.72rem;">Due prior to live hosting</div>
                        </div>
                        <span class="text-dark fw-bold"><?php echo formatCurrency($project['final_amount']); ?></span>
                    </div>
                </div>

                <!-- WhatsApp Milestone Reminders -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25">
                    <span class="text-secondary small fw-semibold text-uppercase d-block mb-2">Automated WhatsApp Reminders:</span>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST" class="d-flex flex-column gap-2">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="send_whatsapp_reminder">

                        <button type="submit" name="reminder_type" value="milestone_2" class="btn btn-sm btn-outline-warning text-start">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i> Send Second Milestone (50%) Reminder
                        </button>
                        <button type="submit" name="reminder_type" value="final_payment" class="btn btn-sm btn-outline-danger text-start">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i> Send Final Payment Reminder
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Assigned Developer & Workload -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-laptop-code text-info"></i> Assigned Developer</h5>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($project['assigned_developer_id'])): ?>
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-circle" style="width: 48px; height: 48px; font-size: 1.25rem;">
                            <i class="fa-solid fa-code"></i>
                        </div>
                        <div>
                            <div class="text-dark fw-bold"><?php echo htmlspecialchars($project['dev_name']); ?></div>
                            <div class="text-secondary small"><?php echo htmlspecialchars($project['dev_email']); ?></div>
                            <div class="text-primary small mt-1">Assigned: <?php echo formatDateOnly($project['assigned_date']); ?></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3 text-secondary">
                        <i class="fa-solid fa-user-xmark fa-2x mb-2 d-block text-secondary"></i>
                        No developer currently assigned.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Live Deployment Status -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-server text-primary"></i> Production Deployment</h5>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($project['hosting_url'])): ?>
                    <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success mb-3 text-center">
                        <div class="text-success small fw-bold mb-1"><i class="fa-solid fa-circle-check me-1"></i> Website Deployed Live</div>
                        <a href="<?php echo htmlspecialchars($project['hosting_url']); ?>" target="_blank" class="text-success fw-bold text-decoration-underline">
                            <?php echo htmlspecialchars($project['hosting_url']); ?> <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i>
                        </a>
                        <div class="text-secondary small mt-2">Deployed on: <?php echo formatDateOnly($project['hosting_deployed_date']); ?></div>
                    </div>
                <?php else: ?>
                    <div class="text-secondary small text-center py-3">
                        Project has not yet been deployed to live hosting. Complete final payment verification to unlock deployment.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= MODALS ================= -->

<!-- 1. Modal: Update 12-Stage Project Status -->
<div class="modal fade" id="modalStatusUpdate" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_project_status">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-arrows-spin text-primary me-2"></i> Update Project Status Stage</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Select 12-Stage Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="status" required>
                            <?php foreach (PROJECT_STAGES as $stage): ?>
                                <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo ($project['status'] === $stage) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($stage); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="Cancelled" <?php echo ($project['status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Progress Percentage (0 - 100%)</label>
                        <input type="number" min="0" max="100" class="form-control form-control-chandila" name="progress_percentage" value="<?php echo (int)$project['progress_percentage']; ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Public Progress Update Note</label>
                        <textarea class="form-control form-control-chandila" name="status_note" rows="3" placeholder="e.g. 60% – Shopping cart module completed and tested with live inventory..."></textarea>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notifyWaCheck" checked>
                        <label class="form-check-label text-dark small fw-bold" for="notifyWaCheck">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i> Send WhatsApp update to customer immediately
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-electric">Save Status & Send WhatsApp</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 2. Modal: Edit Pricing & Schedule -->
<div class="modal fade" id="modalEditPricing" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update_pricing_schedule">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-sliders text-primary me-2"></i> Configure Pricing & Milestones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Total Project Cost (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control form-control-chandila" id="calc_total_cost" name="total_cost" value="<?php echo $project['total_cost']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">1. Advance Amount (Rs.)</label>
                            <input type="number" step="0.01" class="form-control form-control-chandila" id="calc_advance_amount" name="advance_amount" value="<?php echo $project['advance_amount']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">2. Second Payment (50% Milestone) (Rs.)</label>
                            <input type="number" step="0.01" class="form-control form-control-chandila" id="calc_milestone2_amount" name="milestone2_amount" value="<?php echo $project['milestone2_amount']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">3. Final Outstanding Amount (Rs.)</label>
                            <input type="number" step="0.01" class="form-control form-control-chandila" id="calc_final_amount" name="final_amount" value="<?php echo $project['final_amount']; ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Target Completion Date</label>
                            <input type="date" class="form-control form-control-chandila" name="expected_completion_date" value="<?php echo htmlspecialchars($project['expected_completion_date']); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Assign Developer</label>
                            <select class="form-select form-select-chandila" name="developer_id">
                                <option value="">-- Unassigned --</option>
                                <?php foreach ($developers as $dev): ?>
                                    <option value="<?php echo $dev['id']; ?>" <?php echo ($project['assigned_developer_id'] == $dev['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dev['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-electric">Update Financials & Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 3. Modal: Record Payment -->
<div class="modal fade" id="modalRecordPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST" enctype="multipart/form-data">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="record_payment">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-receipt text-success me-2"></i> Record & Verify Customer Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Payment Milestone Stage <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="payment_stage" required>
                            <option value="Advance Payment">1. Advance Payment</option>
                            <option value="Second Payment (50% Milestone)">2. Second Payment (50% Milestone)</option>
                            <option value="Final Payment">3. Final Payment (Hosting & Delivery)</option>
                            <option value="Other">Other Adjustment</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Payment Amount (Rs.) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control form-control-chandila" name="amount" placeholder="e.g. 75000" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Payment Method</label>
                        <select class="form-select form-select-chandila" name="payment_method">
                            <option value="Bank Transfer">Commercial Bank / BOC Direct Transfer</option>
                            <option value="Online Banking Slip">Online Banking Slip</option>
                            <option value="Cash Deposit">Cash Deposit at Branch</option>
                            <option value="PayHere / Card">PayHere Online Gateway</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Transaction Reference / Slip Number</label>
                        <input type="text" class="form-control form-control-chandila" name="transaction_reference" placeholder="e.g. BOC-TXN-984321">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Verification Status <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="payment_status" required>
                            <option value="Payment Received">Payment Received (Verified & Approved)</option>
                            <option value="Pending">Pending Manual Verification</option>
                            <option value="Payment Rejected">Payment Rejected</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Upload Bank Slip / Receipt (Optional)</label>
                        <input type="file" class="form-control form-control-chandila" name="receipt">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Internal Admin Verification Note</label>
                        <textarea class="form-control form-control-chandila" name="admin_notes" rows="2" placeholder="Verified in Commercial Bank online statement..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-success">Save & Verify Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 4. Modal: Website Hosting & Final Delivery -->
<div class="modal fade" id="modalHostingDeployment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="complete_hosting_delivery">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-server text-primary me-2"></i> Website Hosting & Final Delivery</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Once development is complete and final payment verified, enter the live production URL. This triggers the official <strong>Hosting Completed WhatsApp Message</strong> to the customer.
                    </p>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Production Live Hosting URL <span class="text-danger">*</span></label>
                        <input type="url" class="form-control form-control-chandila" name="hosting_url" placeholder="https://clientdomain.lk" value="<?php echo htmlspecialchars($project['hosting_url'] ?? ''); ?>" required>
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="mark_delivered" value="1" id="markDeliveredCheck" checked>
                        <label class="form-check-label text-dark small fw-bold" for="markDeliveredCheck">
                            Mark Project Status as officially "Project Delivered"
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-electric">Confirm Hosting & Notify Customer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

