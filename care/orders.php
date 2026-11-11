<?php
$careTitle = 'Customer Order Entry Desk';
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

// Handle New Customer Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_customer_order') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please try again.');
    } else {
        // Customer Information
        $custName    = trim($_POST['customer_name'] ?? '');
        $companyName = trim($_POST['company_name'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $phone       = trim($_POST['phone'] ?? '');
        $whatsapp    = trim($_POST['whatsapp'] ?? $phone);
        $address     = trim($_POST['address'] ?? '');

        // Project Specifications
        $title       = trim($_POST['project_title'] ?? '');
        $type        = trim($_POST['project_type'] ?? 'Corporate Website');
        $description = trim($_POST['description'] ?? '');
        $features    = trim($_POST['required_features'] ?? '');
        $refUrl      = trim($_POST['reference_url'] ?? '');
        $targetDate  = !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null;

        // Pricing Schedule
        $totalCost   = (float)($_POST['total_cost'] ?? 0);
        $advanceAmt  = (float)($_POST['advance_amount'] ?? 0);
        $milestone2  = (float)($_POST['milestone2_amount'] ?? 0);
        $finalAmt    = (float)($_POST['final_amount'] ?? 0);

        // Immediate Allocation & Payment options
        $developerId = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;
        $advancePaid = !empty($_POST['advance_paid']);
        $payMethod   = trim($_POST['payment_method'] ?? 'Bank Transfer');
        $transRef    = trim($_POST['transaction_reference'] ?? '');

        if (empty($custName) || empty($email) || empty($phone) || empty($title) || $totalCost <= 0) {
            setFlash('error', 'Customer name, email, phone, project title, and total cost are mandatory.');
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Locate or create customer
                $stmtC = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ? OR whatsapp_number = ? LIMIT 1");
                $stmtC->execute([$email, $phone, $whatsapp]);
                $existingCustomerId = $stmtC->fetchColumn();

                if ($existingCustomerId) {
                    $customerId = (int)$existingCustomerId;
                    $upCust = $pdo->prepare("
                        UPDATE customers SET 
                            full_name = ?, company_name = ?, whatsapp_number = ?, address = ? 
                        WHERE id = ?
                    ");
                    $upCust->execute([$custName, $companyName, $whatsapp, $address, $customerId]);
                } else {
                    $insCust = $pdo->prepare("
                        INSERT INTO customers (full_name, company_name, email, phone, whatsapp_number, address, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $insCust->execute([$custName, $companyName, $email, $phone, $whatsapp, $address]);
                    $customerId = (int)$pdo->lastInsertId();
                }

                // 2. Generate unique Job ID (e.g. CHW-2026-0004)
                $year = date('Y');
                $countThisYear = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE job_id LIKE 'CHW-{$year}-%'")->fetchColumn();
                $jobId = sprintf("CHW-%s-%04d", $year, $countThisYear + 1);

                // Check collision just in case
                $chkJob = $pdo->prepare("SELECT id FROM projects WHERE job_id = ?");
                $chkJob->execute([$jobId]);
                if ($chkJob->fetch()) {
                    $jobId = sprintf("CHW-%s-%04d", $year, rand(1000, 9999));
                }

                // 3. Status & Balances
                $totalPaid = 0.00;
                $initialStatus = 'Advance Payment Pending';

                if ($advancePaid && $advanceAmt > 0) {
                    $totalPaid = $advanceAmt;
                    $initialStatus = $developerId ? 'Development Started' : 'Advance Payment Confirmed';
                } elseif ($developerId) {
                    $initialStatus = 'Advance Payment Pending';
                }

                $balanceDue = max(0, $totalCost - $totalPaid);
                $assignedDate = $developerId ? date('Y-m-d H:i:s') : null;

                // 4. Insert Project
                $insProj = $pdo->prepare("
                    INSERT INTO projects (
                        job_id, customer_id, project_title, project_type, description, required_features,
                        reference_url, expected_budget, total_cost, advance_amount, milestone2_amount, final_amount,
                        total_paid, balance_due, status, progress_percentage, assigned_developer_id, assigned_date,
                        expected_completion_date, is_approved, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, ?, ?, ?, ?, ?,
                        ?, 1, NOW()
                    )
                ");
                $expectedBudgetStr = formatCurrency($totalCost);
                $initProgress = ($initialStatus === 'Development Started') ? 10 : 0;

                $insProj->execute([
                    $jobId, $customerId, $title, $type, $description, $features,
                    $refUrl, $expectedBudgetStr, $totalCost, $advanceAmt, $milestone2, $finalAmt,
                    $totalPaid, $balanceDue, $initialStatus, $initProgress, $developerId, $assignedDate,
                    $targetDate
                ]);
                $projectId = (int)$pdo->lastInsertId();

                // 5. If Advance Payment is marked received, record in payments table
                if ($advancePaid && $advanceAmt > 0) {
                    $insPay = $pdo->prepare("
                        INSERT INTO payments (
                            project_id, payment_stage, amount, payment_method, transaction_reference,
                            status, admin_notes, verified_by, verified_at, payment_date, created_at
                        ) VALUES (
                            ?, 'Advance Payment', ?, ?, ?,
                            'Payment Received', 'Recorded on order intake by Customer Care.', ?, NOW(), NOW(), NOW()
                        )
                    ");
                    $insPay->execute([$projectId, $advanceAmt, $payMethod, $transRef, $currentUser['id']]);

                    logActivity($pdo, $jobId, $projectId, 'Advance Payment Verified', "Customer Care recorded Advance Payment of " . formatCurrency($advanceAmt), $currentUser['full_name']);
                }

                // 6. Log Initial Audit Activity
                logActivity($pdo, $jobId, $projectId, 'Order Created', "Order entered by Customer Care agent {$currentUser['full_name']}.", $currentUser['full_name']);

                if ($developerId) {
                    $devName = $pdo->query("SELECT full_name FROM users WHERE id = {$developerId}")->fetchColumn();
                    logActivity($pdo, $jobId, $projectId, 'Developer Assigned', "Initial assignment to {$devName}.", $currentUser['full_name']);
                }

                $pdo->commit();

                // 7. Multi-Channel Notification: WhatsApp & Email Confirmation to Client
                $clientPhone = $whatsapp ?: $phone;
                $trackingUrl = BASE_URL . '/track_project.php?job_id=' . urlencode($jobId);

                // Dispatch WhatsApp Order Confirmation
                WhatsAppService::send($pdo, $clientPhone, 'Project Confirmed', [
                    'job_id'      => $jobId,
                    'track_url'   => $trackingUrl,
                    'total_cost'  => $totalCost,
                    'advance_due' => $advanceAmt
                ], $projectId, $jobId);

                // Dispatch HTML Email Confirmation
                if (!empty($email)) {
                    EmailService::send($pdo, $email, 'Order Registered & Confirmed', [
                        'customer_name'        => $custName,
                        'job_id'               => $jobId,
                        'project_title'        => $title,
                        'project_type'         => $type,
                        'total_cost'           => $totalCost,
                        'advance_amount'       => $advanceAmt,
                        'balance_due'          => $balanceDue,
                        'tracking_url'         => $trackingUrl,
                        'invoice_url'          => BASE_URL . '/invoice.php?job_id=' . urlencode($jobId),
                        'status'               => $initialStatus
                    ], $projectId, $jobId, false, $custName);
                }

                setFlash('success', "Customer Order {$jobId} registered successfully! Customer notified via WhatsApp & Email.");
                header("Location: " . BASE_URL . "/care/project_view.php?id=" . $projectId);
                exit;

            } catch (Exception $e) {
                $pdo->rollBack();
                setFlash('error', "Database error while creating order: " . $e->getMessage());
            }
        }
    }
}

// Fetch Active Developers for Assignment Dropdown
$developers = $pdo->query("
    SELECT u.id, u.full_name, u.skills,
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions') THEN 1 END) AS ongoing_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY ongoing_count ASC, u.full_name ASC
")->fetchAll();

// Fetch Recent Orders with Filter
$search = trim($_GET['q'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = ["p.status != 'Cancelled'"];
$params = [];

if (!empty($search)) {
    $where[] = "(p.job_id LIKE ? OR p.project_title LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}
if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

$whereSql = "WHERE " . implode(" AND ", $where);
$stmtOrders = $pdo->prepare("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.phone, 
           c.whatsapp_number,
           u.full_name AS dev_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    {$whereSql}
    ORDER BY p.created_at DESC
    LIMIT 50
");
$stmtOrders->execute($params);
$orders = $stmtOrders->fetchAll();

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Header with Action -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-cart-plus text-primary me-2"></i> Enter Customer Order</h3>
        <p class="text-secondary small mb-0">Record new client inquiries, set up milestone budgets, allocate developers, and send automated client tracking links.</p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-electric btn-sm" data-bs-toggle="collapse" data-bs-target="#collapseOrderForm" aria-expanded="true">
            <i class="fa-solid fa-plus-circle me-1"></i> New Order Form
        </button>
        <a href="<?php echo BASE_URL; ?>/care/assign.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-user-check me-1"></i> Assignment Desk
        </a>
    </div>
</div>

<!-- Order Intake Form Panel -->
<div class="collapse show mb-4" id="collapseOrderForm">
    <div class="admin-card p-4 shadow-sm" style="border: 1.5px solid #3b82f6; border-radius: 16px;">
        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary text-white rounded-pill px-3 py-1"><i class="fa-solid fa-file-pen me-1"></i> New Customer Order Intake Form</span>
            </div>
            <span class="text-muted small">* Indicates mandatory fields</span>
        </div>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="newOrderForm">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="create_customer_order">

            <!-- Section 1: Customer Profile -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h6 class="text-primary fw-bold mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-user me-1"></i> 1. Customer & Organization Details
                    </h6>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">Customer Full Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-chandila" name="customer_name" placeholder="e.g. Ruwan Wickramasinghe" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">Company / Brand Name</label>
                    <input type="text" class="form-control form-control-chandila" name="company_name" placeholder="e.g. Aqua Lanka Holdings (Pvt) Ltd">
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">Customer Email Address <span class="text-danger">*</span></label>
                    <input type="email" class="form-control form-control-chandila" name="email" placeholder="customer@domain.com" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">Primary Phone Number <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-chandila" name="phone" id="orderPhone" placeholder="e.g. +94 77 123 4567" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">WhatsApp Number (For Auto-Receipts) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light text-success"><i class="fa-brands fa-whatsapp"></i></span>
                        <input type="text" class="form-control form-control-chandila" name="whatsapp" id="orderWhatsApp" placeholder="+94 77 123 4567" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">City / Office Address</label>
                    <input type="text" class="form-control form-control-chandila" name="address" placeholder="e.g. Colombo 03, Sri Lanka">
                </div>
            </div>

            <!-- Section 2: Project Scope & Deliverables -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h6 class="text-primary fw-bold mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-code me-1"></i> 2. Project Specifications & Scope
                    </h6>
                </div>
                <div class="col-md-8">
                    <label class="form-label text-dark small fw-semibold">Project Title / Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control form-control-chandila" name="project_title" placeholder="e.g. Aqua Super E-Commerce & Online Bottled Water Delivery Portal" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-dark small fw-semibold">Project Category / Type <span class="text-danger">*</span></label>
                    <select class="form-select form-select-chandila" name="project_type" required>
                        <?php foreach (PROJECT_TYPES as $t): ?>
                            <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small fw-semibold">Project Scope & Core Requirements <span class="text-danger">*</span></label>
                    <textarea class="form-control form-control-chandila" name="description" rows="3" placeholder="Describe client expectations, business goals, and core system modules..." required></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small fw-semibold">Required Features Checklist</label>
                    <textarea class="form-control form-control-chandila" name="required_features" rows="3" placeholder="e.g. User authentication, PayHere payment gateway, SMS notifications, Admin reports"></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small fw-semibold">Client Reference URL / Design Concept</label>
                    <input type="url" class="form-control form-control-chandila" name="reference_url" placeholder="https://example.com or Figma/Drive link">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small fw-semibold">Agreed Target Completion Date</label>
                    <input type="date" class="form-control form-control-chandila" name="expected_completion_date" value="<?php echo date('Y-m-d', strtotime('+3 weeks')); ?>">
                </div>
            </div>

            <!-- Section 3: Financial Milestones & Terms -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h6 class="text-primary fw-bold mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-money-bill-wave me-1"></i> 3. Pricing Schedule & Milestone Breakdown
                    </h6>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark small fw-semibold">Total Project Cost (<?php echo getCurrencySymbol(); ?>) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" class="form-control form-control-chandila fw-bold text-primary" id="totalCostInput" name="total_cost" placeholder="250000.00" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark small fw-semibold">Advance Deposit (30% Rec.)</label>
                    <input type="number" step="0.01" class="form-control form-control-chandila" id="advanceAmtInput" name="advance_amount" placeholder="75000.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark small fw-semibold">50% Milestone (Mid-Dev)</label>
                    <input type="number" step="0.01" class="form-control form-control-chandila" id="milestone2Input" name="milestone2_amount" placeholder="125000.00">
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark small fw-semibold">Final Balance (Hosting/Delivery)</label>
                    <input type="number" step="0.01" class="form-control form-control-chandila" id="finalAmtInput" name="final_amount" placeholder="50000.00">
                </div>
            </div>

            <!-- Section 4: Developer Assignment & Instant Advance Settlement -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <h6 class="text-primary fw-bold mb-2 pb-1 border-bottom">
                        <i class="fa-solid fa-user-check me-1"></i> 4. Developer Allocation & Immediate Advance Settlement
                    </h6>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small fw-semibold">Assign Developer (Optional – can also assign later)</label>
                    <select class="form-select form-select-chandila" name="developer_id">
                        <option value="">-- Leave Unassigned for Later Allocation --</option>
                        <?php foreach ($developers as $dev): ?>
                            <option value="<?php echo $dev['id']; ?>">
                                <?php echo htmlspecialchars($dev['full_name']); ?> (<?php echo (int)$dev['ongoing_count']; ?> Ongoing Projects) • <?php echo htmlspecialchars($dev['skills']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="p-3 border rounded bg-light">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="advancePaidCheck" name="advance_paid" value="1">
                            <label class="form-check-label text-dark fw-bold" for="advancePaidCheck">
                                <i class="fa-solid fa-check-circle text-success me-1"></i> Mark Advance Deposit as Received Right Now
                            </label>
                        </div>
                        <div id="advancePaymentDetails" style="display: none;" class="mt-2 pt-2 border-top">
                            <div class="row g-2">
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted mb-1">Payment Method</label>
                                    <select class="form-select form-select-sm" name="payment_method">
                                        <option value="Bank Transfer">Bank Transfer (Commercial Bank)</option>
                                        <option value="Cash Deposit">Cash Deposit / Cash</option>
                                        <option value="PayHere Online">PayHere Online Gateway</option>
                                        <option value="Cheque">Cheque</option>
                                    </select>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label small text-muted mb-1">Bank Ref / Slip No.</label>
                                    <input type="text" class="form-control form-control-sm" name="transaction_reference" placeholder="e.g. FT26081234 / Cashier">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Submit Buttons -->
            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <button type="reset" class="btn btn-outline-secondary px-4">Clear Form</button>
                <button type="submit" class="btn btn-electric px-4 py-2">
                    <i class="fa-solid fa-paper-plane me-2"></i> Register Customer Order & Dispatch Confirmation
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Auto-Calculate Milestones Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var totalCost = document.getElementById('totalCostInput');
    var advance = document.getElementById('advanceAmtInput');
    var milestone2 = document.getElementById('milestone2Input');
    var finalAmt = document.getElementById('finalAmtInput');
    var phone = document.getElementById('orderPhone');
    var whatsapp = document.getElementById('orderWhatsApp');
    var advancePaidCheck = document.getElementById('advancePaidCheck');
    var advancePaymentDetails = document.getElementById('advancePaymentDetails');

    // Auto-sync whatsapp with phone if whatsapp is empty or untouched
    if (phone && whatsapp) {
        phone.addEventListener('input', function() {
            if (!whatsapp.value || whatsapp.dataset.edited !== 'true') {
                whatsapp.value = phone.value;
            }
        });
        whatsapp.addEventListener('input', function() {
            whatsapp.dataset.edited = 'true';
        });
    }

    // Auto-calculate suggested 30% advance, 50% milestone, 20% final
    if (totalCost) {
        totalCost.addEventListener('input', function() {
            var val = parseFloat(totalCost.value) || 0;
            if (val > 0) {
                if (!advance.value || advance.dataset.edited !== 'true') {
                    advance.value = (val * 0.30).toFixed(2);
                }
                if (!milestone2.value || milestone2.dataset.edited !== 'true') {
                    milestone2.value = (val * 0.50).toFixed(2);
                }
                if (!finalAmt.value || finalAmt.dataset.edited !== 'true') {
                    var adv = parseFloat(advance.value) || 0;
                    var m2 = parseFloat(milestone2.value) || 0;
                    finalAmt.value = Math.max(0, val - adv - m2).toFixed(2);
                }
            }
        });

        [advance, milestone2, finalAmt].forEach(function(el) {
            if (el) {
                el.addEventListener('input', function() {
                    el.dataset.edited = 'true';
                });
            }
        });
    }

    // Toggle advance payment inputs
    if (advancePaidCheck && advancePaymentDetails) {
        advancePaidCheck.addEventListener('change', function() {
            advancePaymentDetails.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>

<!-- Registered Orders Table -->
<div class="admin-card">
    <div class="p-3 border-bottom d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <h5 class="text-dark fw-bold mb-0"><i class="fa-solid fa-list-check text-primary me-2"></i> All Registered Orders (<?php echo count($orders); ?>)</h5>
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="d-flex gap-2">
            <input type="text" class="form-control form-control-sm" name="q" placeholder="Search Job ID or Customer..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 220px;">
            <select class="form-select form-select-sm" name="status" style="max-width: 180px;">
                <option value="">-- All Statuses --</option>
                <?php foreach (PROJECT_STAGES as $stage): ?>
                    <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo ($statusFilter === $stage) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stage); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa-solid fa-filter"></i></button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Name</th>
                    <th>Project Title</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Assigned Dev</th>
                    <th>Total Budget</th>
                    <th>Balance Due</th>
                    <th class="text-end">Quick Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-folder-open fa-3x text-secondary mb-2 d-block"></i>
                            No orders found matching your search.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($o['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($o['company_name'] ?: 'Individual'); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($o['project_title']); ?>">
                                    <?php echo htmlspecialchars($o['project_title']); ?>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.7rem;"><?php echo htmlspecialchars($o['project_type']); ?></span>
                            </td>
                            <td><?php echo getStatusBadge($o['status']); ?></td>
                            <td style="min-width: 100px;">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span><?php echo (int)$o['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo (int)$o['progress_percentage']; ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($o['dev_name'])): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <?php echo htmlspecialchars($o['dev_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $o['id']; ?>" class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none">
                                        <i class="fa-solid fa-plus me-1"></i> Assign
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold text-dark"><?php echo formatCurrency($o['total_cost']); ?></td>
                            <td>
                                <?php if ($o['balance_due'] > 0): ?>
                                    <span class="text-danger fw-semibold"><?php echo formatCurrency($o['balance_due']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Paid</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?php echo BASE_URL; ?>/care/project_view.php?id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Full Status">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <?php if ($o['balance_due'] > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/care/payments.php?project_id=<?php echo $o['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark Payment Done">
                                            <i class="fa-solid fa-credit-card"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($o['job_id']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Official Invoice">
                                        <i class="fa-solid fa-file-invoice"></i>
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

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

