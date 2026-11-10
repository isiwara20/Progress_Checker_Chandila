<?php
$adminTitle = 'Payment Management System';
require_once __DIR__ . '/../includes/admin_header.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Handle Payment Verification / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        if ($action === 'verify_payment') {
            $paymentId = (int)($_POST['payment_id'] ?? 0);
            $newStatus = $_POST['status'] ?? 'Payment Received';

            $stmtPay = $pdo->prepare("SELECT p.*, pr.job_id, pr.status as proj_status, pr.total_cost, pr.customer_id FROM payments p JOIN projects pr ON p.project_id = pr.id WHERE p.id = ?");
            $stmtPay->execute([$paymentId]);
            $payment = $stmtPay->fetch();

            if ($payment) {
                $verifiedBy = ($newStatus === 'Payment Received') ? $currentUser['id'] : null;
                $verifiedAt = ($newStatus === 'Payment Received') ? date('Y-m-d H:i:s') : null;

                $pdo->prepare("UPDATE payments SET status = ?, verified_by = ?, verified_at = ? WHERE id = ?")->execute([$newStatus, $verifiedBy, $verifiedAt, $paymentId]);

                // Recalculate Project total_paid and balance_due
                $calcStmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE project_id = ? AND status = 'Payment Received'");
                $calcStmt->execute([$payment['project_id']]);
                $newTotalPaid = (float)$calcStmt->fetchColumn();
                $newBalanceDue = max(0, $payment['total_cost'] - $newTotalPaid);

                // If Advance payment is confirmed, update status to Advance Payment Confirmed & Development Started
                $newProjStatus = $payment['proj_status'];
                if ($payment['payment_stage'] === 'Advance Payment' && $newStatus === 'Payment Received') {
                    if (in_array($payment['proj_status'], ['Request Submitted', 'Request Reviewed', 'Project Confirmed', 'Advance Payment Pending'])) {
                        $newProjStatus = 'Advance Payment Confirmed';
                    }

                    // Get customer phone
                    $custPhone = $pdo->query("SELECT COALESCE(whatsapp_number, phone) FROM customers WHERE id = {$payment['customer_id']}")->fetchColumn();
                    WhatsAppService::send($pdo, $custPhone, 'Advance Payment Confirmed', ['job_id' => $payment['job_id']], $payment['project_id'], $payment['job_id']);
                }

                $pdo->prepare("UPDATE projects SET total_paid = ?, balance_due = ?, status = ? WHERE id = ?")->execute([$newTotalPaid, $newBalanceDue, $newProjStatus, $payment['project_id']]);

                logActivity($pdo, $payment['job_id'], (int)$payment['project_id'], 'Payment Verified', "Payment of " . formatCurrency($payment['amount']) . " marked as {$newStatus}.", $currentUser['full_name']);

                setFlash('success', "Payment {$paymentId} marked as '{$newStatus}'. Project balance updated.");
                header("Location: " . BASE_URL . "/admin/payments.php");
                exit;
            }
        }
    }
}

// Fetch Overall Financial Metrics
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Payment Received'")->fetchColumn();
$pendingPaymentAmount = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Pending'")->fetchColumn();
$totalOutstandingBalance = (float)$pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM projects WHERE status != 'Cancelled'")->fetchColumn();

