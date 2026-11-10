<?php
$devTitle = 'Developer Dashboard';
require_once __DIR__ . '/../includes/dev_header.php';

$devId = $currentUser['id'];

// Metrics for logged-in developer
$totalAssigned = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE assigned_developer_id = {$devId}")->fetchColumn();
$ongoingCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE assigned_developer_id = {$devId} AND status IN ('Development Started', 'Development in Progress', 'Client Review / Revisions')")->fetchColumn();
$completedCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE assigned_developer_id = {$devId} AND status IN ('Development Completed', 'Hosting & Deployment', 'Project Completed', 'Project Delivered')")->fetchColumn();

// Pending Tasks / Milestones count
$pendingMilestonesCount = (int)$pdo->query("
    SELECT COUNT(*) FROM project_milestones m 
    JOIN projects p ON m.project_id = p.id 
    WHERE p.assigned_developer_id = {$devId} AND m.is_completed = 0
")->fetchColumn();

// Fetch Assigned Projects
$assignedProjects = $pdo->query("
    SELECT p.*, c.full_name as customer_name, c.company_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.assigned_developer_id = {$devId}
    ORDER BY p.expected_completion_date ASC
")->fetchAll();
?>

<!-- Welcome Banner -->
<div class="admin-card p-4 mb-4" style="background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border-left: 4px solid #2563eb;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <h3 class="text-dark fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>!</h3>
            <p class="text-muted small mb-0">Here is your active sprint queue, upcoming milestones, and delivery deadlines.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo BASE_URL; ?>/developer/my_projects.php" class="btn btn-electric btn-sm">
                <i class="fa-solid fa-laptop-code me-1"></i> View All My Projects
            </a>
        </div>
    </div>
</div>

<!-- Developer KPI Cards -->
<div class="row g-3 mb-4">
    <!-- 1. Total Assigned -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card">
            <div class="stat-card-title">Assigned Projects</div>
            <div class="stat-card-value"><?php echo $totalAssigned; ?></div>
            <div class="text-secondary small mt-1">Total in your queue</div>
            <div class="stat-icon-wrapper"><i class="fa-solid fa-list-check"></i></div>
        </div>
    </div>

    <!-- 2. Ongoing Projects -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="border-left: 3px solid #2563eb;">
            <div class="stat-card-title text-primary">Ongoing Sprints</div>
            <div class="stat-card-value text-primary"><?php echo $ongoingCount; ?></div>
            <div class="text-secondary small mt-1">Currently in progress</div>
            <div class="stat-icon-wrapper text-primary"><i class="fa-solid fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <!-- 3. Pending Milestone Tasks -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Pending Tasks</div>
            <div class="stat-card-value text-warning"><?php echo $pendingMilestonesCount; ?></div>
            <div class="text-secondary small mt-1">Checklist milestones</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-tasks"></i></div>
        </div>
    </div>

    <!-- 4. Completed Projects -->
    <div class="col-sm-6 col-lg-3">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Delivered Projects</div>
            <div class="stat-card-value text-success"><?php echo $completedCount; ?></div>
            <div class="text-secondary small mt-1">Completed assignments</div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-circle-check"></i></div>
        </div>
    </div>
</div>

<!-- My Assigned Projects Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="admin-card-title"><i class="fa-solid fa-laptop-code text-primary"></i> My Active Assignments</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Project Scope</th>
                    <th>Customer Name</th>
                    <th>Current Status</th>
                    <th>Progress</th>
                    <th>Target Deadline</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($assignedProjects)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-mug-hot fa-3x text-secondary mb-2 d-block"></i>
                            You currently have no assigned projects in your queue.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($assignedProjects as $p): 
                        $today = new DateTime('today');
                        $target = !empty($p['expected_completion_date']) ? new DateTime($p['expected_completion_date']) : null;
                        $isOverdue = $target && ($today > $target);
                    ?>
                        <tr>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/developer/project_view.php?id=<?php echo $p['id']; ?>" class="fw-bold font-monospace text-primary">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                </div>
                                <span class="badge bg-secondary small"><?php echo htmlspecialchars($p['project_type']); ?></span>
                            </td>
                            <td>
                                <div class="text-dark small"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <?php if (!empty($p['company_name'])): ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($p['company_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getStatusBadge($p['status']); ?></td>
                            <td style="min-width: 120px;">
                                <div class="d-flex justify-content-between text-secondary small mb-1">
                                    <span><?php echo (int)$p['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress-chandila">
                                    <div class="progress-bar-chandila" style="width: <?php echo (int)$p['progress_percentage']; ?>%; height: 100%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger">Overdue: <?php echo formatDateOnly($p['expected_completion_date']); ?></span>
                                <?php else: ?>
                                    <span class="text-info fw-semibold small"><?php echo formatDateOnly($p['expected_completion_date']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo BASE_URL; ?>/developer/project_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-electric">
                                    <i class="fa-solid fa-code me-1"></i> Open Workspace
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if ($ongoingCount > 0): ?>
        setTimeout(() => {
            showChandilaToast(
                'info',
                'You have <strong><?php echo $ongoingCount; ?></strong> active development sprint(s) in progress. <a href="<?php echo BASE_URL; ?>/developer/my_projects.php" class="text-primary fw-bold ms-1 text-decoration-underline">View Queue &rarr;</a>',
                'Active Sprints',
                7000
            );
        }, 600);
    <?php endif; ?>
});
</script>

<?php require_once __DIR__ . '/../includes/dev_footer.php'; ?>

