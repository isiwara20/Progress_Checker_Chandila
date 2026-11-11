<?php
$adminTitle = 'Reports & Financial Analytics';
require_once __DIR__ . '/../includes/admin_header.php';

// Revenue by Project Type
$revenueByType = $pdo->query("
    SELECT p.project_type, 
           COUNT(p.id) as total_count,
           COALESCE(SUM(p.total_cost), 0) as total_value,
           COALESCE(SUM(p.total_paid), 0) as total_collected
    FROM projects p
    WHERE p.status != 'Cancelled'
    GROUP BY p.project_type
    ORDER BY total_collected DESC
")->fetchAll();

// Developer Productivity
$devMetrics = $pdo->query("
    SELECT u.full_name,
           COUNT(p.id) as total_handled,
           COUNT(CASE WHEN p.status = 'Project Delivered' THEN 1 END) as delivered_count,
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Second Payment Pending') THEN 1 END) as active_count,
           COALESCE(SUM(p.total_paid), 0) as generated_revenue
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer'
    GROUP BY u.id
")->fetchAll();

// Recent Cleared Invoices
$invoices = $pdo->query("
    SELECT pm.*, p.job_id, p.project_title, c.full_name as customer_name, c.company_name
    FROM payments pm
    JOIN projects p ON pm.project_id = p.id
    JOIN customers c ON p.customer_id = c.id
    WHERE pm.status = 'Payment Received'
    ORDER BY pm.payment_date DESC
    LIMIT 15
")->fetchAll();
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-chart-line text-primary me-2"></i> Business Analytics & Financial Reports</h3>
        <p class="text-secondary small mb-0">Audited cash flows, service line performance, and development velocity.</p>
    </div>
    <div>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i> Print / Save PDF
        </button>
    </div>
</div>

<!-- Revenue by Service Line Table -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h5 class="admin-card-title"><i class="fa-solid fa-table-list text-primary"></i> Revenue Breakdown by Project Type</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Service Line</th>
                    <th>Projects Count</th>
                    <th>Total Contract Value</th>
                    <th>Total Collected Revenue</th>
                    <th>Average Ticket Size</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($revenueByType as $rev): 
                    $avg = ($rev['total_count'] > 0) ? ($rev['total_value'] / $rev['total_count']) : 0;
                ?>
                    <tr>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($rev['project_type']); ?></td>
                        <td><span class="badge bg-secondary"><?php echo (int)$rev['total_count']; ?> Projects</span></td>
                        <td class="fw-semibold text-dark"><?php echo formatCurrency($rev['total_value']); ?></td>
                        <td class="fw-bold text-success"><?php echo formatCurrency($rev['total_collected']); ?></td>
                        <td class="text-secondary small"><?php echo formatCurrency($avg); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Developer Performance Metrics -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h5 class="admin-card-title"><i class="fa-solid fa-user-check text-primary"></i> Developer Delivery Performance</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Developer</th>
                    <th>Total Assigned</th>
                    <th>Delivered Projects</th>
                    <th>Currently Active</th>
                    <th>Generated Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($devMetrics as $dm): ?>
                    <tr>
                        <td class="fw-bold text-dark"><?php echo htmlspecialchars($dm['full_name']); ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo (int)$dm['total_handled']; ?></span></td>
                        <td><span class="badge bg-success-subtle text-success"><?php echo (int)$dm['delivered_count']; ?> Delivered</span></td>
                        <td><span class="badge bg-primary-subtle text-primary"><?php echo (int)$dm['active_count']; ?> Active</span></td>
                        <td class="fw-bold text-success"><?php echo formatCurrency($dm['generated_revenue']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Verified Payments Audit List -->
<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="admin-card-title"><i class="fa-solid fa-receipt text-primary"></i> Cleared Transactions Log</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer</th>
                    <th>Milestone Stage</th>
                    <th>Payment Method</th>
                    <th>Reference</th>
                    <th>Amount</th>
                    <th>Cleared Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($invoices as $inv): ?>
                    <tr>
                        <td class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($inv['job_id']); ?></td>
                        <td class="text-dark small fw-semibold"><?php echo htmlspecialchars($inv['customer_name']); ?></td>
                        <td><span class="badge bg-secondary small"><?php echo htmlspecialchars($inv['payment_stage']); ?></span></td>
                        <td class="small text-secondary"><?php echo htmlspecialchars($inv['payment_method']); ?></td>
                        <td class="small font-monospace text-secondary"><?php echo htmlspecialchars($inv['transaction_reference'] ?: '—'); ?></td>
                        <td class="fw-bold text-success"><?php echo formatCurrency($inv['amount']); ?></td>
                        <td class="small text-secondary"><?php echo formatDateOnly($inv['payment_date']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

