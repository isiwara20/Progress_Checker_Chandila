<?php
$adminTitle = 'WhatsApp Notification Center';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/whatsapp_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Handle Manual Ad-Hoc Test Send
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_adhoc_whatsapp') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $phone   = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $jobId   = trim($_POST['job_id'] ?? 'CHW-CUSTOM');

        if (empty($phone) || empty($message)) {
            setFlash('error', 'Phone number and message text are required.');
        } else {
            $res = WhatsAppService::send(
                $pdo,
                $phone,
                'Ad-Hoc Custom Notification',
                ['custom_message' => $message, 'job_id' => $jobId],
                null,
                $jobId,
                true
            );

            if ($res['success']) {
                setFlash('success', "WhatsApp message dispatched successfully to {$phone}!");
            } else {
                setFlash('error', "Failed to dispatch message: " . ($res['message'] ?? 'API error'));
            }
            header("Location: " . BASE_URL . "/admin/whatsapp.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch WhatsApp Provider setting
$currentProvider = WhatsAppService::getSetting($pdo, 'whatsapp_provider', 'simulator');

// Fetch Notification History
$eventFilter = trim($_GET['event'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if (!empty($eventFilter)) {
    $where[] = "event_type = ?";
    $params[] = $eventFilter;
}
if (!empty($statusFilter)) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if (!empty($search)) {
    $where[] = "(job_id LIKE ? OR recipient_phone LIKE ? OR message_text LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like]);
}
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT * FROM whatsapp_notifications 
    {$whereSql} 
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Metrics
$totalSent = (int)$pdo->query("SELECT COUNT(*) FROM whatsapp_notifications WHERE status = 'Sent'")->fetchColumn();
$totalFailed = (int)$pdo->query("SELECT COUNT(*) FROM whatsapp_notifications WHERE status = 'Failed'")->fetchColumn();
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM whatsapp_notifications WHERE status = 'Pending'")->fetchColumn();
?>

<!-- Multi-Channel Navigation Tabs -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link active bg-success text-white" href="<?php echo BASE_URL; ?>/admin/whatsapp.php">
            <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp Notifications
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-secondary" href="<?php echo BASE_URL; ?>/admin/emails.php">
            <i class="fa-solid fa-envelope me-2"></i> Customer Email Notifications
        </a>
    </li>
</ul>

<!-- Header & Active Provider Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-brands fa-whatsapp text-success me-2"></i> WhatsApp Messaging Center</h3>
        <p class="text-muted small mb-0">Audit delivery logs, monitor automated event triggers, and manually re-dispatch notifications.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-light border text-dark px-3 py-2 d-flex align-items-center gap-2">
            <span class="rounded-circle bg-success" style="width: 8px; height: 8px;"></span>
            <span>Provider: <strong class="text-primary text-uppercase"><?php echo htmlspecialchars($currentProvider); ?></strong></span>
        </span>
        <button type="button" class="btn btn-electric btn-sm" data-bs-toggle="modal" data-bs-target="#modalSendAdhoc">
            <i class="fa-solid fa-paper-plane me-1"></i> Send Ad-Hoc Message
        </button>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Total Messages Dispatched</div>
            <div class="stat-card-value text-success"><?php echo $totalSent; ?></div>
            <div class="text-secondary small mt-1">Confirmed delivered to customers</div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-check-double"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #ef4444;">
            <div class="stat-card-title text-danger">Failed Dispatches</div>
            <div class="stat-card-value text-danger"><?php echo $totalFailed; ?></div>
            <div class="text-secondary small mt-1">Eligible for manual 1-click resend</div>
            <div class="stat-icon-wrapper text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Queued / Pending</div>
            <div class="stat-card-value text-warning"><?php echo $totalPending; ?></div>
            <div class="text-secondary small mt-1">Pending background dispatch</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-clock"></i></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" class="form-control form-control-chandila form-control-sm" name="q" placeholder="Job ID, phone number or text..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-chandila form-select-sm" name="event">
                <option value="">-- All WhatsApp Event Types --</option>
                <option value="Project Request Submitted" <?php echo ($eventFilter === 'Project Request Submitted') ? 'selected' : ''; ?>>1. Project Request Submitted</option>
                <option value="Project Approved" <?php echo ($eventFilter === 'Project Approved') ? 'selected' : ''; ?>>2. Project Approved</option>
                <option value="Advance Payment Confirmed" <?php echo ($eventFilter === 'Advance Payment Confirmed') ? 'selected' : ''; ?>>3. Advance Payment Confirmed</option>
                <option value="Project Assigned" <?php echo ($eventFilter === 'Project Assigned') ? 'selected' : ''; ?>>4. Project Assigned</option>
                <option value="Project Progress Updated" <?php echo ($eventFilter === 'Project Progress Updated') ? 'selected' : ''; ?>>5. Project Progress Updated</option>
                <option value="Second Payment Reminder" <?php echo ($eventFilter === 'Second Payment Reminder') ? 'selected' : ''; ?>>6. Second Payment Reminder</option>
                <option value="Development Completed" <?php echo ($eventFilter === 'Development Completed') ? 'selected' : ''; ?>>7. Development Completed</option>
                <option value="Final Payment Reminder" <?php echo ($eventFilter === 'Final Payment Reminder') ? 'selected' : ''; ?>>8. Final Payment Reminder</option>
                <option value="Hosting Completed" <?php echo ($eventFilter === 'Hosting Completed') ? 'selected' : ''; ?>>9. Hosting Completed</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-chandila form-select-sm" name="status">
                <option value="">-- Status --</option>
                <option value="Sent" <?php echo ($statusFilter === 'Sent') ? 'selected' : ''; ?>>Sent</option>
                <option value="Failed" <?php echo ($statusFilter === 'Failed') ? 'selected' : ''; ?>>Failed</option>
                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($eventFilter) || !empty($statusFilter) || !empty($search)): ?>
                <a href="<?php echo BASE_URL; ?>/admin/whatsapp.php" class="btn btn-sm btn-outline-secondary"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Notifications Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Job ID</th>
                    <th>Recipient</th>
                    <th>Event Trigger</th>
                    <th>Message Content Preview</th>
                    <th>Status</th>
                    <th>Sent Timestamp</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-5">
                            <i class="fa-brands fa-whatsapp fa-3x text-secondary mb-2 d-block"></i>
                            No WhatsApp notifications recorded matching the criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <tr id="notif_row_<?php echo $n['id']; ?>">
                            <td>
                                <span class="fw-bold font-monospace text-primary"><?php echo htmlspecialchars($n['job_id'] ?? '—'); ?></span>
                            </td>
                            <td>
                                <span class="text-dark fw-semibold small"><?php echo htmlspecialchars($n['recipient_phone']); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($n['event_type']); ?></span>
                            </td>
                            <td>
                                <div class="text-truncate text-secondary small" style="max-width: 320px;" title="<?php echo htmlspecialchars($n['message_text']); ?>">
                                    <?php echo htmlspecialchars($n['message_text']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($n['status'] === 'Sent'): ?>
                                    <span class="badge bg-success notif-status-badge"><i class="fa-solid fa-check me-1"></i> Sent</span>
                                <?php elseif ($n['status'] === 'Failed'): ?>
                                    <span class="badge bg-danger notif-status-badge"><i class="fa-solid fa-triangle-exclamation me-1"></i> Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark notif-status-badge"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary"><?php echo formatDateTime($n['created_at']); ?></td>
                            <td class="text-end text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#modalMsg<?php echo $n['id']; ?>" title="Preview Full Message">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success btn-resend-whatsapp" data-notif-id="<?php echo $n['id']; ?>" title="Resend Notification via AJAX">
                                    <i class="fa-solid fa-rotate-right"></i> Resend
                                </button>
                            </td>
                        </tr>

                        <!-- Modal: Message Preview -->
                        <div class="modal fade" id="modalMsg<?php echo $n['id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content chandila-modal">
                                    <div class="modal-header">
                                        <h5 class="modal-title text-dark fw-bold"><i class="fa-brands fa-whatsapp text-success me-2"></i> WhatsApp Message Details</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <span class="text-secondary small">Recipient:</span>
                                            <div class="text-dark fw-bold"><?php echo htmlspecialchars($n['recipient_phone']); ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <span class="text-secondary small">Event Type:</span>
                                            <div><span class="badge bg-primary"><?php echo htmlspecialchars($n['event_type']); ?></span></div>
                                        </div>
                                        <div class="mb-3">
                                            <span class="text-secondary small">Message Payload:</span>
                                            <div class="p-3 rounded-3 mt-1" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; font-family: monospace; white-space: pre-wrap;">
                                                <?php echo htmlspecialchars($n['message_text']); ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($n['api_response'])): ?>
                                            <div>
                                                <span class="text-secondary small">API Response Payload:</span>
                                                <pre class="p-2 rounded bg-light border text-muted small mt-1" style="max-height: 120px; overflow-y: auto;"><?php echo htmlspecialchars($n['api_response']); ?></pre>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-success btn-resend-whatsapp" data-notif-id="<?php echo $n['id']; ?>" data-bs-dismiss="modal">
                                            <i class="fa-solid fa-rotate-right me-1"></i> Resend Now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Send Ad-Hoc Message -->
<div class="modal fade" id="modalSendAdhoc" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content chandila-modal">
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="send_adhoc_whatsapp">

                <div class="modal-header">
                    <h5 class="modal-title text-dark fw-bold"><i class="fa-brands fa-whatsapp text-success me-2"></i> Send Custom WhatsApp Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Recipient Phone Number (with Country Code) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="phone" placeholder="e.g. +9476843340 or 0768433340" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Associated Job ID (Optional)</label>
                        <input type="text" class="form-control form-control-chandila" name="job_id" placeholder="e.g. CHW-2026-0001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Message Text <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-chandila" name="message" rows="4" placeholder="Type your WhatsApp notification..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-electric"><i class="fa-solid fa-paper-plane me-1"></i> Dispatch Message</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

