<?php
$adminTitle = 'All Projects Directory';
require_once __DIR__ . '/../includes/admin_header.php';

// Filter parameters
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter   = trim($_GET['type'] ?? '');
$devFilter    = trim($_GET['dev'] ?? '');
$search       = trim($_GET['q'] ?? '');

// Build query
$whereClauses = [];
$params = [];

if (!empty($statusFilter)) {
    $whereClauses[] = "p.status = ?";
    $params[] = $statusFilter;
}
if (!empty($typeFilter)) {
    $whereClauses[] = "p.project_type = ?";
    $params[] = $typeFilter;
}
if (!empty($devFilter)) {
    $whereClauses[] = "p.assigned_developer_id = ?";
    $params[] = $devFilter;
}
if (!empty($search)) {
    $whereClauses[] = "(p.job_id LIKE ? OR p.project_title LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}

$whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

$stmt = $pdo->prepare("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.whatsapp_number, 
           c.phone AS customer_phone,
           u.full_name AS dev_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    {$whereSql}
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Fetch developers for filter
$allDevs = $pdo->query("SELECT id, full_name FROM users WHERE role = 'developer' ORDER BY full_name ASC")->fetchAll();
?>

<!-- Top Header with Actions -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-diagram-project text-primary me-2"></i> Master Projects Directory</h3>
        <p class="text-secondary small mb-0">Total Projects: <strong><?php echo count($projects); ?></strong> • Track lifecycle from request to live deployment</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>/request_project.php" target="_blank" class="btn btn-electric btn-sm">
            <i class="fa-solid fa-plus me-1"></i> New Project
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <!-- Search -->
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control form-control-chandila py-1" name="q" placeholder="Job ID or Customer..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>

        <!-- Status Filter -->
        <div class="col-md-3">
            <select class="form-select form-select-chandila py-1 form-select-sm" name="status">
                <option value="">-- All Status Stages --</option>
                <?php foreach (PROJECT_STAGES as $stage): ?>
                    <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo ($statusFilter === $stage) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stage); ?>
                    </option>
                <?php endforeach; ?>
                <option value="Cancelled" <?php echo ($statusFilter === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
            </select>
        </div>

        <!-- Project Type Filter -->
        <div class="col-md-3">
            <select class="form-select form-select-chandila py-1 form-select-sm" name="type">
                <option value="">-- All Project Types --</option>
                <?php foreach (PROJECT_TYPES as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($typeFilter === $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Developer Filter -->
        <div class="col-md-2">
            <select class="form-select form-select-chandila py-1 form-select-sm" name="dev">
                <option value="">-- All Developers --</option>
                <?php foreach ($allDevs as $dev): ?>
                    <option value="<?php echo $dev['id']; ?>" <?php echo ($devFilter == $dev['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($dev['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Submit & Reset -->
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100 py-1" title="Apply Filter">
                <i class="fa-solid fa-filter"></i>
            </button>
            <?php if (!empty($statusFilter) || !empty($typeFilter) || !empty($devFilter) || !empty($search)): ?>
                <a href="<?php echo BASE_URL; ?>/admin/projects.php" class="btn btn-sm btn-outline-secondary py-1" title="Clear Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
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
                    <th>Customer Name</th>
                    <th>Project Scope</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Assigned Dev</th>
                    <th>Total Cost</th>
                    <th>Balance Due</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-folder-open fa-3x text-secondary mb-2 d-block"></i>
                            No projects found matching the filter criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $p['id']; ?>" class="fw-bold font-monospace text-primary">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </a>
                                <div class="text-secondary small"><?php echo formatDateOnly($p['created_at']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <?php if (!empty($p['company_name'])): ?>
                                    <div class="text-secondary small"><?php echo htmlspecialchars($p['company_name']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 220px;" title="<?php echo htmlspecialchars($p['project_title']); ?>">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                </div>
                                <span class="badge bg-secondary small"><?php echo htmlspecialchars($p['project_type']); ?></span>
                            </td>
                            <td>
                                <?php echo getStatusBadge($p['status']); ?>
                            </td>
                            <td style="min-width: 110px;">
                                <div class="d-flex justify-content-between text-secondary small mb-1">
                                    <span><?php echo (int)$p['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress-chandila">
                                    <div class="progress-bar-chandila" style="width: <?php echo (int)$p['progress_percentage']; ?>%; height: 100%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($p['dev_name'])): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo htmlspecialchars($p['dev_name']); ?></span>
                                <?php else: ?>
                                    <span class="text-secondary small fst-italic">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="fw-bold text-dark small"><?php echo formatCurrency($p['total_cost']); ?></span>
                            </td>
                            <td>
                                <?php if ($p['balance_due'] > 0): ?>
                                    <span class="text-warning fw-bold small"><?php echo formatCurrency($p['balance_due']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success small">Settled</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Project Command Center">
                                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

