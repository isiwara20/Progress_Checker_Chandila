<?php
$adminTitle = 'Project Progress Overview';
require_once __DIR__ . '/../includes/admin_header.php';

// Fetch all active/ongoing projects
$projects = $pdo->query("
    SELECT p.*, 
           c.full_name AS customer_name, 
           c.company_name,
           u.full_name AS dev_name,
           (SELECT description FROM project_progress WHERE project_id = p.id ORDER BY created_at DESC LIMIT 1) AS latest_progress_desc,
           (SELECT created_at FROM project_progress WHERE project_id = p.id ORDER BY created_at DESC LIMIT 1) AS latest_progress_time
    FROM projects p
    JOIN customers c ON p.customer_id = c.id
    LEFT JOIN users u ON p.assigned_developer_id = u.id
    WHERE p.status IN ('Development Started', 'Development in Progress', 'Client Review / Revisions', 'Hosting & Deployment')
    ORDER BY p.expected_completion_date ASC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-bars-progress text-primary me-2"></i> Active Sprint Progress Radar</h3>
        <p class="text-secondary small mb-0">Live sprint completion status, milestones, and delivery countdowns across all assigned development.</p>
    </div>
</div>

<div class="row g-4">
    <?php if (empty($projects)): ?>
        <div class="col-12">
            <div class="admin-card p-5 text-center text-secondary">
                <i class="fa-solid fa-laptop-code fa-3x mb-3 d-block text-secondary"></i>
                <h4 class="text-dark">No Active Development Sprints</h4>
                <p class="small mb-0">Projects in development will appear here with live progress percentages and deadline tracking.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($projects as $p): 
            $today = new DateTime('today');
            $target = !empty($p['expected_completion_date']) ? new DateTime($p['expected_completion_date']) : null;
            $diffDays = $target ? $today->diff($target) : null;
            $isOverdue = $target && ($today > $target);
        ?>
            <div class="col-lg-6">
                <div class="admin-card p-4 h-100 d-flex flex-column justify-content-between">
                    <div>
                        <!-- Top Header -->
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $p['id']; ?>" class="fw-bold font-monospace text-primary fs-6">
                                    <?php echo htmlspecialchars($p['job_id']); ?>
                                </a>
                                <h4 class="text-dark fw-bold mt-1 mb-1"><?php echo htmlspecialchars($p['project_title']); ?></h4>
                                <div class="text-secondary small">Customer: <strong><?php echo htmlspecialchars($p['customer_name']); ?></strong></div>
                            </div>
                            <?php echo getStatusBadge($p['status']); ?>
                        </div>

                        <!-- Developer & Deadline Info -->
                        <div class="row g-2 p-2 rounded-3 my-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <div class="col-6">
                                <span class="text-secondary small d-block">Assigned Developer:</span>
                                <span class="fw-semibold text-dark small">
                                    <i class="fa-solid fa-user-gear text-primary me-1"></i> <?php echo htmlspecialchars($p['dev_name'] ?? 'Unassigned'); ?>
                                </span>
                            </div>
                            <div class="col-6 text-end">
                                <span class="text-secondary small d-block">Target Completion:</span>
                                <?php if ($isOverdue): ?>
                                    <span class="badge bg-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Overdue (<?php echo formatDateOnly($p['expected_completion_date']); ?>)</span>
                                <?php else: ?>
                                    <span class="text-primary fw-semibold small">
                                        <i class="fa-regular fa-clock me-1"></i> <?php echo formatDateOnly($p['expected_completion_date']); ?> 
                                        <?php if ($diffDays): ?>(in <?php echo $diffDays->days; ?> days)<?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Progress Bar -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-dark small fw-bold">Sprint Completion</span>
                                <span class="text-primary fw-bold"><?php echo (int)$p['progress_percentage']; ?>%</span>
                            </div>
                            <div class="progress" style="height: 10px; background-color: #e2e8f0; border-radius: 20px;">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     style="width: <?php echo (int)$p['progress_percentage']; ?>%; background: linear-gradient(90deg, #d97706, #2563eb);" 
                                     role="progressbar"></div>
                            </div>
                        </div>

                        <!-- Latest Progress Note -->
                        <?php if (!empty($p['latest_progress_desc'])): ?>
                            <div class="p-2 rounded bg-light border small text-dark mb-3">
                                <div class="d-flex justify-content-between text-secondary mb-1" style="font-size: 0.72rem;">
                                    <span>Latest Developer Log:</span>
                                    <span><?php echo formatDateTime($p['latest_progress_time'], 'M d, h:i A'); ?></span>
                                </div>
                                <?php echo htmlspecialchars($p['latest_progress_desc']); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Bottom Link -->
                    <div class="pt-3 border-top d-flex justify-content-between align-items-center">
                        <span class="text-secondary small">Stage: <strong><?php echo getStageStepNumber($p['status']); ?> of 12</strong></span>
                        <a href="<?php echo BASE_URL; ?>/admin/project_view.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary">
                            Open Project Command Center <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

