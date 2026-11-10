<?php
$adminTitle = 'Assign Projects';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Project Assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_project') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $projectId = (int)($_POST['project_id'] ?? 0);
        $devId = !empty($_POST['developer_id']) ? (int)$_POST['developer_id'] : null;

        if ($projectId) {
            $stmtP = $pdo->prepare("SELECT p.*, c.phone, c.whatsapp_number FROM projects p JOIN customers c ON p.customer_id = c.id WHERE p.id = ?");
            $stmtP->execute([$projectId]);
            $proj = $stmtP->fetch();

            if ($proj) {
                $assignedDate = $devId ? date('Y-m-d H:i:s') : null;
                $pdo->prepare("UPDATE projects SET assigned_developer_id = ?, assigned_date = ? WHERE id = ?")->execute([$devId, $assignedDate, $projectId]);

                if ($devId) {
                    $devName = $pdo->query("SELECT full_name FROM users WHERE id = {$devId}")->fetchColumn();
                    logActivity($pdo, $proj['job_id'], $projectId, 'Developer Assigned', "Project assigned to {$devName}.", $currentUser['full_name']);

                    // Send WhatsApp Event 4: Project Assigned
                    WhatsAppService::send($pdo, $proj['whatsapp_number'] ?: $proj['phone'], 'Project Assigned', ['job_id' => $proj['job_id']], $projectId, $proj['job_id']);

                    setFlash('success', "Project {$proj['job_id']} assigned to {$devName} and customer notified via WhatsApp!");
                } else {
                    logActivity($pdo, $proj['job_id'], $projectId, 'Developer Unassigned', "Project developer unassigned.", $currentUser['full_name']);
                    setFlash('info', "Project {$proj['job_id']} is now unassigned.");
                }
                header("Location: " . BASE_URL . "/admin/assign.php");
                exit;
            }
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Developers with their current ongoing count
$developers = $pdo->query("
    SELECT u.*, 
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Client Review / Revisions') THEN 1 END) AS ongoing_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer' AND u.status = 'active'
    GROUP BY u.id
    ORDER BY u.full_name ASC
")->fetchAll();

// Fetch Projects that are confirmed, started, or unassigned
$projects = $pdo->query("
    SELECT p.*, c.full_name AS customer_name, u.full_name AS dev_name
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.status NOT IN ('Project Completed', 'Project Delivered', 'Cancelled')
    ORDER BY p.assigned_developer_id IS NULL DESC, p.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-user-check text-primary me-2"></i> Project Assignment Board</h3>
        <p class="text-secondary small mb-0">Assign or rebalance project workload across available developers.</p>
    </div>
</div>

<!-- Developer Capacities Summary -->
<div class="row g-3 mb-4">
    <?php foreach ($developers as $dev): ?>
        <div class="col-md-6 col-lg-4">
            <div class="chandila-card p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($dev['full_name']); ?></div>
                        <div class="text-secondary small"><?php echo htmlspecialchars($dev['skills']); ?></div>
                    </div>
                    <span class="badge bg-primary fs-6 px-3 py-2"><?php echo (int)$dev['ongoing_count']; ?> Active</span>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Assignment Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Project Title</th>
                    <th>Customer</th>
                    <th>Status</th>
                    <th>Target Date</th>
                    <th>Current Assignee</th>
                    <th>Quick Reassign</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">No active projects requiring assignment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $proj): ?>
                        <tr>
                            <td>
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $proj['id']; ?>" class="fw-bold font-monospace text-primary">
                                    <?php echo htmlspecialchars($proj['job_id']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark text-truncate" style="max-width: 250px;">
                                    <?php echo htmlspecialchars($proj['project_title']); ?>
                                </div>
                                <span class="badge bg-secondary small"><?php echo htmlspecialchars($proj['project_type']); ?></span>
                            </td>
                            <td class="text-dark small"><?php echo htmlspecialchars($proj['customer_name']); ?></td>
                            <td><?php echo getStatusBadge($proj['status']); ?></td>
                            <td class="small text-secondary"><?php echo formatDateOnly($proj['expected_completion_date']); ?></td>
                            <td>
                                <?php if (!empty($proj['dev_name'])): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo htmlspecialchars($proj['dev_name']); ?></span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i> Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="d-flex gap-2">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="action" value="assign_project">
                                    <input type="hidden" name="project_id" value="<?php echo $proj['id']; ?>">

                                    <select class="form-select form-select-chandila form-select-sm" name="developer_id" style="width: 180px;">
                                        <option value="">-- Unassign --</option>
                                        <?php foreach ($developers as $d): ?>
                                            <option value="<?php echo $d['id']; ?>" <?php echo ($proj['assigned_developer_id'] == $d['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($d['full_name']); ?> (<?php echo $d['ongoing_count']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-electric" title="Save Assignment">
                                        <i class="fa-solid fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

