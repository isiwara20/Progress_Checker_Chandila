<?php
$adminTitle = 'Executive Business Dashboard';
require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Dashboard Metrics
$totalProjects = 0;
$newRequestsCount = 0;
$awaitingAdvanceCount = 0;
$activeProjectsCount = 0;
$completedProjectsCount = 0;
$pendingPaymentsCount = 0;
$totalRevenue = 0.00;
$totalOutstanding = 0.00;
$totalDevelopers = 0;

$statusDistribution = [];
$monthlyRevenue = ['labels' => [], 'amounts' => []];
$devWorkload = ['developers' => [], 'counts' => []];
$completedVsOngoing = [0, 0];

$recentRequests = [];
$recentPayments = [];
$recentActivities = [];

if ($pdo) {
    try {
        // 1. Total Projects
        $totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();

        // 2. New Project Requests
        $newRequestsCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('Request Submitted', 'Request Reviewed')")->fetchColumn();

        // 3. Awaiting Advance Payments
        $awaitingAdvanceCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status = 'Advance Payment Pending'")->fetchColumn();

        // 4. Active Projects (in development / review)
        $activeProjectsCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('Development Started', 'Development in Progress', 'Client Review / Revisions', 'Hosting & Deployment')")->fetchColumn();

        // 5. Completed & Delivered Projects
        $completedProjectsCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE status IN ('Project Completed', 'Project Delivered')")->fetchColumn();

        // 6. Pending Payments count
        $pendingPaymentsCount = (int)$pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'Pending'")->fetchColumn();

        // 7. Total Verified Revenue
        $totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'Payment Received'")->fetchColumn();

        // 8. Total Outstanding Balance
        $totalOutstanding = (float)$pdo->query("SELECT COALESCE(SUM(balance_due), 0) FROM projects WHERE status != 'Cancelled'")->fetchColumn();

        // 9. Number of Developers
        $totalDevelopers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'developer' AND status = 'active'")->fetchColumn();

        // 10. Showcase Posts & Career Applicants
        $totalPostsCount = 0;
        $totalApplicantsCount = 0;
        try {
            $totalPostsCount = (int)$pdo->query("SELECT COUNT(*) FROM posts")->fetchColumn();
            $totalApplicantsCount = (int)$pdo->query("SELECT COUNT(*) FROM career_applications")->fetchColumn();
        } catch (Exception $e) {}

        // Chart 1: Projects by Status
        $stmtStatus = $pdo->query("SELECT status, COUNT(*) as count FROM projects GROUP BY status");
        while ($row = $stmtStatus->fetch()) {
            $statusDistribution[$row['status']] = (int)$row['count'];
        }

        // Chart 2: Monthly Revenue (Last 6 Months)
        $stmtMonthly = $pdo->query("
            SELECT DATE_FORMAT(payment_date, '%b %Y') AS month_label, 
                   COALESCE(SUM(amount), 0) AS total_amount
            FROM payments
            WHERE status = 'Payment Received'
            GROUP BY YEAR(payment_date), MONTH(payment_date)
            ORDER BY payment_date ASC
            LIMIT 6
        ");
        while ($row = $stmtMonthly->fetch()) {
            $monthlyRevenue['labels'][] = $row['month_label'];
            $monthlyRevenue['amounts'][] = (float)$row['total_amount'];
        }
        if (empty($monthlyRevenue['labels'])) {
            $monthlyRevenue['labels'] = [date('M Y')];
            $monthlyRevenue['amounts'] = [0];
        }

        // Chart 3: Developer Workload
        $stmtDevs = $pdo->query("
            SELECT u.full_name, COUNT(p.id) as active_count
            FROM users u
            LEFT JOIN projects p ON u.id = p.assigned_developer_id 
                 AND p.status IN ('Development Started', 'Development in Progress', 'Client Review / Revisions')
            WHERE u.role = 'developer' AND u.status = 'active'
            GROUP BY u.id
        ");
        while ($row = $stmtDevs->fetch()) {
            $devWorkload['developers'][] = $row['full_name'];
            $devWorkload['counts'][] = (int)$row['active_count'];
        }

        // Chart 4: Completed vs Ongoing
        $completedVsOngoing = [$completedProjectsCount, $activeProjectsCount];

        // Recent Project Requests Table
        $stmtReq = $pdo->query("
            SELECT p.*, c.full_name AS customer_name, c.company_name
            FROM projects p
            JOIN customers c ON p.customer_id = c.id
            ORDER BY p.created_at DESC
            LIMIT 5
        ");
        $recentRequests = $stmtReq->fetchAll();

        // Recent Payments
        $stmtPay = $pdo->query("
            SELECT pm.*, p.job_id, p.project_title, c.full_name as customer_name
            FROM payments pm
            JOIN projects p ON pm.project_id = p.id
            JOIN customers c ON p.customer_id = c.id
            ORDER BY pm.payment_date DESC
            LIMIT 5
        ");
        $recentPayments = $stmtPay->fetchAll();

        // Recent Activities
        $stmtAct = $pdo->query("
            SELECT * FROM project_activities
            ORDER BY created_at DESC
            LIMIT 6
        ");
        $recentActivities = $stmtAct->fetchAll();

    } catch (Exception $e) {
        error_log("Dashboard query error: " . $e->getMessage());
    }
}
?>

<!-- Pass Data to JavaScript for Chart.js Rendering -->
<script>
    window.dashboardChartData = {
        statusData: <?php echo json_encode($statusDistribution); ?>,
        revenueData: <?php echo json_encode($monthlyRevenue); ?>,
        devWorkload: <?php echo json_encode($devWorkload); ?>,
        completedVsOngoing: <?php echo json_encode($completedVsOngoing); ?>
    };
</script>

<!-- Row 1: The 9 KPI Dashboard Statistics Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Projects -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Total Projects</div>
            <div class="stat-card-value"><?php echo $totalProjects; ?></div>
            <div class="text-secondary small mt-1">Across all lifecycle stages</div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-diagram-project"></i>
            </div>
        </div>
    </div>

    <!-- 2. New Project Requests -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">New Project Requests</div>
            <div class="stat-card-value text-warning"><?php echo $newRequestsCount; ?></div>
            <div class="text-secondary small mt-1"><a href="<?php echo BASE_URL; ?>/admin/requests.php" class="text-warning">Review submissions &rarr;</a></div>
            <div class="stat-icon-wrapper text-warning">
                <i class="fa-solid fa-inbox"></i>
            </div>
        </div>
    </div>

    <!-- 3. Projects Awaiting Advance Payments -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Awaiting Advance</div>
            <div class="stat-card-value text-info"><?php echo $awaitingAdvanceCount; ?></div>
            <div class="text-secondary small mt-1">Awaiting verification</div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
        </div>
    </div>

    <!-- 4. Active Projects -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Active Projects</div>
            <div class="stat-card-value text-primary"><?php echo $activeProjectsCount; ?></div>
            <div class="text-secondary small mt-1">Currently in development</div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>

    <!-- 5. Completed Projects -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Completed & Delivered</div>
            <div class="stat-card-value text-success"><?php echo $completedProjectsCount; ?></div>
            <div class="text-secondary small mt-1">Successfully deployed</div>
            <div class="stat-icon-wrapper text-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
    </div>

    <!-- 6. Pending Payments -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Pending Payment Slips</div>
            <div class="stat-card-value text-danger"><?php echo $pendingPaymentsCount; ?></div>
            <div class="text-secondary small mt-1"><a href="<?php echo BASE_URL; ?>/admin/payments.php" class="text-danger">Verify payments &rarr;</a></div>
            <div class="stat-icon-wrapper text-danger">
                <i class="fa-solid fa-wallet"></i>
            </div>
        </div>
    </div>

    <!-- 7. Total Revenue -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Total Verified Revenue</div>
            <div class="stat-card-value text-success" style="font-size: 1.5rem;"><?php echo formatCurrency($totalRevenue); ?></div>
            <div class="text-secondary small mt-1">Verified cash & bank transfers</div>
            <div class="stat-icon-wrapper text-success">
                <i class="fa-solid fa-money-bill-trend-up"></i>
            </div>
        </div>
    </div>

    <!-- 8. Total Outstanding Payments -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Total Outstanding Balance</div>
            <div class="stat-card-value text-warning" style="font-size: 1.5rem;"><?php echo formatCurrency($totalOutstanding); ?></div>
            <div class="text-secondary small mt-1">Milestones & final payments due</div>
            <div class="stat-icon-wrapper text-warning">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>
    </div>

    <!-- 9. Number of Developers -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card">
            <div class="stat-card-title">Active Developers</div>
            <div class="stat-card-value"><?php echo $totalDevelopers; ?></div>
            <div class="text-secondary small mt-1"><a href="<?php echo BASE_URL; ?>/admin/developers.php" class="text-info">Manage developer team &rarr;</a></div>
            <div class="stat-icon-wrapper">
                <i class="fa-solid fa-user-group"></i>
            </div>
        </div>
    </div>

    <!-- 10. 1080x1080 Showcase Posts -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card" style="border-left: 3px solid #eab308;">
            <div class="stat-card-title text-warning">1080x1080 Posts</div>
            <div class="stat-card-value text-warning"><?php echo $totalPostsCount; ?></div>
            <div class="text-secondary small mt-1"><a href="<?php echo BASE_URL; ?>/admin/posts.php" class="text-warning">Manage 1:1 Posts &rarr;</a></div>
            <div class="stat-icon-wrapper text-warning">
                <i class="fa-solid fa-photo-film"></i>
            </div>
        </div>
    </div>

    <!-- 11. Career Applicants -->
    <div class="col-sm-6 col-xl-4 col-xxl-3">
        <div class="stat-card" style="border-left: 3px solid #38bdf8;">
            <div class="stat-card-title text-info">Job Applicants</div>
            <div class="stat-card-value text-info"><?php echo $totalApplicantsCount; ?></div>
            <div class="text-secondary small mt-1"><a href="<?php echo BASE_URL; ?>/admin/careers.php" class="text-info">Review Applicants &rarr;</a></div>
            <div class="stat-icon-wrapper text-info">
                <i class="fa-solid fa-user-tie"></i>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: 4 Interactive Chart.js Widgets -->
<div class="row g-4 mb-4">
    <!-- Chart 1: Projects by Status -->
    <div class="col-lg-6 col-xl-3">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-chart-pie text-info"></i> Projects by Status</h5>
            </div>
            <div class="admin-card-body d-flex flex-column justify-content-center">
                <div style="height: 250px; position: relative;">
                    <canvas id="chartProjectsByStatus"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 2: Monthly Revenue -->
    <div class="col-lg-6 col-xl-4">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-chart-simple text-info"></i> Monthly Revenue (LKR)</h5>
            </div>
            <div class="admin-card-body">
                <div style="height: 250px; position: relative;">
                    <canvas id="chartMonthlyRevenue"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 3: Developer Workload -->
    <div class="col-lg-6 col-xl-3">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-user-gear text-info"></i> Developer Workload</h5>
            </div>
            <div class="admin-card-body">
                <div style="height: 250px; position: relative;">
                    <canvas id="chartDevWorkload"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart 4: Completed vs Ongoing -->
    <div class="col-lg-6 col-xl-2">
        <div class="admin-card h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-chart-donut text-info"></i> Delivery Ratio</h5>
            </div>
            <div class="admin-card-body d-flex flex-column justify-content-center">
                <div style="height: 250px; position: relative;">
                    <canvas id="chartCompletedVsOngoing"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Recent Requests Table & Recent Activity Feed -->
<div class="row g-4 mb-4">
    <!-- Recent Project Requests Table -->
    <div class="col-lg-8">
        <div class="admin-card mb-0 h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-inbox text-info"></i> Recent Project Submissions</h5>
                <a href="<?php echo BASE_URL; ?>/admin/requests.php" class="btn btn-sm btn-outline-info">View All Requests</a>
            </div>
            <div class="table-responsive">
                <table class="table table-chandila">
                    <thead>
                        <tr>
                            <th>Job ID</th>
                            <th>Customer</th>
                            <th>Project Title</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentRequests)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-secondary py-4">No project requests recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentRequests as $req): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $req['id']; ?>" class="fw-bold font-monospace text-primary">
                                            <?php echo htmlspecialchars($req['job_id']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($req['customer_name']); ?></div>
                                        <?php if (!empty($req['company_name'])): ?>
                                            <div class="small text-secondary"><?php echo htmlspecialchars($req['company_name']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($req['project_title']); ?>">
                                            <?php echo htmlspecialchars($req['project_title']); ?>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($req['project_type']); ?></span></td>
                                    <td><?php echo getStatusBadge($req['status']); ?></td>
                                    <td class="small text-secondary"><?php echo formatDateOnly($req['created_at']); ?></td>
                                    <td class="text-end">
                                        <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $req['id']; ?>" class="btn btn-sm btn-outline-primary" title="Review Project">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Live Activity Audit Timeline -->
    <div class="col-lg-4">
        <div class="admin-card mb-0 h-100">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-clock-rotate-left text-info"></i> Recent System Activity</h5>
            </div>
            <div class="admin-card-body p-3">
                <?php if (empty($recentActivities)): ?>
                    <div class="text-secondary text-center py-4 small">No recent activity logged.</div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($recentActivities as $act): ?>
                            <div class="p-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid #2563eb;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="badge bg-primary bg-opacity-10 text-primary font-monospace" style="font-size: 0.7rem;"><?php echo htmlspecialchars($act['job_id']); ?></span>
                                    <span class="text-muted" style="font-size: 0.72rem;"><?php echo formatDateTime($act['created_at'], 'M d, h:i A'); ?></span>
                                </div>
                                <div class="text-dark small fw-semibold"><?php echo htmlspecialchars($act['activity_type']); ?></div>
                                <div class="text-muted small mt-1" style="font-size: 0.8rem;"><?php echo htmlspecialchars($act['description']); ?></div>
                                <div class="text-muted mt-1" style="font-size: 0.72rem;"><i class="fa-regular fa-user me-1"></i> By: <?php echo htmlspecialchars($act['updated_by']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Admin Dashboard Real-Time Notifications Popup -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($newRequestsCount > 0): ?>
        setTimeout(() => {
            showChandilaToast(
                'warning', 
                'You have <strong><?php echo $newRequestsCount; ?></strong> inbound project request(s) awaiting pricing and review. <a href="<?php echo BASE_URL; ?>/admin/requests.php" class="text-primary fw-bold ms-1 text-decoration-underline">Review Requests &rarr;</a>', 
                'Pending Inbound Requests', 
                8000
            );
        }, 600);
    <?php endif; ?>

    <?php if ($pendingPaymentsCount > 0): ?>
        setTimeout(() => {
            showChandilaToast(
                'info', 
                'There are <strong><?php echo $pendingPaymentsCount; ?></strong> payment transaction(s) pending verification. <a href="<?php echo BASE_URL; ?>/admin/payments.php" class="text-primary fw-bold ms-1 text-decoration-underline">Verify Payments &rarr;</a>', 
                'Payment Verification Pending', 
                9000
            );
        }, 1200);
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