// Fetch Payments with Project & Customer info
$stageFilter = trim($_GET['stage'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

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
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT pm.*, p.job_id, p.project_title, p.total_cost, p.balance_due, c.full_name AS customer_name, c.company_name, u.full_name AS verified_by_name
    FROM payments pm
    JOIN projects p ON pm.project_id = p.id
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON pm.verified_by = u.id
    {$whereSql}
    ORDER BY pm.payment_date DESC
");
$stmt->execute($params);
$payments = $stmt->fetchAll();
?>

<!-- Financial Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Total Verified Revenue</div>
            <div class="stat-card-value text-success"><?php echo formatCurrency($totalRevenue); ?></div>
            <div class="text-secondary small mt-1">Verified cash & cleared bank deposits</div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-vault"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Pending Slip Verifications</div>
            <div class="stat-card-value text-warning"><?php echo formatCurrency($pendingPaymentAmount); ?></div>
            <div class="text-secondary small mt-1">Awaiting manual admin confirmation</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-receipt"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #3a86ff;">
            <div class="stat-card-title text-info">Total Outstanding Portfolio Balance</div>
            <div class="stat-card-value text-info"><?php echo formatCurrency($totalOutstandingBalance); ?></div>
            <div class="text-secondary small mt-1">Due across Milestone 2 & Final Hosting</div>
            <div class="stat-icon-wrapper text-info"><i class="fa-solid fa-hand-holding-dollar"></i></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-md-5">
            <select class="form-select form-select-chandila form-select-sm" name="stage">
                <option value="">-- All Payment Milestone Stages --</option>
                <?php foreach (PAYMENT_STAGES as $stage): ?>
                    <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo ($stageFilter === $stage) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stage); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-5">
            <select class="form-select form-select-chandila form-select-sm" name="status">
                <option value="">-- All Verification Statuses --</option>
                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending Verification</option>
                <option value="Payment Received" <?php echo ($statusFilter === 'Payment Received') ? 'selected' : ''; ?>>Payment Received</option>
                <option value="Payment Rejected" <?php echo ($statusFilter === 'Payment Rejected') ? 'selected' : ''; ?>>Payment Rejected</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($stageFilter) || !empty($statusFilter)): ?>
                <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Payments Master Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer</th>
                    <th>Milestone Stage</th>
                    <th>Amount</th>
                    <th>Method & Ref</th>
                    <th>Status</th>
                    <th>Verified By</th>
                    <th>Date</th>
                    <th>Receipt</th>
                    <th class="text-end">Verification Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="10" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-credit-card fa-3x text-secondary mb-2 d-block"></i>
                            No payment transactions found matching the filter.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $pay): ?>
                        <tr>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $pay['project_id']; ?>" class="fw-bold font-monospace text-primary">
                                    <?php echo htmlspecialchars($pay['job_id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($pay['customer_name']); ?></div>
                                <?php if (!empty($pay['company_name'])): ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($pay['company_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($pay['payment_stage']); ?></span>
                            </td>
                            <td>
                                <span class="fw-bold text-dark fs-6"><?php echo formatCurrency($pay['amount']); ?></span>
                            </td>
                            <td>
                                <div class="text-dark small"><?php echo htmlspecialchars($pay['payment_method']); ?></div>
                                <?php if (!empty($pay['transaction_reference'])): ?>
                                    <div class="text-secondary small font-monospace"><?php echo htmlspecialchars($pay['transaction_reference']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($pay['status'] === 'Payment Received'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Verified</span>
                                <?php elseif ($pay['status'] === 'Pending'): ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle"><i class="fa-solid fa-ban me-1"></i> Rejected</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary"><?php echo htmlspecialchars($pay['verified_by_name'] ?? '—'); ?></td>
                            <td class="small text-secondary"><?php echo formatDateOnly($pay['payment_date']); ?></td>
                            <td>
                                <?php if (!empty($pay['receipt_file'])): ?>
                                    <a href="<?php echo BASE_URL . '/' . htmlspecialchars($pay['receipt_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2" title="View Uploaded Slip">
                                        <i class="fa-solid fa-file-invoice"></i> Slip
                                    </a>
                                <?php else: ?>
                                    <span class="text-secondary small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($pay['status'] === 'Pending'): ?>
                                    <!-- Confirm Payment Button -->
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="verify_payment">
                                        <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                        <input type="hidden" name="status" value="Payment Received">
                                        <button type="submit" class="btn btn-sm btn-success me-1" title="Mark as Verified">
                                            <i class="fa-solid fa-check me-1"></i> Approve
                                        </button>
                                    </form>

                                    <!-- Reject Payment Button -->
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="d-inline">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="verify_payment">
                                        <input type="hidden" name="payment_id" value="<?php echo $pay['id']; ?>">
                                        <input type="hidden" name="status" value="Payment Rejected">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Mark as Rejected">
                                            <i class="fa-solid fa-xmark"></i> Reject
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-secondary small fst-italic">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

