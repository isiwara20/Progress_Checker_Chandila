<?php
$adminTitle = 'Email Notification Center';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/email_config.php';

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

// Initialize table if not exists
EmailService::initTable($pdo);

// Handle Manual Ad-Hoc Email Dispatch
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_adhoc_email') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
    } else {
        $email   = trim($_POST['recipient_email'] ?? '');
        $name    = trim($_POST['recipient_name'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $jobId   = trim($_POST['job_id'] ?? 'CHW-CUSTOM');

        if (empty($email) || empty($subject) || empty($message)) {
            setFlash('error', 'Recipient email, subject, and message are required.');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Please enter a valid recipient email address.');
        } else {
            $res = EmailService::send(
                $pdo,
                $email,
                'Ad-Hoc Custom Notification',
                [
                    'custom_subject'  => $subject,
                    'custom_headline' => $subject,
                    'custom_message'  => $message,
                    'job_id'          => $jobId,
                    'customer_name'   => $name ?: 'Valued Customer'
                ],
                null,
                $jobId,
                true,
                $name
            );

            if ($res['success']) {
                setFlash('success', "Email dispatched successfully to {$email}!");
            } else {
                setFlash('error', "Failed to dispatch email: " . ($res['response'] ?? 'Mail error'));
            }
            header("Location: " . BASE_URL . "/admin/emails.php");
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Email Settings
$currentProvider = EmailService::getSetting($pdo, 'email_provider', 'mail');
$notificationsEnabled = EmailService::getSetting($pdo, 'email_notifications_enabled', '1');

// Fetch Notification History
$eventFilter  = trim($_GET['event'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');
$search       = trim($_GET['q'] ?? '');

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
    $where[] = "(job_id LIKE ? OR recipient_email LIKE ? OR recipient_name LIKE ? OR subject LIKE ?)";
    $like = '%' . $search . '%';
    $params = array_merge($params, [$like, $like, $like, $like]);
}
$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

$stmt = $pdo->prepare("
    SELECT * FROM email_notifications 
    {$whereSql} 
    ORDER BY created_at DESC 
    LIMIT 100
");
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Metrics
$totalSent    = (int)$pdo->query("SELECT COUNT(*) FROM email_notifications WHERE status = 'Sent'")->fetchColumn();
$totalFailed  = (int)$pdo->query("SELECT COUNT(*) FROM email_notifications WHERE status = 'Failed'")->fetchColumn();
$totalPending = (int)$pdo->query("SELECT COUNT(*) FROM email_notifications WHERE status = 'Pending'")->fetchColumn();
?>

<!-- Multi-Channel Navigation Tabs -->
<ul class="nav nav-pills mb-4">
    <li class="nav-item">
        <a class="nav-link text-secondary" href="<?php echo BASE_URL; ?>/admin/whatsapp.php">
            <i class="fa-brands fa-whatsapp text-success me-2"></i> WhatsApp Notifications
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active bg-primary text-white" href="<?php echo BASE_URL; ?>/admin/emails.php">
            <i class="fa-solid fa-envelope me-2"></i> Customer Email Notifications
        </a>
    </li>
</ul>

<!-- Header & Active Provider Banner -->
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-envelope text-primary me-2"></i> Email Notification Center</h3>
        <p class="text-secondary small mb-0">Audit outgoing email delivery logs, inspect rendered HTML previews, and dispatch messages.</p>
    </div>
    <div class="d-flex gap-2">
        <span class="badge bg-light border text-dark px-3 py-2 d-flex align-items-center gap-2">
            <span class="rounded-circle <?php echo ($notificationsEnabled === '1') ? 'bg-success' : 'bg-danger'; ?>" style="width: 8px; height: 8px;"></span>
            <span>Provider: <strong class="text-primary text-uppercase"><?php echo htmlspecialchars($currentProvider); ?></strong></span>
        </span>
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalSendAdhocEmail">
            <i class="fa-solid fa-paper-plane me-1"></i> Compose Custom Email
        </button>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #10b981;">
            <div class="stat-card-title text-success">Total Emails Dispatched</div>
            <div class="stat-card-value text-success"><?php echo $totalSent; ?></div>
            <div class="text-secondary small mt-1">Confirmed sent via <?php echo htmlspecialchars($currentProvider); ?></div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-envelope-circle-check"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #ef4444;">
            <div class="stat-card-title text-danger">Delivery Failures</div>
            <div class="stat-card-value text-danger"><?php echo $totalFailed; ?></div>
            <div class="text-secondary small mt-1">Eligible for manual 1-click re-dispatch</div>
            <div class="stat-icon-wrapper text-danger"><i class="fa-solid fa-triangle-exclamation"></i></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card" style="border-left: 3px solid #f59e0b;">
            <div class="stat-card-title text-warning">Pending / Queued</div>
            <div class="stat-card-value text-warning"><?php echo $totalPending; ?></div>
            <div class="text-secondary small mt-1">Awaiting transport queue</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-clock"></i></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="admin-card p-3 mb-4">
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET" class="row g-2 align-items-center">
        <div class="col-md-4">
            <input type="text" class="form-control form-control-chandila form-control-sm" name="q" placeholder="Job ID, email address, name..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-4">
            <select class="form-select form-select-chandila form-select-sm" name="event">
                <option value="">-- All Email Notification Events --</option>
                <option value="Project Request Submitted" <?php echo ($eventFilter === 'Project Request Submitted') ? 'selected' : ''; ?>>Project Request Submitted</option>
                <option value="Project Approved" <?php echo ($eventFilter === 'Project Approved') ? 'selected' : ''; ?>>Project Approved</option>
                <option value="Advance Payment Confirmed" <?php echo ($eventFilter === 'Advance Payment Confirmed') ? 'selected' : ''; ?>>Advance Payment Confirmed</option>
                <option value="Project Assigned" <?php echo ($eventFilter === 'Project Assigned') ? 'selected' : ''; ?>>Project Assigned</option>
                <option value="Project Progress Updated" <?php echo ($eventFilter === 'Project Progress Updated') ? 'selected' : ''; ?>>Project Progress Updated</option>
                <option value="Second Payment Reminder" <?php echo ($eventFilter === 'Second Payment Reminder') ? 'selected' : ''; ?>>Second Payment Reminder</option>
                <option value="Development Completed" <?php echo ($eventFilter === 'Development Completed') ? 'selected' : ''; ?>>Development Completed</option>
                <option value="Final Payment Reminder" <?php echo ($eventFilter === 'Final Payment Reminder') ? 'selected' : ''; ?>>Final Payment Reminder</option>
                <option value="Hosting Completed" <?php echo ($eventFilter === 'Hosting Completed') ? 'selected' : ''; ?>>Hosting Completed</option>
            </select>
        </div>
        <div class="col-md-2">
            <select class="form-select form-select-chandila form-select-sm" name="status">
                <option value="">-- All Statuses --</option>
                <option value="Sent" <?php echo ($statusFilter === 'Sent') ? 'selected' : ''; ?>>Sent</option>
                <option value="Failed" <?php echo ($statusFilter === 'Failed') ? 'selected' : ''; ?>>Failed</option>
                <option value="Pending" <?php echo ($statusFilter === 'Pending') ? 'selected' : ''; ?>>Pending</option>
            </select>
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Filter</button>
            <?php if (!empty($eventFilter) || !empty($statusFilter) || !empty($search)): ?>
                <a href="<?php echo BASE_URL; ?>/admin/emails.php" class="btn btn-light border btn-sm text-secondary" title="Reset Filters"><i class="fa-solid fa-rotate-left"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Email Logs Table -->
<div class="admin-card">
    <div class="admin-card-header d-flex justify-content-between align-items-center">
        <h5 class="admin-card-title mb-0"><i class="fa-solid fa-list-check me-2 text-primary"></i> Customer Email Dispatch Audit</h5>
        <span class="badge bg-secondary">Showing Latest <?php echo count($notifications); ?> Records</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th style="width: 140px;">Job ID</th>
                    <th>Recipient</th>
                    <th>Event & Subject</th>
                    <th style="width: 110px;">Status</th>
                    <th style="width: 160px;">Dispatched At</th>
                    <th style="width: 160px;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($notifications)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-secondary">
                            <i class="fa-regular fa-envelope-open fa-3x mb-3 text-muted opacity-50 d-block"></i>
                            <div class="fw-semibold text-dark">No email notifications found</div>
                            <small class="text-muted">Automated emails will appear here when project requests, approvals, payments, and progress milestones are updated.</small>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <tr>
                            <td class="font-monospace text-secondary small">#<?php echo $n['id']; ?></td>
                            <td>
                                <?php if (!empty($n['job_id'])): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 font-monospace">
                                        <?php echo htmlspecialchars($n['job_id']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($n['recipient_name'] ?: 'Customer'); ?></div>
                                <div class="small text-secondary font-monospace"><i class="fa-solid fa-envelope me-1 text-muted"></i><?php echo htmlspecialchars($n['recipient_email']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-primary small mb-1"><?php echo htmlspecialchars($n['event_type']); ?></div>
                                <div class="small text-dark text-truncate" style="max-width: 320px;" title="<?php echo htmlspecialchars($n['subject']); ?>">
                                    <?php echo htmlspecialchars($n['subject']); ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($n['status'] === 'Sent'): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i> Sent</span>
                                <?php elseif ($n['status'] === 'Failed'): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle" title="<?php echo htmlspecialchars($n['mail_response'] ?? ''); ?>"><i class="fa-solid fa-xmark me-1"></i> Failed</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle"><i class="fa-solid fa-clock me-1"></i> Pending</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary">
                                <i class="fa-regular fa-clock me-1 text-muted"></i><?php echo formatDateTime($n['sent_at'] ?: $n['created_at']); ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-preview-email" data-id="<?php echo $n['id']; ?>" title="Preview HTML Email Content">
                                        <i class="fa-solid fa-eye me-1"></i> Preview
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-resend-email" data-id="<?php echo $n['id']; ?>" title="Resend Email">
                                        <i class="fa-solid fa-rotate-right"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Compose Custom Ad-Hoc Email -->
<div class="modal fade" id="modalSendAdhocEmail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom">
                <h5 class="modal-title text-dark fw-bold"><i class="fa-solid fa-envelope text-primary me-2"></i> Compose Custom Customer Email</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="send_adhoc_email">

                <div class="modal-body p-4">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Recipient Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-chandila" name="recipient_email" placeholder="customer@domain.com" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-dark small fw-semibold">Customer Name</label>
                            <input type="text" class="form-control form-control-chandila" name="recipient_name" placeholder="e.g. Kasun Perera">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Associated Job ID</label>
                        <input type="text" class="form-control form-control-chandila" name="job_id" placeholder="e.g. CHW-2026-0001">
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Email Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-chandila" name="subject" placeholder="Important update regarding your project" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-dark small fw-semibold">Email Message Body <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-chandila" name="message" rows="5" placeholder="Enter your custom message here. It will be wrapped inside Chandila's branded HTML layout." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-paper-plane me-1"></i> Send Customer Email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Preview Rendered HTML Email -->
<div class="modal fade" id="modalPreviewEmail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-bottom">
                <div>
                    <h5 class="modal-title text-dark fw-bold mb-1" id="previewSubject">Email Preview</h5>
                    <div class="text-muted small" id="previewMeta">Loading preview...</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" style="background: #f1f5f9; min-height: 480px;">
                <iframe id="previewFrame" style="width: 100%; height: 540px; border: none; border-radius: 0 0 16px 16px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Resend Email AJAX
    document.querySelectorAll('.btn-resend-email').forEach(btn => {
        btn.addEventListener('click', function () {
            const notifId = this.getAttribute('data-id');
            if (!confirm('Are you sure you want to resend this customer email notification?')) return;

            const originalHtml = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
            this.disabled = true;

            const formData = new FormData();
            formData.append('action', 'resend');
            formData.append('notification_id', notifId);

            fetch('<?php echo BASE_URL; ?>/api/email.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showChandilaToast('success', 'Email notification dispatched successfully!', 'Email Dispatched');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showChandilaToast('error', data.message || 'Failed to dispatch email.', 'Delivery Failed');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                showChandilaToast('error', 'Network error during dispatch.', 'Connection Error');
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });

    // 2. Preview HTML Email AJAX
    const previewModalEl = document.getElementById('modalPreviewEmail');
    const previewModal = new bootstrap.Modal(previewModalEl);
    const previewSubject = document.getElementById('previewSubject');
    const previewMeta = document.getElementById('previewMeta');
    const previewFrame = document.getElementById('previewFrame');

    document.querySelectorAll('.btn-preview-email').forEach(btn => {
        btn.addEventListener('click', function () {
            const notifId = this.getAttribute('data-id');
            previewSubject.innerText = 'Loading Email Preview...';
            previewMeta.innerText = 'Fetching rendered template...';
            previewFrame.srcdoc = '<div style="padding: 40px; text-align: center; color: #64748b; font-family: sans-serif;">Loading branded HTML layout...</div>';
            previewModal.show();

            fetch('<?php echo BASE_URL; ?>/api/email.php?action=get_html&id=' + notifId)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    previewSubject.innerText = data.subject;
                    previewMeta.innerHTML = 'To: <strong>' + data.name + '</strong> &lt;' + data.recipient + '&gt; • Sent: ' + data.sent_at;
                    previewFrame.srcdoc = data.body_html;
                } else {
                    previewSubject.innerText = 'Error';
                    previewMeta.innerText = data.message || 'Could not load preview';
                    previewFrame.srcdoc = '<div style="padding: 40px; color: #ef4444; text-align: center;">Failed to load email preview.</div>';
                }
            })
            .catch(err => {
                console.error(err);
                previewSubject.innerText = 'Connection Error';
                previewMeta.innerText = 'Failed to communicate with API';
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

