<?php
$adminTitle = 'Manage Developers';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Add New Developer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        if ($action === 'create_developer') {
            $username = trim($_POST['username'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $phone    = trim($_POST['phone'] ?? '');
            $skills   = trim($_POST['skills'] ?? 'Full Stack Web Development');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($fullName) || empty($email) || empty($password)) {
                setFlash('error', 'All fields marked with an asterisk are required.');
            } else {
                // Check duplicate username or email
                $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $check->execute([$username, $email]);
                if ($check->fetch()) {
                    setFlash('error', 'A staff account with this username or email already exists.');
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, full_name, phone, role, skills, status, created_at)
                        VALUES (?, ?, ?, ?, ?, 'developer', ?, 'active', NOW())
                    ");
                    $ins->execute([$username, $email, $hash, $fullName, $phone, $skills]);

                    setFlash('success', "Developer '{$fullName}' account created successfully!");
                    header("Location: " . BASE_URL . "/admin/developers.php");
                    exit;
                }
            }
        } elseif ($action === 'toggle_status') {
            $devId = (int)($_POST['developer_id'] ?? 0);
            $newStatus = ($_POST['status'] === 'active') ? 'inactive' : 'active';
            $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role = 'developer'")->execute([$newStatus, $devId]);
            setFlash('info', "Developer account status updated to {$newStatus}.");
            header("Location: " . BASE_URL . "/admin/developers.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch all developers with active/completed workload metrics
$developers = $pdo->query("
    SELECT u.*,
           COUNT(p.id) AS total_assigned,
           COUNT(CASE WHEN p.status IN ('Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions') THEN 1 END) AS ongoing_count,
           COUNT(CASE WHEN p.status IN ('Project Completed', 'Project Delivered') THEN 1 END) AS completed_count
    FROM users u
    LEFT JOIN projects p ON u.id = p.assigned_developer_id
    WHERE u.role = 'developer'
    GROUP BY u.id
    ORDER BY u.created_at ASC
")->fetchAll();
?>

<div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-laptop-code text-primary me-2"></i> Engineering Team Management</h3>
        <p class="text-secondary small mb-0">Manage developer logins, credentials, assigned workloads, and tech skillsets.</p>
    </div>
    <div>
        <button type="button" class="btn btn-electric btn-sm" data-bs-toggle="modal" data-bs-target="#modalAddDeveloper">
            <i class="fa-solid fa-user-plus me-1"></i> Add New Developer
        </button>
    </div>
</div>

<!-- Developers Cards Grid -->
<div class="row g-4 mb-4">
    <?php foreach ($developers as $dev): ?>
        <div class="col-md-6 col-lg-4">
            <div class="chandila-card h-100 p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-circle" style="width: 50px; height: 50px; font-size: 1.4rem;">
                            <i class="fa-solid fa-laptop-code"></i>
                        </div>
                        <div>
                            <h5 class="text-dark fw-bold mb-0"><?php echo htmlspecialchars($dev['full_name']); ?></h5>
                            <span class="text-secondary small">@<?php echo htmlspecialchars($dev['username']); ?></span>
                        </div>
                    </div>
                    <?php if ($dev['status'] === 'active'): ?>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Inactive</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <span class="text-secondary small d-block">Specializations:</span>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo htmlspecialchars($dev['skills']); ?></span>
                </div>

                <div class="row g-2 text-center mb-4 pt-2 border-top">
                    <div class="col-4">
                        <div class="text-secondary small" style="font-size: 0.72rem;">Assigned</div>
                        <div class="fw-bold text-dark fs-5"><?php echo (int)$dev['total_assigned']; ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-secondary small" style="font-size: 0.72rem;">Ongoing</div>
                        <div class="fw-bold text-primary fs-5"><?php echo (int)$dev['ongoing_count']; ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-secondary small" style="font-size: 0.72rem;">Completed</div>
                        <div class="fw-bold text-success fs-5"><?php echo (int)$dev['completed_count']; ?></div>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <a href="<?php echo BASE_URL; ?>/admin/projects.php?dev=<?php echo $dev['id']; ?>" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fa-solid fa-folder-open me-1"></i> View Projects
                    </a>
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="toggle_status">
                        <input type="hidden" name="developer_id" value="<?php echo $dev['id']; ?>">
                        <input type="hidden" name="status" value="<?php echo $dev['status']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Toggle active status">
                            <i class="fa-solid fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal: Add New Developer -->
<div class="modal fade" id="modalAddDeveloper" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="create_developer">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i> Add New Developer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="username" placeholder="e.g. dev3" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="full_name" placeholder="e.g. Developer 03" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control form-control-chandila" name="email" placeholder="dev3@chandilaweb.com" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Phone / WhatsApp</label>
                        <input type="text" class="form-control form-control-chandila" name="phone" placeholder="+94 77 999 8888">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Technical Skills</label>
                        <input type="text" class="form-control form-control-chandila" name="skills" placeholder="e.g. Backend Architecture, PHP 8.2, MySQL, APIs" value="Full Stack Web Development">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Initial Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control form-control-chandila" name="password" placeholder="••••••••" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-electric">Create Developer Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

