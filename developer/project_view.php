<?php
$devTitle = 'Developer Project Workspace';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Strict Developer Gate
requireDeveloper();

$pdo = getDB();
$currentUser = getCurrentUser();
$projectId = (int)($_GET['id'] ?? 0);
$devId = $currentUser['id'];

// Fetch project with strict assignment check
$stmt = $pdo->prepare("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name, 
           c.email AS customer_email, 
           c.phone AS customer_phone, 
           c.whatsapp_number
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    WHERE p.id = ? AND p.assigned_developer_id = ?
");
$stmt->execute([$projectId, $devId]);
$project = $stmt->fetch();

if (!$project) {
    http_response_code(403);
    require_once __DIR__ . '/../includes/dev_header.php';
    ?>
    <div class="admin-card p-5 text-center my-5 mx-auto" style="max-width: 600px;">
        <i class="fa-solid fa-lock fa-4x text-danger mb-3"></i>
        <h3 class="text-dark">Access Denied</h3>
        <p class="text-secondary">This project is not assigned to your developer account. Developers are strictly authorized to view and manage only their assigned projects.</p>
        <a href="<?php echo BASE_URL; ?>/developer/my_projects.php" class="btn btn-primary mt-2">
            <i class="fa-solid fa-arrow-left me-1"></i> Return to My Assigned Projects
        </a>
    </div>
    <?php
    require_once __DIR__ . '/../includes/dev_footer.php';
    exit;
}

// Handle Developer Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $action = $_POST['action'];

        // 1. Update Progress & Log
        if ($action === 'update_dev_progress') {
            $progressPct = max(0, min(100, (int)($_POST['progress_percentage'] ?? 0)));
            $newStatus   = trim($_POST['status'] ?? 'Development in Progress');
            $desc        = trim($_POST['progress_description'] ?? '');
            $internalNote= trim($_POST['internal_note'] ?? '');
            $notifyWa    = !empty($_POST['notify_whatsapp']);

            // Allowed statuses for developer (includes 50% milestone: Second Payment Pending)
            $allowedDevStatuses = ['Development Started', 'Development in Progress', 'Second Payment Pending', 'Client Review / Revisions', 'Development Completed'];
            if (!in_array($newStatus, $allowedDevStatuses, true)) {
                $newStatus = 'Development in Progress';
            }

            if (empty($desc)) {
                setFlash('error', 'Please enter a progress description.');
            } else {
                $up = $pdo->prepare("
                    UPDATE projects SET 
                        progress_percentage = ?,
                        status = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $up->execute([$progressPct, $newStatus, $projectId]);

                // Insert into project_progress table
                $progStmt = $pdo->prepare("
                    INSERT INTO project_progress (project_id, percentage, status_stage, description, internal_note, updated_by, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $progStmt->execute([$projectId, $progressPct, $newStatus, $desc, $internalNote ?: null, $devId]);

                // Log Activity
                logActivity($pdo, $project['job_id'], $projectId, 'Progress Updated', "Developer {$currentUser['full_name']} updated progress to {$progressPct}% ({$newStatus}).", $currentUser['full_name']);

                // WhatsApp & Email Multi-Channel Notification Triggers
                $phone = $project['whatsapp_number'] ?: $project['customer_phone'];
                if ($newStatus === 'Development Completed') {
                    // Send Multi-Channel Notification: Development Completed (WhatsApp & Email)
                    WhatsAppService::send($pdo, $phone, 'Development Completed', [
                        'job_id'         => $project['job_id'],
                        'customer_email' => $project['customer_email'],
                        'customer_name'  => $project['customer_name']
                    ], $projectId, $project['job_id']);
                } elseif ($newStatus === 'Second Payment Pending') {
                    // Send Multi-Channel Notification: 50% Milestone Invoice (WhatsApp & Email)
                    WhatsAppService::send($pdo, $phone, 'Second Payment Reminder', [
                        'job_id'              => $project['job_id'],
                        'customer_email'      => $project['customer_email'],
                        'customer_name'       => $project['customer_name'],
                        'project_title'       => $project['project_title'],
                        'progress_percentage' => $progressPct,
                        'latest_update'       => $desc
                    ], $projectId, $project['job_id'], true);

                    logActivity($pdo, $project['job_id'], $projectId, 'Milestone Invoice Sent', "Dispatched 50% Milestone Payment Invoice to client via WhatsApp & Email.", $currentUser['full_name']);
                } elseif ($notifyWa) {
                    // Send Multi-Channel Notification: Project Progress Updated (WhatsApp & Email)
                    WhatsAppService::send($pdo, $phone, 'Project Progress Updated', [
                        'job_id'              => $project['job_id'],
                        'customer_email'      => $project['customer_email'],
                        'customer_name'       => $project['customer_name'],
                        'progress_percentage' => $progressPct,
                        'latest_update'       => $desc
                    ], $projectId, $project['job_id']);
                }

                if ($newStatus === 'Second Payment Pending') {
                    setFlash('success', "Progress updated to {$progressPct}% (Second Payment Pending). 50% Milestone Payment Invoice sent to {$project['customer_name']} via WhatsApp & Email!");
                } else {
                    setFlash('success', "Progress updated to {$progressPct}% successfully!");
                }
                header("Location: " . BASE_URL . "/developer/project_view.php?id=" . $projectId);
                exit;
            }
        }

        // 2. Add New Milestone Task
        if ($action === 'add_milestone') {
            $milestoneName = trim($_POST['milestone_name'] ?? '');
            $targetPct     = max(0, min(100, (int)($_POST['target_percentage'] ?? 0)));
            $notes         = trim($_POST['milestone_notes'] ?? '');

            if (!empty($milestoneName)) {
                $insM = $pdo->prepare("
                    INSERT INTO project_milestones (project_id, milestone_name, target_percentage, is_completed, notes, created_at)
                    VALUES (?, ?, ?, 0, ?, NOW())
                ");
                $insM->execute([$projectId, $milestoneName, $targetPct, $notes]);

                logActivity($pdo, $project['job_id'], $projectId, 'Milestone Added', "Added milestone: {$milestoneName}", $currentUser['full_name']);
                setFlash('success', 'Milestone task added to checklist.');
                header("Location: " . BASE_URL . "/developer/project_view.php?id=" . $projectId);
                exit;
            }
        }
    }
}

// Fetch Milestones
$milestones = $pdo->prepare("SELECT * FROM project_milestones WHERE project_id = ? ORDER BY target_percentage ASC");
$milestones->execute([$projectId]);
$milestoneList = $milestones->fetchAll();

// Fetch Progress History
$progressHistory = $pdo->prepare("
    SELECT p.*, u.full_name as author_name 
    FROM project_progress p 
    LEFT JOIN users u ON p.updated_by = u.id 
    WHERE p.project_id = ? 
    ORDER BY p.created_at DESC
");
$progressHistory->execute([$projectId]);
$progressLogs = $progressHistory->fetchAll();

// Fetch Uploaded Files
$files = $pdo->prepare("SELECT * FROM uploaded_files WHERE project_id = ? ORDER BY created_at DESC");
$files->execute([$projectId]);
$fileList = $files->fetchAll();

require_once __DIR__ . '/../includes/dev_header.php';
?>

<!-- Top Header -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-2">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace fs-6">
                <i class="fa-solid fa-barcode me-1"></i> <?php echo htmlspecialchars($project['job_id']); ?>
            </span>
            <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_type']); ?></span>
            <?php echo getStatusBadge($project['status']); ?>
        </div>
        <h2 class="text-dark fw-bold mb-1"><?php echo htmlspecialchars($project['project_title']); ?></h2>
        <div class="text-secondary small">
            Customer: <strong><?php echo htmlspecialchars($project['customer_name']); ?></strong> 
            • Assigned to: <strong><?php echo htmlspecialchars($currentUser['full_name']); ?></strong>
        </div>
    </div>
    <div class="text-md-end">
        <span class="text-secondary small d-block">Target Deadline:</span>
        <span class="text-primary fw-bold fs-5">
            <i class="fa-regular fa-calendar-check me-1"></i> <?php echo formatDateOnly($project['expected_completion_date']); ?>
        </span>
    </div>
</div>

<!-- Progress Bar -->
<div class="admin-card p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-dark fw-bold"><i class="fa-solid fa-gauge text-primary me-2"></i> Current Sprint Progress</span>
        <span class="text-primary fw-bold fs-5"><?php echo (int)$project['progress_percentage']; ?>%</span>
    </div>
    <div class="progress" style="height: 14px; background-color: #e2e8f0; border: 1px solid #cbd5e1; border-radius: 20px;">
        <div class="progress-bar progress-bar-striped progress-bar-animated" 
             style="width: <?php echo (int)$project['progress_percentage']; ?>%; background: linear-gradient(90deg, #d97706, #2563eb);" 
             role="progressbar"></div>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Scope, Milestones Checklist, Files -->
    <div class="col-lg-7">
        <!-- Project Specifications Card -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-file-code text-primary"></i> Project Specifications & Scope</h5>
            </div>
            <div class="admin-card-body">
                <div class="mb-4">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2">Project Description</h6>
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; font-size: 0.92rem; white-space: pre-line;">
                        <?php echo htmlspecialchars($project['description']); ?>
                    </div>
                </div>

                <div class="mb-4">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2">Required Features & Modules</h6>
                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0; color: #0f172a; font-size: 0.92rem; white-space: pre-line;">
                        <?php echo htmlspecialchars($project['required_features']); ?>
                    </div>
                </div>

                <?php if (!empty($project['reference_url'])): ?>
                    <div class="mb-3">
                        <span class="text-secondary small">Reference Site URL:</span>
                        <div>
                            <a href="<?php echo htmlspecialchars($project['reference_url']); ?>" target="_blank" class="text-primary fw-semibold">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> <?php echo htmlspecialchars($project['reference_url']); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Attachments Download -->
                <div class="pt-3 border-top">
                    <h6 class="text-dark fw-bold small text-uppercase mb-2"><i class="fa-solid fa-paperclip text-primary me-1"></i> Project Attachments</h6>
                    <?php if (empty($fileList)): ?>
                        <div class="text-secondary small fst-italic">No attachments uploaded.</div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($fileList as $f): ?>
                                <a href="<?php echo BASE_URL . '/' . htmlspecialchars($f['file_path']); ?>" target="_blank" download class="btn btn-sm btn-light border text-primary">
                                    <i class="fa-solid fa-download me-1"></i> <?php echo htmlspecialchars($f['original_name']); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Milestones Checklist Card (Interactive AJAX toggle) -->
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-list-check text-primary"></i> Sprint Milestones Checklist</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalAddMilestone">
                    <i class="fa-solid fa-plus me-1"></i> Add Task
                </button>
            </div>
            <div class="admin-card-body">
                <p class="text-secondary small mb-3">Check items as you complete them. Status updates live via AJAX.</p>

                <?php if (empty($milestoneList)): ?>
                    <div class="text-secondary text-center py-4 small">
                        No milestone tasks created yet. Click "+ Add Task" to set up your sprint checklist.
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush bg-transparent">
                        <?php foreach ($milestoneList as $m): ?>
                            <div class="list-group-item bg-transparent text-dark border-bottom px-0 py-2 d-flex align-items-center justify-content-between">
                                <div class="form-check d-flex align-items-center gap-2">
                                    <input class="form-check-input milestone-check-input" 
                                           type="checkbox" 
                                           data-milestone-id="<?php echo $m['id']; ?>" 
                                           id="m_<?php echo $m['id']; ?>" 
                                           <?php echo $m['is_completed'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label <?php echo $m['is_completed'] ? 'text-decoration-line-through text-muted' : ''; ?>" 
                                           id="milestone_label_<?php echo $m['id']; ?>" 
                                           for="m_<?php echo $m['id']; ?>">
                                        <?php echo htmlspecialchars($m['milestone_name']); ?>
                                    </label>
                                </div>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo (int)$m['target_percentage']; ?>% Target</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress History Feed -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Progress Updates Feed</h5>
            </div>
            <div class="admin-card-body">
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($progressLogs as $pLog): ?>
                        <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-primary"><?php echo (int)$pLog['percentage']; ?>% • <?php echo htmlspecialchars($pLog['status_stage']); ?></span>
                                <span class="text-secondary small"><?php echo formatDateTime($pLog['created_at']); ?></span>
                            </div>
                            <div class="text-dark small mt-2"><?php echo nl2br(htmlspecialchars($pLog['description'])); ?></div>
                            <?php if (!empty($pLog['internal_note'])): ?>
                                <div class="mt-2 p-2 rounded bg-warning bg-opacity-10 border border-warning border-opacity-30 text-dark small">
                                    <i class="fa-solid fa-lock me-1"></i> <strong>Internal Note:</strong> <?php echo htmlspecialchars($pLog['internal_note']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Developer Progress Update Form & Customer Contacts -->
    <div class="col-lg-5">
        <!-- Update Progress Form Card -->
        <div class="admin-card mb-4" style="border-top: 3px solid #2563eb;">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-pen-nib text-primary"></i> Update Development Progress</h5>
            </div>
            <div class="admin-card-body">
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="update_dev_progress">

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Progress Percentage (0% – 100%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" min="0" max="100" class="form-control form-control-chandila" name="progress_percentage" id="devProgressInput" value="<?php echo (int)$project['progress_percentage']; ?>" required>
                            <span class="input-group-text bg-light border text-primary fw-bold">%</span>
                        </div>
                        <div class="form-text text-secondary small">Auto-adjusts with stage changes; developers can also type custom values.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Development Status Stage <span class="text-danger">*</span></label>
                        <select class="form-select form-select-chandila" name="status" id="devStatusSelect" required>
                            <option value="Development Started" <?php echo ($project['status'] === 'Development Started') ? 'selected' : ''; ?>>Development Started (20%)</option>
                            <option value="Development in Progress" <?php echo ($project['status'] === 'Development in Progress') ? 'selected' : ''; ?>>Development in Progress (40%)</option>
                            <option value="Second Payment Pending" <?php echo ($project['status'] === 'Second Payment Pending') ? 'selected' : ''; ?>>Second Payment Pending (50% Milestone Reached)</option>
                            <option value="Client Review / Revisions" <?php echo ($project['status'] === 'Client Review / Revisions') ? 'selected' : ''; ?>>Client Review / Revisions (75%)</option>
                            <option value="Development Completed" <?php echo ($project['status'] === 'Development Completed') ? 'selected' : ''; ?>>Development Completed (100% - Ready for Final Review)</option>
                        </select>
                        <div id="devMilestoneNotice" class="alert alert-warning border-warning d-flex align-items-center gap-2 py-2 px-3 small mt-2 <?php echo ($project['status'] === 'Second Payment Pending' || (int)$project['progress_percentage'] === 50) ? '' : 'd-none'; ?>">
                            <i class="fa-solid fa-file-invoice-dollar fs-5 text-warning flex-shrink-0"></i>
                            <div>
                                <strong>50% Milestone Invoice Notice:</strong> Saving this stage will automatically generate and send a <strong>Second Payment Invoice</strong> to the client via <strong>WhatsApp &amp; Email</strong> with bank deposit details.
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Public Progress Description <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-chandila" name="progress_description" id="devProgressDesc" rows="3" placeholder="e.g. 50% – Core application modules constructed and staged. Second milestone payment invoice dispatched." required></textarea>
                        <div class="form-text text-secondary small">This description is shown on the customer's live tracking timeline.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Private Internal Developer Note (Team Only)</label>
                        <textarea class="form-control form-control-chandila" name="internal_note" rows="2" placeholder="e.g. Database migrations run on dev server. Stripe sandbox keys configured."></textarea>
                        <div class="form-text text-secondary small">Hidden from customers. Visible to Admin and Developer team.</div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="notify_whatsapp" value="1" id="notifyDevWaCheck" checked>
                        <label class="form-check-label text-dark small fw-bold" for="notifyDevWaCheck">
                            <i class="fa-brands fa-whatsapp text-success me-1"></i><i class="fa-solid fa-envelope text-primary me-1"></i> Send WhatsApp &amp; Email update to customer now
                        </label>
                    </div>

                    <button type="submit" class="btn btn-electric w-100 py-3 fw-bold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Update & Dispatch Notification
                    </button>
                </form>
            </div>
        </div>

        <!-- Customer Contact Details for Development -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title"><i class="fa-solid fa-address-card text-primary"></i> Client Contact Information</h5>
            </div>
            <div class="admin-card-body">
                <div class="mb-3">
                    <span class="text-secondary small">Customer Full Name:</span>
                    <div class="text-dark fw-bold"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                </div>
                <?php if (!empty($project['company_name'])): ?>
                    <div class="mb-3">
                        <span class="text-secondary small">Company:</span>
                        <div class="text-dark"><?php echo htmlspecialchars($project['company_name']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="mb-3">
                    <span class="text-secondary small">Email:</span>
                    <div><a href="mailto:<?php echo htmlspecialchars($project['customer_email']); ?>" class="text-primary"><?php echo htmlspecialchars($project['customer_email']); ?></a></div>
                </div>
                <div class="mb-3">
                    <span class="text-secondary small">WhatsApp / Phone:</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-dark fw-semibold"><?php echo htmlspecialchars($project['whatsapp_number'] ?: $project['customer_phone']); ?></span>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $project['whatsapp_number'] ?: $project['customer_phone']); ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="Chat on WhatsApp">
                            <i class="fa-brands fa-whatsapp"></i> Chat
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Milestone Task -->
<div class="modal fade" id="modalAddMilestone" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?id=<?php echo $projectId; ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="add_milestone">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-list-check text-primary me-2"></i> Add Sprint Task Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Milestone / Task Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="milestone_name" placeholder="e.g. Integrate PayHere payment webhook" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Target Project Percentage</label>
                        <input type="number" min="0" max="100" class="form-control form-control-chandila" name="target_percentage" placeholder="e.g. 75">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Technical Notes (Optional)</label>
                        <textarea class="form-control form-control-chandila" name="milestone_notes" rows="2" placeholder="Implementation details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-electric">Add Milestone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('devStatusSelect');
    const progressInput = document.getElementById('devProgressInput');
    const progressDesc = document.getElementById('devProgressDesc');
    const milestoneNotice = document.getElementById('devMilestoneNotice');

    const stageDefaults = {
        'Development Started': {
            pct: 20,
            desc: 'Development started: Core database schema, backend architecture, and project repository initialized.'
        },
        'Development in Progress': {
            pct: 40,
            desc: 'Development in progress: Frontend UI components, page templates, and primary business logic being actively coded.'
        },
        'Second Payment Pending': {
            pct: 50,
            desc: '50% Development Milestone Reached: Core application modules constructed and staged. Second payment (50% milestone) invoice dispatched.'
        },
        'Client Review / Revisions': {
            pct: 75,
            desc: 'Client review and revisions stage: Staging build ready for client walkthrough, testing, and feedback incorporation.'
        },
        'Development Completed': {
            pct: 100,
            desc: 'Core development completed 100%: All modules implemented, tested, and ready for final client sign-off and deployment.'
        }
    };

    function updateFromStatus(forceDesc = false) {
        if (!statusSelect || !progressInput) return;
        const val = statusSelect.value;
        const config = stageDefaults[val];

        if (config) {
            progressInput.value = config.pct;
            if (progressDesc) {
                const currentText = progressDesc.value.trim();
                const isMatchingAnyPreset = Object.values(stageDefaults).some(s => s.desc === currentText);
                if (!currentText || isMatchingAnyPreset || forceDesc) {
                    progressDesc.value = config.desc;
                }
            }
        }

        // Toggle 50% milestone notice banner
        if (milestoneNotice) {
            if (val === 'Second Payment Pending' || parseInt(progressInput.value) === 50) {
                milestoneNotice.classList.remove('d-none');
            } else {
                milestoneNotice.classList.add('d-none');
            }
        }
    }

    if (statusSelect) {
        statusSelect.addEventListener('change', function() {
            updateFromStatus(false);
        });
    }

    if (progressInput) {
        progressInput.addEventListener('input', function() {
            const num = parseInt(progressInput.value, 10);
            if (num === 50 && statusSelect.value !== 'Second Payment Pending') {
                statusSelect.value = 'Second Payment Pending';
                updateFromStatus(false);
            } else if (milestoneNotice) {
                if (statusSelect.value === 'Second Payment Pending' || num === 50) {
                    milestoneNotice.classList.remove('d-none');
                } else {
                    milestoneNotice.classList.add('d-none');
                }
            }
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/dev_footer.php'; ?>

