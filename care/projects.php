<?php
$careTitle = 'Project Status Checker';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Customer Care Gate
requireCustomerCare();

$pdo = getDB();
$currentUser = getCurrentUser();

// Filters
$statusFilter = trim($_GET['status'] ?? '');
$typeFilter   = trim($_GET['type'] ?? '');
$devFilter    = trim($_GET['dev'] ?? '');
$search       = trim($_GET['q'] ?? '');

$where = ["p.status != 'Cancelled'"];
$params = [];

if (!empty($statusFilter)) {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}
if (!empty($typeFilter)) {
    $where[] = "p.project_type = ?";
    $params[] = $typeFilter;
}
if (!empty($devFilter)) {
    $where[] = "p.assigned_developer_id = ?";
    $params[] = $devFilter;
}
if (!empty($search)) {
    $where[] = "(p.job_id LIKE ? OR p.project_title LIKE ? OR c.full_name LIKE ? OR c.company_name LIKE ? OR c.phone LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

$whereSql = "WHERE " . implode(" AND ", $where);

$stmt = $pdo->prepare("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.phone, 
           c.whatsapp_number,
           u.full_name AS dev_name,
           u.phone AS dev_phone
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    {$whereSql}
    ORDER BY p.updated_at DESC
");
$stmt->execute($params);
$projects = $stmt->fetchAll();

// Fetch developers for filter
$allDevs = $pdo->query("SELECT id, full_name FROM users WHERE role = 'developer' ORDER BY full_name ASC")->fetchAll();

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Header -->
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-bars-progress text-primary me-2"></i> Project Status Checker</h3>
        <p class="text-secondary small mb-0">Search, monitor real-time development milestones, and assist customers with status inquiries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?php echo BASE_URL; ?>/care/orders.php" class="btn btn-electric btn-sm">
            <i class="fa-solid fa-cart-plus me-1"></i> Enter New Order
        </a>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <!-- Search -->
        <div class="col-md-3">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" class="form-control" name="q" placeholder="Job ID, Customer, Phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>

        <!-- Status Stage -->
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="status">
                <option value="">-- All Development Stages --</option>
                <?php foreach (PROJECT_STAGES as $stage): ?>
                    <option value="<?php echo htmlspecialchars($stage); ?>" <?php echo ($statusFilter === $stage) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($stage); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Project Type -->
        <div class="col-md-3">
            <select class="form-select form-select-sm" name="type">
                <option value="">-- All Project Types --</option>
                <?php foreach (PROJECT_TYPES as $type): ?>
                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($typeFilter === $type) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($type); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Developer -->
        <div class="col-md-2">
            <select class="form-select form-select-sm" name="dev">
                <option value="">-- All Developers --</option>
                <?php foreach ($allDevs as $d): ?>
                    <option value="<?php echo $d['id']; ?>" <?php echo ($devFilter == $d['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($d['full_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Buttons -->
        <div class="col-md-1 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100" title="Apply Filter"><i class="fa-solid fa-filter"></i></button>
            <?php if (!empty($search) || !empty($statusFilter) || !empty($typeFilter) || !empty($devFilter)): ?>
                <a href="<?php echo BASE_URL; ?>/care/projects.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Projects Table -->
<div class="admin-card">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 class="text-dark fw-bold mb-0">Total Projects Found: <?php echo count($projects); ?></h5>
        <span class="text-secondary small">Real-time status synced with developer progress updates</span>
    </div>

    <div class="table-responsive">
        <table class="table table-chandila mb-0">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Customer Name</th>
                    <th>Project Title & Scope</th>
                    <th>Development Stage</th>
                    <th>Progress</th>
                    <th>Assigned Dev</th>
                    <th>Target Date</th>
                    <th>Financials</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">
                            <i class="fa-solid fa-folder-open fa-3x text-secondary mb-2 d-block"></i>
                            No projects match the selected criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($p['customer_name']); ?></div>
                                <div class="small text-secondary"><?php echo htmlspecialchars($p['company_name'] ?: 'Individual'); ?></div>
                                <div class="small mt-1">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $p['whatsapp_number'] ?: $p['phone']); ?>" target="_blank" class="text-success text-decoration-none">
                                        <i class="fa-brands fa-whatsapp me-1"></i><?php echo htmlspecialchars($p['whatsapp_number'] ?: $p['phone']); ?>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 240px;" title="<?php echo htmlspecialchars($p['project_title']); ?>">
                                    <?php echo htmlspecialchars($p['project_title']); ?>
                                </div>
                                <span class="badge bg-light text-secondary border" style="font-size: 0.72rem;"><?php echo htmlspecialchars($p['project_type']); ?></span>
                            </td>
                            <td><?php echo getStatusBadge($p['status']); ?></td>
                            <td style="min-width: 110px;">
                                <div class="d-flex justify-content-between small text-secondary mb-1">
                                    <span class="fw-semibold"><?php echo (int)$p['progress_percentage']; ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: <?php echo (int)$p['progress_percentage']; ?>%;"></div>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($p['dev_name'])): ?>
                                    <span class="badge bg-info-subtle text-info border border-info-subtle">
                                        <i class="fa-solid fa-code me-1"></i> <?php echo htmlspecialchars($p['dev_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <a href="<?php echo BASE_URL; ?>/care/assign.php?project_id=<?php echo $p['id']; ?>" class="badge bg-warning-subtle text-warning border border-warning-subtle text-decoration-none">
                                        <i class="fa-solid fa-plus me-1"></i> Assign
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary">
                                <?php echo formatDateOnly($p['expected_completion_date']); ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark small"><?php echo formatCurrency($p['total_cost']); ?></div>
                                <?php if ($p['balance_due'] > 0): ?>
                                    <div class="small text-danger fw-semibold">Due: <?php echo formatCurrency($p['balance_due']); ?></div>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.68rem;">Paid in Full</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <a href="<?php echo BASE_URL; ?>/care/project_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary" title="Check Full Status & Milestone Logs">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <?php if ($p['balance_due'] > 0): ?>
                                        <a href="<?php echo BASE_URL; ?>/care/payments.php?project_id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-success" title="Mark Payment Done">
                                            <i class="fa-solid fa-credit-card"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-copy-tracker" 
                                        data-url="<?php echo BASE_URL; ?>/track_project.php?job_id=<?php echo urlencode($p['job_id']); ?>"
                                        title="Copy Client Live Tracker Link">
                                        <i class="fa-solid fa-link"></i>
                                    </button>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-copy-tracker').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.dataset.url;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    showChandilaToast('success', 'Client Live Tracking link copied to clipboard!');
                });
            } else {
                prompt('Copy this link:', url);
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

