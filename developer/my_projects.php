<?php
$devTitle = 'My Assigned Projects';
require_once __DIR__ . '/../includes/dev_header.php';

$devId = $currentUser['id'];
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = ["p.assigned_developer_id = ?"];
$params = [$devId];

if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}
if (!empty($search)) {
    $where[] = "(p.job_id LIKE ? OR p.project_title LIKE ? OR c.full_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}

$whereSql = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT p.*, c.full_name as customer_name, c.company_name, c.whatsapp_number
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    {$whereSql}
    ORDER BY p.expected_completion_date ASC
");
$stmt->execute($params);
$projects = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-code text-primary me-2"></i> My Assigned Projects</h3>
        <p class="text-secondary small mb-0">Total Projects: <strong><?php echo count($projects); ?></strong> • Restricted to your developer assignments</p>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-md-6">
            <input type="text" class="form-control form-control-chandila form-control-sm" name="q" placeholder="Job ID, title, customer..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-chandila form-select-sm" name="status">
                <option value="">-- All Status Stages --</option>
                <?php foreach (PROJECT_STAGES as $st): ?>
                    <option value="<?php echo htmlspecialchars($st); ?>" <?php echo ($statusFilter === $st) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($st); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($statusFilter) || !empty($search)): ?>
                <a href="<?php echo BASE_URL; ?>/developer/my_projects.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Projects Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Project Scope</th>
                    <th>Customer Name</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Deadline</th>
                    <th class="text-end">Workspace</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">No assigned projects found matching your query.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
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
                                <span class="text-dark small"><?php echo htmlspecialchars($p['customer_name']); ?></span>
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
                                <span class="text-primary small fw-semibold"><?php echo formatDateOnly($p['expected_completion_date']); ?></span>
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

<?php require_once __DIR__ . '/../includes/dev_footer.php'; ?>

