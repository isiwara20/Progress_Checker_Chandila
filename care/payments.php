<?php
$careTitle = 'Payment Settlement Desk';
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

// Handle Record / Mark Payment Done Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please try again.');
    } else {
        $action = $_POST['action'];

        // 1. Record New Payment Done
        if ($action === 'record_payment_done') {
            $projectId = (int)($_POST['project_id'] ?? 0);
            $stage     = trim($_POST['payment_stage'] ?? 'Advance Payment');
            $amount    = (float)($_POST['amount'] ?? 0);
            $method    = trim($_POST['payment_method'] ?? 'Bank Transfer');
            $reference = trim($_POST['transaction_reference'] ?? '');
            $notes     = trim($_POST['notes'] ?? '');
            $status    = trim($_POST['status'] ?? 'Payment Received');
            $notify    = !empty($_POST['notify_customer']);

            if (!$projectId || $amount <= 0) {
                setFlash('error', 'Please select a project and enter a valid payment amount.');
            } else {
                $stmtProj = $pdo->prepare("
                    SELECT p.*, c.full_name AS customer_name, c.email AS customer_email, c.phone, c.whatsapp_number
                    FROM projects p
                    JOIN customers c ON p.customer_id = c.id
                    WHERE p.id = ?
                ");
                $stmtProj->execute([$projectId]);
                $proj = $stmtProj->fetch();

                if ($proj) {
                    $verifiedBy = ($status === 'Payment Received') ? $currentUser['id'] : null;
                    $verifiedAt = ($status === 'Payment Received') ? date('Y-m-d H:i:s') : null;

                    $insPay = $pdo->prepare("
                        INSERT INTO payments (
                            project_id, payment_stage, amount, payment_method, transaction_reference,
                            status, admin_notes, verified_by, verified_at, payment_date, created_at
                        ) VALUES (
                            ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, NOW(), NOW()
                        )
                    ");
                    $insPay->execute([
                        $projectId, $stage, $amount, $method, $reference,
                        $status, $notes, $verifiedBy, $verifiedAt
                    ]);
                    $paymentId = (int)$pdo->lastInsertId();

                    // Recalculate totals
                    $calcStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE project_id = ? AND status = 'Payment Received'");
                    $calcStmt->execute([$projectId]);
                    $newTotalPaid = (float)$calcStmt->fetchColumn();
                    $newBalanceDue = max(0, $proj['total_cost'] - $newTotalPaid);

                    // Update project status stage if marked received
                    $newProjStatus = $proj['status'];
                    $invoiceStage = 'advance';

                    if ($status === 'Payment Received') {
                        if ($stage === 'Advance Payment') {
                            $invoiceStage = 'advance';
                            if (in_array($proj['status'], ['Request Submitted', 'Request Reviewed', 'Project Confirmed', 'Advance Payment Pending'])) {
                                $newProjStatus = !empty($proj['assigned_developer_id']) ? 'Development Started' : 'Advance Payment Confirmed';
                            }
                        } elseif ($stage === 'Second Payment (50% Milestone)') {
                            $invoiceStage = 'milestone2';
                            if ($proj['status'] === 'Second Payment Pending') {
                                $newProjStatus = 'Development in Progress';
                            }
                        } elseif ($stage === 'Final Payment' || $newBalanceDue <= 0) {
                            $invoiceStage = 'final';
                            if ($newBalanceDue <= 0 && $proj['status'] === 'Development Completed') {
                                $newProjStatus = 'Project Delivered';
                            }
                        }
                    }

                    $upProj = $pdo->prepare("UPDATE projects SET total_paid = ?, balance_due = ?, status = ? WHERE id = ?");
                    $upProj->execute([$newTotalPaid, $newBalanceDue, $newProjStatus, $projectId]);

                    // Audit Activity Log
                    $logMsg = "Payment of " . formatCurrency($amount) . " ({$stage}) marked as {$status} by Customer Care agent {$currentUser['full_name']}.";
                    logActivity($pdo, $proj['job_id'], $projectId, 'Payment Verified', $logMsg, $currentUser['full_name']);

                    // Dispatch Multi-Channel Notifications & Official Invoice
                    if ($notify && $status === 'Payment Received') {
                        $clientPhone = $proj['whatsapp_number'] ?: $proj['phone'];
                        $invoiceUrl = BASE_URL . '/invoice.php?job_id=' . urlencode($proj['job_id']) . '&stage=' . $invoiceStage;

                        if ($stage === 'Advance Payment') {
                            WhatsAppService::send($pdo, $clientPhone, 'Advance Payment Confirmed', [
                                'job_id'      => $proj['job_id'],
                                'amount_paid' => $amount,
                                'invoice_url' => $invoiceUrl
                            ], $projectId, $proj['job_id']);
                        } elseif ($stage === 'Second Payment (50% Milestone)') {
                            WhatsAppService::send($pdo, $clientPhone, 'Second Payment Confirmed', [
                                'job_id'      => $proj['job_id'],
                                'amount_paid' => $amount,
                                'invoice_url' => $invoiceUrl
                            ], $projectId, $proj['job_id'], true);
                        } else {
                            WhatsAppService::send($pdo, $clientPhone, 'Final Payment Confirmed', [
                                'job_id'      => $proj['job_id'],
                                'amount_paid' => $amount,
                                'invoice_url' => $invoiceUrl
                            ], $projectId, $proj['job_id'], true);
                        }

                        // Send Email Invoice
                        if (!empty($proj['customer_email'])) {
                            EmailService::send($pdo, $proj['customer_email'], "Payment Received & Official Invoice – {$proj['job_id']}", [
                                'customer_name'  => $proj['customer_name'],
                                'job_id'         => $proj['job_id'],
                                'project_title'  => $proj['project_title'],
                                'payment_stage'  => $stage,
                                'amount_paid'    => $amount,
                                'total_paid'     => $newTotalPaid,
                                'balance_due'    => $newBalanceDue,
                                'invoice_url'    => $invoiceUrl
                            ], $projectId, $proj['job_id'], false, $proj['customer_name']);
                        }
                    }

                    setFlash('success', "Payment of " . formatCurrency($amount) . " marked done for {$proj['job_id']}. Official invoice sent to customer!");
                    header("Location: " . BASE_URL . "/care/payments.php");
                    exit;
                }
            }
        }

        // 2. Quick Verify Existing Pending Slip
        if ($action === 'verify_existing_payment') {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'Payment Received';

            $stmtPay = $pdo->prepare("SELECT p.*, pr.job_id, pr.status as proj_status, pr.total_cost, pr.customer_id FROM payments p JOIN projects pr ON p.project_id = pr.id WHERE p.id = ?");
            $stmtPay->execute([$paymentId]);
            $payment = $stmtPay->fetch();

            if ($payment) {
                $verifiedBy = ($newStatus === 'Payment Received') ? $currentUser['id'] : null;
                $verifiedAt = ($newStatus === 'Payment Received') ? date('Y-m-d H:i:s') : null;

                $pdo->prepare("UPDATE payments SET status = ?, verified_by = ?, verified_at = ? WHERE id = ?")->execute([$newStatus, $verifiedBy, $verifiedAt, $paymentId]);

                // Recalculate totals
                $calcStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE project_id = ? AND status = 'Payment Received'");
                $calcStmt->execute([$payment['project_id']]);
                $newTotalPaid = (float)$calcStmt->fetchColumn();
                $newBalanceDue = max(0, $payment['total_cost'] - $newTotalPaid);

                $newProjStatus = $payment['proj_status'];
                if ($newStatus === 'Payment Received') {
                    if ($payment['payment_stage'] === 'Advance Payment') {
                        if (in_array($payment['proj_status'], ['Request Submitted', 'Request Reviewed', 'Project Confirmed', 'Advance Payment Pending'])) {
                            $newProjStatus = 'Advance Payment Confirmed';
                        }
                    } elseif ($payment['payment_stage'] === 'Second Payment (50% Milestone)') {
                        if ($payment['proj_status'] === 'Second Payment Pending') {
                            $newProjStatus = 'Development in Progress';
                        }
                    }
                }

                $pdo->prepare("UPDATE projects SET total_paid = ?, balance_due = ?, status = ? WHERE id = ?")->execute([$newTotalPaid, $newBalanceDue, $newProjStatus, $payment['project_id']]);

                logActivity($pdo, $payment['job_id'], (int)$payment['project_id'], 'Payment Verified', "Payment of " . formatCurrency($payment['amount']) . " verified as {$newStatus} by Customer Care.", $currentUser['full_name']);

                setFlash('success', "Payment #{$paymentId} marked as '{$newStatus}'. Project balance updated.");
                header("Location: " . BASE_URL . "/care/payments.php");
                exit;
            }
        }
    }
}

// Financial KPI Metrics
$totalVerifiedRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Payment Received'")->fetchColumn();
$pendingPaymentAmount = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Pending'")->fetchColumn();
$totalOutstandingBal  = (float)$pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM projects WHERE status != 'Cancelled'")->fetchColumn();

// Fetch Active Projects for the "Mark Payment Done" Dropdown
$activeProjectsList = $pdo->query("
    SELECT p.id, p.job_id, p.project_title, p.total_cost, p.total_paid, p.balance_due,
           p.advance_amount, p.milestone2_amount, p.final_amount,
           c.full_name AS customer_name, c.company_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.status != 'Cancelled'
    ORDER BY p.created_at DESC
")->fetchAll();

// Fetch Payments Table with Filters
$stageFilter = trim($_GET['stage'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if (!empty($stageFilter)) {
    $where[] = "pm.payment_stage = ?";
    $params[] = $stageFilter;
}
if (!empty($statusFilter)) {
    $where[] = "pm.status = ?";
    $params[] = $statusFilter;
}
if (!empty($search)) {
    $where[] = "(p.job_id LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ? OR pm.transaction_reference LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmtPayments = $pdo->prepare("
    SELECT pm.*, p.job_id, p.project_title, p.total_cost, p.balance_due, 
           c.full_name AS customer_name, c.company_name, u.full_name AS verified_by_name
    FROM payments pm
    JOIN projects p ON pm.project_id = p.id
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON pm.verified_by = u.id
    {$whereSql}
    ORDER BY pm.payment_date DESC
    LIMIT 100
");
$stmtPayments->execute($params);
$payments = $stmtPayments->fetchAll();

// Pre-selected project ID from GET if any
$selectedProjectId = (int)($_GET['project_id'] ?? 0);

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Header with Action -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-credit-card text-success me-2"></i> Payment Settlement Desk</h3>
        <p class="text-secondary small mb-0">Record and mark customer payments as done, verify bank slips, update balances, and dispatch official invoices.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-success btn-sm text-white" data-bs-toggle="modal" data-bs-target="#modalRecordPayment">
            <i class="fa-solid fa-receipt me-1"></i> Mark Payment Done
        </button>
        <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-electric btn-sm">
            <i class="fa-solid fa-cart-plus me-1"></i> Enter New Order
        </a>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Total Verified Collections</div>
            <div class="stat-card-value text-success"><?php echo formatCurrency($totalVerifiedRevenue); ?></div>
            <div class="text-secondary small mt-1">Cleared advance, milestone & final payments</div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-vault"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Pending Slip Verifications</div>
            <div class="stat-card-value text-warning"><?php echo formatCurrency($pendingPaymentAmount); ?></div>
            <div class="text-secondary small mt-1">Awaiting statement confirmation</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-hourglass-half"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #3b82f6;">
            <div class="stat-card-title text-primary">Outstanding Portfolio Balance</div>
            <div class="stat-card-value text-primary"><?php echo formatCurrency($totalOutstandingBal); ?></div>
            <div class="text-secondary small mt-1">Due across active client projects</div>
            <div class="stat-icon-wrapper text-primary"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Job ID, Customer, or Ref..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="stage">
                <option value="">-- All Milestones --</option>
                <?php foreach (PAYMENT_STAGES as $stg): ?>
                    <option value="<?php echo htmlspecialchars($stg); ?>" <?php echo ($stageFilter === $stg) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stg); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="status">
                <option value="">-- All Verification Statuses --</option>
                <option value="Payment Received" <?php echo ($statusFilter === 'Payment Received') ? 'selected' : ''; ?>>Payment Received</option>
                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending Slip Verification</option>
                <option value="Payment Rejected" <?php echo ($statusFilter === 'Payment Rejected') ? 'selected' : ''; ?>>Payment Rejected</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($search) || !empty($stageFilter) || !empty($statusFilter)): ?>
                <a href="<?php echo BASE_URL; ?>/care/payments.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payments Master Table -->
<div class="admin-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="text-dark fw-bold mb-0"><i class="fa-solid fa-receipt text-primary me-2"></i> Payment Records & Settlements (<?php echo count($payments); ?>)</h5>
        <button type="button" class="btn btn-sm btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalRecordPayment">
            <i class="fa-solid fa-plus me-1"></i> Mark Payment Done
        </button>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Name</th>
                    <th>Milestone Stage</th>
                    <th>Amount</th>
                    <th>Method & Ref</th>
                    <th>Status</th>
                    <th>Recorded By</th>
                    <th>Date</th>
                    <th class="text-end">Actions & Invoices</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-receipt fa-3x text-secondary mb-2 d-block"></i>
                            No payment transactions found. Click <strong>"Mark Payment Done"</strong> to record a payment.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($p['company_name'] ?: 'Individual'); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($p['payment_stage']); ?>
                                </span>
                            </td>
                            <td class="fw-bold text-success fs-6">
                                <?php echo formatCurrency($p['amount']); ?>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><?php echo htmlspecialchars($p['payment_method']); ?></div>
                                <?php if (!empty($p['transaction_reference'])): ?>
                                    <div class="small text-muted font-monospace"><?php echo htmlspecialchars($p['transaction_reference']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'Payment Received'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Payment Received</span>
                                <?php elseif ($p['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-clock me-1"></i> Pending Slip</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-xmark me-1"></i> Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary">
                                <?php echo htmlspecialchars($p['verified_by_name'] ?? 'Care Agent'); ?>
                            </td>
                            <td class="small text-secondary">
                                <?php echo formatDateOnly($p['payment_date']); ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($p['status'] === 'Pending'): ?>
                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="d-inline">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="verify_existing_payment">
                                            <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                                            <input type="hidden" name="status" value="Payment Received">
                                            <button type="submit" class="btn btn-sm btn-success text-white" title="Mark Verified / Paid">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- View / Print Official Customer Invoice -->
                                    <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($p['job_id']); ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View Customer Invoice with Official Stamp">
                                        <i class="fa-solid fa-file-invoice me-1"></i> Invoice
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

<!-- Modal: Mark Payment Done -->
<div class="modal fade" id="modalRecordPayment" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="record_payment_done">

                <div class="modal-header border-bottom">
                    <h5 class="modal-title text-dark fw-bold">
                        <i class="fa-solid fa-credit-card text-success me-2"></i> Mark Customer Payment Done
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Project Selector -->
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Select Customer Project <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="project_id" id="modalProjectSelect" required>
                            <option value="">-- Choose Project --</option>
                            <?php foreach ($activeProjectsList as $ap): ?>
                                <option value="<?php echo $ap['id']; ?>"
                                    data-advance="<?php echo $ap['advance_amount']; ?>"
                                    data-m2="<?php echo $ap['milestone2_amount']; ?>"
                                    data-final="<?php echo $ap['final_amount']; ?>"
                                    data-balance="<?php echo $ap['balance_due']; ?>"
                                    <?php echo ($selectedProjectId === (int)$ap['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ap['job_id']); ?> • <?php echo htmlspecialchars($ap['customer_name']); ?> (Due: <?php echo formatCurrency($ap['balance_due']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Milestone Stage Selector -->
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Payment Milestone Stage <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="payment_stage" id="modalStageSelect" required>
                            <option value="Advance Payment">Advance Payment (Initial Deposit)</option>
                            <option value="Second Payment (50% Milestone)">Second Payment (50% Milestone)</option>
                            <option value="Final Payment">Final Payment (Delivery & Hosting Settlement)</option>
                            <option value="Other">Other / Additional Scope</option>
                        </select>
                    </div>

                    <!-- Payment Amount -->
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Amount Received (<?php echo getCurrencySymbol(); ?>) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control form-control-chandila fw-bold text-success fs-5" name="amount" id="modalAmountInput" placeholder="0.00" required>
                        <div class="form-text small" id="suggestedDueHint">Select project to see milestone suggested amount</div>
                    </div>

                    <!-- Method & Reference -->
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label text-dark small fw-semibold">Payment Method <span class="text-danger">*</span></label>
                            <select class="form-select form-select-chandila" name="payment_method" required>
                                <option value="Bank Transfer">Bank Transfer (Commercial Bank)</option>
                                <option value="Cash Deposit">Cash Deposit / Cashier</option>
                                <option value="PayHere Online">PayHere Online Gateway</option>
                                <option value="Cheque">Bank Cheque</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-dark small fw-semibold">Slip / Reference No.</label>
                            <input type="text" class="form-control form-control-chandila" name="transaction_reference" placeholder="e.g. TXN-894123 / Cashier">
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Settlement Verification Status</label>
                        <select class="form-select form-select-chandila" name="status">
                            <option value="Payment Received" selected>Payment Received (Verified & Cleared)</option>
                            <option value="Pending">Pending (Slip uploaded, awaiting bank check)</option>
                        </select>
                    </div>

                    <!-- Internal Notes -->
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Customer Care Notes</label>
                        <input type="text" class="form-control form-control-chandila" name="notes" placeholder="e.g. Cleared via Commercial Bank online transfer.">
                    </div>

                    <!-- Auto-Notify Customer Checkbox -->
                    <div class="form-check form-switch p-3 bg-light rounded border mb-0">
                        <input class="form-check-input" type="checkbox" id="notifyCustCheck" name="notify_customer" value="1" checked>
                        <label class="form-check-label text-dark fw-bold small" for="notifyCustCheck">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i> Send Official Invoice to Customer via WhatsApp & Email
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success text-white px-4">
                        <i class="fa-solid fa-check-circle me-1"></i> Confirm & Mark Done
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var projSelect = document.getElementById('modalProjectSelect');
    var stageSelect = document.getElementById('modalStageSelect');
    var amountInput = document.getElementById('modalAmountInput');
    var hint = document.getElementById('suggestedDueHint');

    function updateSuggestedAmount() {
        if (!projSelect || !stageSelect || !amountInput) return;
        var opt = projSelect.options[projSelect.selectedIndex];
        if (!opt || !opt.value) {
            hint.textContent = 'Select project to see milestone suggested amount';
            return;
        }

        var stage = stageSelect.value;
        var adv = parseFloat(opt.dataset.advance) || 0;
        var m2  = parseFloat(opt.dataset.m2) || 0;
        var fnl = parseFloat(opt.dataset.final) || 0;
        var bal = parseFloat(opt.dataset.balance) || 0;

        if (stage === 'Advance Payment' && adv > 0) {
            amountInput.value = adv.toFixed(2);
            hint.textContent = 'Standard Advance Deposit: ' + adv.toFixed(2);
        } else if (stage === 'Second Payment (50% Milestone)' && m2 > 0) {
            amountInput.value = m2.toFixed(2);
            hint.textContent = '50% Milestone Amount: ' + m2.toFixed(2);
        } else if (stage === 'Final Payment' && fnl > 0) {
            amountInput.value = fnl.toFixed(2);
            hint.textContent = 'Final Balance Due: ' + bal.toFixed(2);
        } else {
            amountInput.value = bal.toFixed(2);
            hint.textContent = 'Remaining Balance Due: ' + bal.toFixed(2);
        }
    }

    if (projSelect && stageSelect) {
        projSelect.addEventListener('change', updateSuggestedAmount);
        stageSelect.addEventListener('change', updateSuggestedAmount);
    }

    <?php if ($selectedProjectId): ?>
        var myModal = new bootstrap.Modal(document.getElementById('modalRecordPayment'));
        myModal.show();
        updateSuggestedAmount();
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

