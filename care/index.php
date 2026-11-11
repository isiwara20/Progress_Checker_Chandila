<?php
$careTitle = 'Customer Care Dashboard & SOP Guide';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Customer Care Gate
requireCustomerCare();

$pdo = getDB();
$currentUser = getCurrentUser();

// Desk Metrics
$totalProjects       = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status != 'Cancelled'")->fetchColumn();
$unassignedProjects  = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE assigned_developer_id IS NULL AND status != 'Cancelled'")->fetchColumn();
$pendingPayments     = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();
$activeProjects      = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions')")->fetchColumn();
$totalOutstandingBal = (float)$pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM projects WHERE status != 'Cancelled'")->fetchColumn();

// Fetch Recent Customer Orders
$stmtRecent = $pdo->query("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.phone AS customer_phone, 
           c.whatsapp_number,
           u.full_name AS dev_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.status != 'Cancelled'
    ORDER BY p.created_at DESC
    LIMIT 8
");
$recentProjects = $stmtRecent->fetchAll();

// Fetch Active Developers for Quick Workload Glance
$devs = $pdo->query("
    SELECT u.id, u.full_name, u.phone, u.skills,
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions') THEN 1 END) AS ongoing_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY ongoing_count ASC
")->fetchAll();

// Fetch Care Agent's Today Logged Hours
$todayCareHours = 0;
if ($pdo && !empty($currentUser['id'])) {
    try {
        $stmtCareH = $pdo->prepare("SELECT COALESCE(SUM(total_hours), 0) FROM care_working_hours WHERE user_id = ? AND work_date = CURDATE() AND status IN ('Completed', 'Approved')");
        $stmtCareH->execute([$currentUser['id']]);
        $todayCareHours = (float)$stmtCareH->fetchColumn();
    } catch (Exception $e) {}
}

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Welcome Banner -->
<div class="admin-card p-4 mb-4" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: 16px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-primary px-3 py-1 text-white fw-semibold" style="letter-spacing: 0.5px;">
                    <i class="fa-solid fa-headset me-1"></i> CUSTOMER CARE DESK
                </span>
                <span class="text-secondary small">Live Ops • <?php echo date('l, F j, Y'); ?></span>
            </div>
            <h3 class="text-white fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h3>
            <p class="text-light text-opacity-75 small mb-0">Manage customer order entry, developer task allocations, payment verifications, and real-time client project tracking.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-electric btn-sm px-3 py-2">
                <i class="fa-solid fa-cart-plus me-1"></i> Enter New Order
            </a>
            <a href="<?php echo BASE_URL; ?>/care/assign.php" class="btn btn-outline-light btn-sm px-3 py-2">
                <i class="fa-solid fa-user-check me-1"></i> Assign Developers
            </a>
            <a href="<?php echo BASE_URL; ?>/care/payments.php" class="btn btn-success btn-sm px-3 py-2 text-white">
                <i class="fa-solid fa-credit-card me-1"></i> Mark Payments Done
            </a>
        </div>
    </div>
</div>

<!-- Customer Care Duty & Working Hours Live Widget -->
<div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; <?php echo $activeCareDuty ? 'background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 100%); border: 1.5px solid #86efac !important;' : 'background: #ffffff; border: 1.5px solid #e2e8f0 !important;'; ?>">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.3rem; <?php echo $activeCareDuty ? 'background: #10b981; color: #fff;' : 'background: #f1f5f9; color: #64748b;'; ?>">
                    <i class="fa-solid <?php echo $activeCareDuty ? 'fa-business-time fa-bounce' : 'fa-clock'; ?>"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <?php if ($activeCareDuty): ?>
                            <span class="badge bg-success px-2 py-1 text-white fw-bold"><i class="fa-solid fa-circle fa-beat-fade me-1 small"></i> ON DUTY</span>
                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($activeCareDuty['shift_type']); ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary px-2 py-1 text-white">OFF DUTY</span>
                            <span class="text-muted small">Not currently clocked in</span>
                        <?php endif; ?>
                    </div>
                    <h5 class="mb-0 text-dark fw-bold">
                        <?php if ($activeCareDuty): ?>
                            Active Shift Started at <span class="text-success"><?php echo date('h:i A', strtotime($activeCareDuty['start_time'])); ?></span>
                        <?php else: ?>
                            Ready to start your Customer Care shift?
                        <?php endif; ?>
                    </h5>
                    <div class="text-secondary small mt-1">
                        <?php if ($activeCareDuty): ?>
                            Shift Date: <?php echo date('l, M j, Y', strtotime($activeCareDuty['work_date'])); ?> • Remember to record customer notes when clocking out.
                        <?php else: ?>
                            Hours completed today: <strong><?php echo number_format($todayCareHours, 2); ?> hrs</strong> • Select shift and clock in below.
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2">
                <?php if ($activeCareDuty): ?>
                    <a href="<?php echo BASE_URL; ?>/care/hours.php" class="btn btn-danger btn-sm px-3 py-2 fw-semibold shadow-sm">
                        <i class="fa-solid fa-stopwatch me-1"></i> Clock Out / End Shift
                    </a>
                    <a href="<?php echo BASE_URL; ?>/care/hours.php" class="btn btn-outline-success btn-sm px-3 py-2">
                        <i class="fa-solid fa-calendar-check me-1"></i> View Timesheet
                    </a>
                <?php else: ?>
                    <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST" class="d-inline-flex gap-2">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                        <input type="hidden" name="action" value="clock_in">
                        <select name="shift_type" class="form-select form-select-sm" style="min-width: 220px;" required>
                            <option value="Standard Full Day (08:30 AM – 05:30 PM)">Standard (08:30 AM – 05:30 PM)</option>
                            <option value="Morning Shift (08:00 AM – 01:00 PM)">Morning (08:00 AM – 01:00 PM)</option>
                            <option value="Evening Shift (01:00 PM – 07:00 PM)">Evening (01:00 PM – 07:00 PM)</option>
                            <option value="Night Shift / On-Call Support">Night / On-Call Support</option>
                            <option value="Custom / Overtime Duty">Custom / Overtime</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm px-3 py-2 text-nowrap fw-semibold">
                            <i class="fa-solid fa-play me-1"></i> Clock In
                        </button>
                    </form>
                    <a href="<?php echo BASE_URL; ?>/care/hours.php" class="btn btn-outline-secondary btn-sm px-3 py-2" title="View Timesheet & History">
                        <i class="fa-solid fa-calendar-days me-1"></i> Duty History
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Key Performance Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid #2563eb;">
            <div class="stat-card-title text-primary">Total Active Orders</div>
            <div class="stat-card-value text-primary"><?php echo $totalProjects; ?></div>
            <div class="text-secondary small mt-1">Client projects in database</div>
            <div class="stat-icon-wrapper text-primary"><i class="fa-solid fa-folder-tree"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Needs Developer Assignment</div>
            <div class="stat-card-value text-warning"><?php echo $unassignedProjects; ?></div>
            <div class="text-secondary small mt-1">Awaiting developer allocation</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-user-clock"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Active In-Development</div>
            <div class="stat-card-value text-success"><?php echo $activeProjects; ?></div>
            <div class="text-secondary small mt-1">Being coded by developers</div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-laptop-code"></i></div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 3px solid #ef4444;">
            <div class="stat-card-title text-danger">Pending Payment Slips</div>
            <div class="stat-card-value text-danger"><?php echo $pendingPayments; ?></div>
            <div class="text-secondary small mt-1">Due for verification & invoice</div>
            <div class="stat-icon-wrapper text-danger"><i class="fa-solid fa-receipt"></i></div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- STANDARD OPERATING PROCEDURES (SOP) & INSTRUCTIONS PANEL       -->
<!-- User Requirement: "and in his page have instructotion also"    -->
<!-- ============================================================== -->
<div class="admin-card mb-4" id="careInstructionsSection" style="border: 1px solid #cbd5e1; border-radius: 14px; overflow: hidden;">
    <div class="p-3 bg-light d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary text-white rounded-pill px-2 py-1"><i class="fa-solid fa-book-bookmark"></i></span>
            <div>
                <h6 class="text-dark fw-bold mb-0">Customer Care Operating Guidelines & Standard Operating Procedures (SOP)</h6>
                <span class="text-muted small" style="font-size: 0.78rem;">Official step-by-step instructions for customer orders, developer assignment, payments, and client support</span>
            </div>
        </div>
        <div>
            <button class="btn btn-sm btn-outline-primary py-1 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#sopAccordionContent" aria-expanded="true">
                <i class="fa-solid fa-chevron-down me-1"></i> Toggle Guide
            </button>
        </div>
    </div>

    <div class="collapse show" id="sopAccordionContent">
        <div class="p-3 p-md-4">
            <!-- Nav Tabs -->
            <ul class="nav nav-pills mb-3 gap-2" id="sopTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active py-2 px-3 small fw-semibold" id="tab-intake-btn" data-bs-toggle="pill" data-bs-target="#tab-intake" type="button" role="tab">
                        <i class="fa-solid fa-cart-plus me-1 text-primary"></i> 1. Order Intake
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3 small fw-semibold" id="tab-assign-btn" data-bs-toggle="pill" data-bs-target="#tab-assign" type="button" role="tab">
                        <i class="fa-solid fa-user-check me-1 text-info"></i> 2. Developer Allocation
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3 small fw-semibold" id="tab-payments-btn" data-bs-toggle="pill" data-bs-target="#tab-payments" type="button" role="tab">
                        <i class="fa-solid fa-credit-card me-1 text-success"></i> 3. Payments & Invoices
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link py-2 px-3 small fw-semibold" id="tab-tracking-btn" data-bs-toggle="pill" data-bs-target="#tab-tracking" type="button" role="tab">
                        <i class="fa-solid fa-headset me-1 text-warning"></i> 4. Client Support & Status
                    </button>
                </li>
            </ul>

            <!-- Tab Panes -->
            <div class="tab-content border rounded-3 p-3 p-md-4 bg-light bg-opacity-50">
                <!-- Tab 1: Order Intake -->
                <div class="tab-pane fade show active" id="tab-intake" role="tabpanel">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-9">
                            <h6 class="text-primary fw-bold mb-2"><i class="fa-solid fa-circle-check text-primary me-2"></i> Step 1: Customer Order Intake Protocol</h6>
                            <ul class="text-dark small mb-0 ps-3" style="line-height: 1.7;">
                                <li>Collect the client's verified Full Name, Company, Email, and Phone number with country code (e.g. <code>+94 77 123 4567</code>).</li>
                                <li>Select the accurate <strong>Project Category</strong> (Corporate, E-Commerce, Web App) and enter detailed scope deliverables.</li>
                                <li>Establish Total Agreed Budget (e.g. Rs. 250,000). The system automatically suggests standard 30% advance, 50% milestone, and final balance.</li>
                                <li>Upon submission, the system generates an official <strong>Job ID (CHW-2026-XXXX)</strong> and immediately dispatches WhatsApp & Email confirmation with a 24/7 public tracking link.</li>
                            </ul>
                        </div>
                        <div class="col-lg-3 text-lg-end">
                            <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-electric btn-sm px-3 py-2 w-100">
                                <i class="fa-solid fa-cart-plus me-1"></i> Open Order Intake Form
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Developer Allocation -->
                <div class="tab-pane fade" id="tab-assign" role="tabpanel">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-9">
                            <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-circle-check text-info me-2"></i> Step 2: Developer Task Allocation Protocol</h6>
                            <ul class="text-dark small mb-0 ps-3" style="line-height: 1.7;">
                                <li>Review the <strong>Engineering Team Workload cards</strong> below. Prioritize available developers or those with lowest ongoing tasks.</li>
                                <li>Match tech skillset requirements (e.g., PHP backend architecture vs Vue/UI mobile responsiveness).</li>
                                <li>Attach client design references/Figma links and specific instructions in the briefing notes.</li>
                                <li>Set a firm target completion deadline. Customer is automatically notified that their developer has been assigned.</li>
                            </ul>
                        </div>
                        <div class="col-lg-3 text-lg-end">
                            <a href="<?php echo BASE_URL; ?>/care/assign.php" class="btn btn-info btn-sm text-white px-3 py-2 w-100">
                                <i class="fa-solid fa-user-check me-1"></i> Open Assignment Desk
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Payments & Invoices -->
                <div class="tab-pane fade" id="tab-payments" role="tabpanel">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-9">
                            <h6 class="text-success fw-bold mb-2"><i class="fa-solid fa-circle-check text-success me-2"></i> Step 3: Payment Settlement & Invoicing Protocol</h6>
                            <ul class="text-dark small mb-0 ps-3" style="line-height: 1.7;">
                                <li><strong>Advance Payment (30%):</strong> Must be cleared before development commences. Marking it paid updates status to <em>Advance Payment Confirmed</em>.</li>
                                <li><strong>50% Milestone Payment:</strong> Triggered when progress reaches 50%. Dispatches milestone invoice to the customer.</li>
                                <li><strong>Final Payment:</strong> Full settlement must be cleared before production server credentials and hosting deployment handover.</li>
                                <li>Every verified transaction updates balances and dispatches an official branded invoice with official certified PAID stamp.</li>
                            </ul>
                        </div>
                        <div class="col-lg-3 text-lg-end">
                            <a href="<?php echo BASE_URL; ?>/care/payments.php" class="btn btn-success btn-sm text-white px-3 py-2 w-100">
                                <i class="fa-solid fa-credit-card me-1"></i> Mark Payments Done
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Client Support & Status -->
                <div class="tab-pane fade" id="tab-tracking" role="tabpanel">
                    <div class="row g-3 align-items-center">
                        <div class="col-lg-9">
                            <h6 class="text-warning fw-bold mb-2"><i class="fa-solid fa-circle-check text-warning me-2"></i> Step 4: Client Support & Real-Time Tracking</h6>
                            <ul class="text-dark small mb-0 ps-3" style="line-height: 1.7;">
                                <li>When clients contact via phone or WhatsApp, search by <strong>Job ID</strong> or customer name in the Project Status Checker.</li>
                                <li>Review live progress percentage and latest milestone notes entered by assigned engineers.</li>
                                <li>One-click copy the client tracking link (<code>track_project.php?job_id=...</code>) and send it directly via WhatsApp.</li>
                                <li>Use the direct green WhatsApp button on any project row to start an instant customer conversation.</li>
                            </ul>
                        </div>
                        <div class="col-lg-3 text-lg-end">
                            <a href="<?php echo BASE_URL; ?>/care/projects.php" class="btn btn-primary btn-sm px-3 py-2 w-100">
                                <i class="fa-solid fa-bars-progress me-1"></i> Open Status Checker
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Active Developers Workload Summary -->
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="admin-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark fw-bold mb-0"><i class="fa-solid fa-users-gear text-primary me-2"></i> Engineering Team Availability & Workload</h5>
                    <span class="text-secondary small">Check developer capacity before assigning new incoming customer orders</span>
                </div>
                <a href="<?php echo BASE_URL; ?>/care/assign.php" class="btn btn-sm btn-outline-primary">
                    <i class="fa-solid fa-user-check me-1"></i> Full Assignment Desk
                </a>
            </div>

            <div class="row g-3">
                <?php foreach ($devs as $d): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="p-3 border rounded-3 bg-light d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="user-avatar-circle" style="width: 44px; height: 44px; font-size: 1.1rem; background: #e0e7ff; color: #3730a3;">
                                    <i class="fa-solid fa-code"></i>
                                </div>
                                <div>
                                    <h6 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($d['full_name']); ?></h6>
                                    <div class="text-muted small text-truncate" style="max-width: 170px;" title="<?php echo htmlspecialchars($d['skills']); ?>">
                                        <?php echo htmlspecialchars($d['skills']); ?>
                                    </div>
                                    <div class="text-secondary small font-monospace"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($d['phone']); ?></div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="badge <?php echo $d['ongoing_count'] > 3 ? 'bg-danger-subtle text-danger border border-danger-subtle' : ($d['ongoing_count'] > 1 ? 'bg-warning-subtle text-warning border border-warning-subtle' : 'bg-success-subtle text-success border border-success-subtle'); ?> mb-1">
                                    <?php echo (int)$d['ongoing_count']; ?> Ongoing
                                </span>
                                <div>
                                    <a href="<?php echo BASE_URL; ?>/care/assign.php?dev_id=<?php echo $d['id']; ?>" class="btn btn-sm btn-light border py-0 px-2" style="font-size: 0.75rem;">Assign</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Customer Orders Table -->
<div class="admin-card">
    <div class="p-4 border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
        <div>
            <h5 class="text-dark fw-bold mb-1"><i class="fa-solid fa-table-list text-primary me-2"></i> Recent Customer Orders</h5>
            <span class="text-secondary small">Showing most recent orders submitted and their operational status</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-sm btn-electric">
                <i class="fa-solid fa-plus me-1"></i> Enter Order
            </a>
            <a href="<?php echo BASE_URL; ?>/care/projects.php" class="btn btn-sm btn-outline-secondary">
                View All Projects
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Name</th>
                    <th>Project Title & Scope</th>
                    <th>Status Stage</th>
                    <th>Progress</th>
                    <th>Assigned Dev</th>
                    <th>Total / Due</th>
                    <th class="text-end">Care Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentProjects)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-box-open fa-3x text-secondary mb-2 d-block"></i>
                            No customer orders entered yet. Click <strong>"Enter New Order"</strong> to create the first order.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentProjects as $p): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($p['company_name'] ?: 'Individual'); ?></div>
                                <div class="small text-secondary">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $p['whatsapp_number'] ?: $p['customer_phone']); ?>" target="_blank" class="text-success text-decoration-none">
                                        <i class="fa-brands fa-whatsapp me-1"></i><?php echo htmlspecialchars($p['whatsapp_number'] ?: $p['customer_phone']); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;" title="<?php echo htmlspecialchars($p['project_title']); ?>">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;"><?php echo htmlspecialchars($p['project_type']); ?></span>
                            </td>
                            <td>
                                <?php echo getStatusBadge($p['status']); ?>
                            </td>
                            <td style="min-width: 110px;">
                                <div class="d-flex align-items-center justify-content-between small mb-1">
                                    <span class="text-secondary"><?php echo (int)$p['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px; border-radius: 4px;">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo (int)$p['progress_percentage']; ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($p['dev_name'])): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="fa-solid fa-code me-1"></i> <?php echo htmlspecialchars($p['dev_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $p['id']; ?>" class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none">
                                        <i class="fa-solid fa-plus me-1"></i> Assign Dev
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo formatCurrency($p['total_cost']); ?></div>
                                <?php if ($p['balance_due'] > 0): ?>
                                    <div class="small text-danger fw-semibold">Due: <?php echo formatCurrency($p['balance_due']); ?></div>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">Paid in Full</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?php echo BASE_URL; ?>/care/project_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="Check Status & Details">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <?php if ($p['balance_due'] > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/care/payments.php?project_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark Payment Done">
                                            <i class="fa-solid fa-credit-card"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?php echo BASE_URL; ?>/invoice.php?job_id=<?php echo urlencode($p['job_id']); ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="View Customer Invoice">
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

