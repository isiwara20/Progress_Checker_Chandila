<?php
$adminTitle = 'Customer Care Working Hours & Attendance';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Safe redirect safeguard
if (!function_exists('redirect')) {
    function redirect(string $url): void {
        if (!headers_sent()) {
            header("Location: {$url}");
            exit;
        }
        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        echo "<noscript><meta http-equiv=\"refresh\" content=\"0;url=" . htmlspecialchars($url) . "\"></noscript>";
        exit;
    }
}

// Strict Admin Gate
requireAdmin();

$pdo = getDB();
$currentUser = getCurrentUser();

$shiftOptions = [
    'Standard Full Day (08:30 AM – 05:30 PM)',
    'Morning Shift (08:00 AM – 01:00 PM)',
    'Evening Shift (01:00 PM – 07:00 PM)',
    'Night Shift / On-Call Support',
    'Custom / Overtime Duty'
];

// Fetch all Customer Care staff for filter dropdown & logging
$careStaff = $pdo->query("SELECT id, username, full_name, email, status FROM users WHERE role = 'customer_care' ORDER BY full_name ASC")->fetchAll();

// Handle Admin Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired.');
        redirect(BASE_URL . '/admin/care_hours.php');
    }

    $action = $_POST['action'];

    // 1. Approve Shift
    if ($action === 'approve_shift') {
        $id = (int)($_POST['session_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE care_working_hours SET status = 'Approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Shift hours approved successfully.');
        redirect(BASE_URL . '/admin/care_hours.php');
    }

    // 2. Admin Log Manual Shift
    elseif ($action === 'admin_log_hours') {
        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $workDate     = trim($_POST['work_date'] ?? date('Y-m-d'));
        $shiftType    = trim($_POST['shift_type'] ?? 'Standard Full Day (08:30 AM – 05:30 PM)');
        $startTimeRaw = trim($_POST['start_time'] ?? '');
        $endTimeRaw   = trim($_POST['end_time'] ?? '');
        $tasksSummary = trim($_POST['tasks_summary'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        if (!$targetUserId || empty($workDate) || empty($startTimeRaw) || empty($endTimeRaw)) {
            setFlash('error', 'Please choose an agent and provide date, start time, and end time.');
        } else {
            $fullStart = $workDate . ' ' . $startTimeRaw . ':00';
            $fullEnd   = $workDate . ' ' . $endTimeRaw . ':00';
            if (strtotime($fullEnd) <= strtotime($fullStart)) {
                $fullEnd = date('Y-m-d H:i:s', strtotime($fullEnd . ' +1 day'));
            }

            $diffSec = max(0, strtotime($fullEnd) - strtotime($fullStart));
            $hours   = round($diffSec / 3600, 2);

            $stmt = $pdo->prepare("
                INSERT INTO care_working_hours (user_id, work_date, shift_type, start_time, end_time, total_hours, status, tasks_summary, notes, created_at)
                VALUES (?, ?, ?, ?, ?, ?, 'Approved', ?, ?, NOW())
            ");
            $stmt->execute([$targetUserId, $workDate, $shiftType, $fullStart, $fullEnd, $hours, $tasksSummary, $notes]);

            setFlash('success', 'Working hours recorded and approved for staff.');
        }
        redirect(BASE_URL . '/admin/care_hours.php');
    }

    // 3. Admin Update Shift Details
    elseif ($action === 'admin_update_shift') {
        $sessionId    = (int)($_POST['session_id'] ?? 0);
        $shiftType    = trim($_POST['shift_type'] ?? '');
        $totalHours   = (float)($_POST['total_hours'] ?? 0);
        $status       = in_array($_POST['status'] ?? '', ['In Progress', 'Completed', 'Approved'], true) ? $_POST['status'] : 'Completed';
        $tasksSummary = trim($_POST['tasks_summary'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE care_working_hours
            SET shift_type = ?, total_hours = ?, status = ?, tasks_summary = ?, notes = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$shiftType, $totalHours, $status, $tasksSummary, $notes, $sessionId]);

        setFlash('success', 'Shift details updated successfully.');
        redirect(BASE_URL . '/admin/care_hours.php');
    }

    // 4. Admin Delete Shift
    elseif ($action === 'delete_shift') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $pdo->prepare("DELETE FROM care_working_hours WHERE id = ?")->execute([$sessionId]);
        setFlash('info', 'Shift record deleted.');
        redirect(BASE_URL . '/admin/care_hours.php');
    }
}

// Filters
$filterUser   = (int)($_GET['user_id'] ?? 0);
$filterMonth  = trim($_GET['month'] ?? date('Y-m'));
$filterStatus = trim($_GET['status'] ?? '');

// Build Query
$where = ["1=1"];
$params = [];

if ($filterUser > 0) {
    $where[] = "h.user_id = ?";
    $params[] = $filterUser;
}
if (!empty($filterMonth)) {
    $where[] = "DATE_FORMAT(h.work_date, '%Y-%m') = ?";
    $params[] = $filterMonth;
}
if (!empty($filterStatus)) {
    $where[] = "h.status = ?";
    $params[] = $filterStatus;
}

$whereSql = implode(' AND ', $where);

// Fetch Working Hours
$stmtList = $pdo->prepare("
    SELECT h.*, u.full_name AS agent_name, u.email AS agent_email, u.phone AS agent_phone
    FROM care_working_hours h
    JOIN users u ON h.user_id = u.id
    WHERE {$whereSql}
    ORDER BY h.work_date DESC, h.start_time DESC
    LIMIT 200
");
$stmtList->execute($params);
$shifts = $stmtList->fetchAll();

// Metrics
// Currently On Duty Agents
$activeOnDuty = $pdo->query("
    SELECT h.*, u.full_name, u.phone 
    FROM care_working_hours h
    JOIN users u ON h.user_id = u.id
    WHERE h.status = 'In Progress'
    ORDER BY h.start_time DESC
")->fetchAll();

// Total Team Hours This Month
$teamHoursMonth = (float)$pdo->query("
    SELECT COALESCE(SUM(total_hours), 0) 
    FROM care_working_hours 
    WHERE status IN ('Completed', 'Approved') 
      AND MONTH(work_date) = MONTH(CURDATE()) 
      AND YEAR(work_date) = YEAR(CURDATE())
")->fetchColumn();

// Total Shifts Completed This Month
$totalShiftsMonth = (int)$pdo->query("
    SELECT COUNT(*) 
    FROM care_working_hours 
    WHERE status IN ('Completed', 'Approved') 
      AND MONTH(work_date) = MONTH(CURDATE()) 
      AND YEAR(work_date) = YEAR(CURDATE())
")->fetchColumn();

require_once __DIR__ . '/../includes/admin_header.php';
?>

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/index.php" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/developers.php" class="text-decoration-none">Staff & Developers</a></li>
        <li class="breadcrumb-item active" aria-current="page">Care Working Hours</li>
    </ol>
</nav>

<!-- Page Header Banner -->
<div class="admin-card p-4 mb-4" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; border-radius: 16px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark px-3 py-1 fw-bold">
                    <i class="fa-solid fa-business-time me-1"></i> ATTENDANCE & TIMESHEET OVERSIGHT
                </span>
                <span class="text-light text-opacity-75 small">Customer Care Operations</span>
            </div>
            <h3 class="text-white fw-bold mb-1">Customer Care Working Hours & Time Periods</h3>
            <p class="text-light text-opacity-75 small mb-0">
                Monitor real-time on-duty status, review completed work hours, approve attendance records, and inspect daily task summaries.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-primary btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#adminAddHoursModal">
                <i class="fa-solid fa-plus me-1"></i> Log Hours for Staff
            </button>
            <a href="<?php echo BASE_URL; ?>/care/index.php" class="btn btn-outline-light btn-sm px-3 py-2">
                <i class="fa-solid fa-headset me-1"></i> Open Care Portal
            </a>
            <button type="button" class="btn btn-light btn-sm px-3 py-2" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print Timesheet
            </button>
        </div>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid <?php echo count($activeOnDuty) > 0 ? '#10b981' : '#64748b'; ?>;">
            <div class="stat-card-title text-success">Currently On Duty</div>
            <div class="stat-card-value text-success"><?php echo count($activeOnDuty); ?> <span class="fs-6 fw-normal text-muted">agents</span></div>
            <div class="text-muted small mt-1">
                <?php if (count($activeOnDuty) > 0): ?>
                    <?php echo htmlspecialchars(implode(', ', array_column($activeOnDuty, 'full_name'))); ?>
                <?php else: ?>
                    No agents active right now
                <?php endif; ?>
            </div>
            <div class="stat-icon-wrapper text-success"><i class="fa-solid fa-headset"></i></div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-card-title text-primary">Team Hours This Month</div>
            <div class="stat-card-value text-primary"><?php echo number_format($teamHoursMonth, 2); ?> <span class="fs-6 fw-normal text-muted">hrs</span></div>
            <div class="text-muted small mt-1"><?php echo date('F Y'); ?> aggregate</div>
            <div class="stat-icon-wrapper text-primary"><i class="fa-solid fa-business-time"></i></div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-card-title text-purple" style="color: #8b5cf6;">Completed Shifts</div>
            <div class="stat-card-value" style="color: #8b5cf6;"><?php echo $totalShiftsMonth; ?></div>
            <div class="text-muted small mt-1">This month across all care staff</div>
            <div class="stat-icon-wrapper" style="color: #8b5cf6;"><i class="fa-solid fa-calendar-check"></i></div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #f59e0b;">
            <div class="stat-card-title text-warning">Total Care Personnel</div>
            <div class="stat-card-value text-warning"><?php echo count($careStaff); ?></div>
            <div class="text-muted small mt-1">Registered customer care accounts</div>
            <div class="stat-icon-wrapper text-warning"><i class="fa-solid fa-users"></i></div>
        </div>
    </div>
</div>

<!-- Active Duty Alert Banner (if agents are working) -->
<?php if (!empty($activeOnDuty)): ?>
    <div class="alert alert-success border-success bg-success bg-opacity-10 d-flex flex-column flex-md-row justify-content-between align-items-md-center p-3 mb-4 rounded-3">
        <div class="d-flex align-items-center gap-3">
            <span class="fs-3 text-success"><i class="fa-solid fa-circle-dot fa-beat-fade"></i></span>
            <div>
                <h6 class="mb-0 fw-bold text-success">Live Customer Care Desk Active</h6>
                <div class="small text-dark">
                    <?php foreach ($activeOnDuty as $onD): ?>
                        <span class="badge bg-success text-white me-2">
                            <?php echo htmlspecialchars($onD['full_name']); ?> • Since <?php echo date('h:i A', strtotime($onD['start_time'])); ?> (<?php echo htmlspecialchars($onD['shift_type']); ?>)
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Filter & Data Table -->
<div class="admin-card mb-4" style="border-radius: 16px;">
    <!-- Filters Header -->
    <div class="p-3 px-4 border-bottom bg-light bg-opacity-50" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
        <form method="GET" action="<?php echo BASE_URL; ?>/admin/care_hours.php" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-semibold">Filter by Care Agent</label>
                <select name="user_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="0">All Customer Care Staff</option>
                    <?php foreach ($careStaff as $staff): ?>
                        <option value="<?php echo $staff['id']; ?>" <?php echo ($filterUser === (int)$staff['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($staff['full_name']); ?> (<?php echo htmlspecialchars($staff['username']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-semibold">Filter by Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filterMonth); ?>" onchange="this.form.submit()">
            </div>

            <div class="col-md-3">
                <label class="form-label small text-muted mb-1 fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="In Progress" <?php echo ($filterStatus === 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo ($filterStatus === 'Completed') ? 'selected' : ''; ?>>Completed</option>
                    <option value="Approved" <?php echo ($filterStatus === 'Approved') ? 'selected' : ''; ?>>Approved</option>
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end gap-2 pt-md-3">
                <button type="submit" class="btn btn-sm btn-primary px-3">Filter</button>
                <a href="<?php echo BASE_URL; ?>/admin/care_hours.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Shifts Table -->
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Care Agent</th>
                    <th>Date & Shift</th>
                    <th>Time Period (Start – End)</th>
                    <th class="text-center">Total Hours</th>
                    <th>Work Tasks & Handover Summary</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($shifts)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clock-rotate-left fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold">No shift records found for this criteria</h6>
                            <p class="small text-muted mb-0">Try clearing or widening your date and agent filters.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $filteredTotalHours = 0;
                    foreach ($shifts as $s): 
                        $filteredTotalHours += (float)($s['total_hours'] ?? 0);
                        $isInProg = ($s['status'] === 'In Progress');
                    ?>
                        <tr class="<?php echo $isInProg ? 'table-warning bg-opacity-25' : ''; ?>">
                            <td class="ps-4">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 0.85rem;">
                                        <?php echo strtoupper(substr($s['agent_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark small"><?php echo htmlspecialchars($s['agent_name']); ?></div>
                                        <div class="text-muted" style="font-size: 0.75rem;"><?php echo htmlspecialchars($s['agent_email']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark small"><?php echo date('d M Y', strtotime($s['work_date'])); ?></div>
                                <span class="badge bg-light text-dark border small" style="font-size: 0.7rem;">
                                    <?php echo htmlspecialchars($s['shift_type']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-monospace small">
                                    <span class="text-success fw-bold"><?php echo date('h:i A', strtotime($s['start_time'])); ?></span>
                                    <span class="text-muted mx-1">→</span>
                                    <?php if (!empty($s['end_time'])): ?>
                                        <span class="text-danger fw-bold"><?php echo date('h:i A', strtotime($s['end_time'])); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-25 text-success">Active Now</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($s['total_hours'])): ?>
                                    <span class="badge bg-primary fs-6 px-2 py-1">
                                        <?php echo number_format($s['total_hours'], 2); ?> <span class="small fw-normal">hrs</span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small font-monospace">--:--</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 300px;">
                                <?php if (!empty($s['tasks_summary'])): ?>
                                    <div class="small text-dark fw-semibold text-truncate" title="<?php echo htmlspecialchars($s['tasks_summary']); ?>">
                                        <i class="fa-solid fa-list-check me-1 text-primary"></i> <?php echo htmlspecialchars($s['tasks_summary']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($s['notes'])): ?>
                                    <div class="text-muted small text-truncate" title="<?php echo htmlspecialchars($s['notes']); ?>">
                                        <i class="fa-regular fa-comment-dots me-1 text-secondary"></i> <?php echo htmlspecialchars($s['notes']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($s['tasks_summary']) && empty($s['notes'])): ?>
                                    <span class="text-muted small italic">No notes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($s['status'] === 'In Progress'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> In Progress</span>
                                <?php elseif ($s['status'] === 'Approved'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check-double me-1"></i> Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><i class="fa-solid fa-check me-1"></i> Completed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-1">
                                    <?php if ($s['status'] === 'Completed'): ?>
                                        <form action="<?php echo BASE_URL; ?>/admin/care_hours.php" method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="approve_shift">
                                            <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Approve Timesheet Hours">
                                                <i class="fa-solid fa-check"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2 text-secondary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#adminEditModal"
                                            data-id="<?php echo $s['id']; ?>"
                                            data-agent="<?php echo htmlspecialchars($s['agent_name']); ?>"
                                            data-shift="<?php echo htmlspecialchars($s['shift_type']); ?>"
                                            data-hours="<?php echo htmlspecialchars($s['total_hours'] ?? '0.00'); ?>"
                                            data-status="<?php echo htmlspecialchars($s['status']); ?>"
                                            data-tasks="<?php echo htmlspecialchars($s['tasks_summary'] ?? ''); ?>"
                                            data-notes="<?php echo htmlspecialchars($s['notes'] ?? ''); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>

                                    <form action="<?php echo BASE_URL; ?>/admin/care_hours.php" method="POST" class="d-inline" onsubmit="return confirm('Delete this shift record?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                        <input type="hidden" name="action" value="delete_shift">
                                        <input type="hidden" name="session_id" value="<?php echo $s['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-light border text-danger py-1 px-2" title="Delete record">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($shifts)): ?>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="ps-4 text-end">Filtered Total Working Hours:</th>
                        <th class="text-center">
                            <span class="badge bg-dark fs-6 px-3 py-1">
                                <?php echo number_format($filteredTotalHours, 2); ?> hrs
                            </span>
                        </th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADMIN LOG HOURS FOR STAFF -->
<!-- ========================================== -->
<div class="modal fade" id="adminAddHoursModal" tabindex="-1" aria-labelledby="adminAddHoursModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="<?php echo BASE_URL; ?>/admin/care_hours.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="admin_log_hours">

                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="adminAddHoursModalLabel">
                        <i class="fa-solid fa-calendar-plus me-2"></i> Log Working Hours for Customer Care Staff
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Customer Care Agent *</label>
                        <select name="target_user_id" class="form-select" required>
                            <option value="">Select Care Agent...</option>
                            <?php foreach ($careStaff as $staff): ?>
                                <option value="<?php echo $staff['id']; ?>"><?php echo htmlspecialchars($staff['full_name']); ?> (<?php echo htmlspecialchars($staff['username']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Shift Date *</label>
                            <input type="date" name="work_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Shift Type *</label>
                            <select name="shift_type" class="form-select" required>
                                <?php foreach ($shiftOptions as $opt): ?>
                                    <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" value="08:30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">End Time *</label>
                            <input type="time" name="end_time" class="form-control" value="17:30" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Tasks Handled / Summary *</label>
                        <textarea name="tasks_summary" class="form-control" rows="3" placeholder="Order intake, client follow-ups, payment notifications..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Admin Approval Notes (Optional)</label>
                        <input type="text" name="notes" class="form-control" placeholder="Approved by management">
                    </div>
                </div>

                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-save me-1"></i> Save Shift Record
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: ADMIN EDIT SHIFT -->
<!-- ========================================== -->
<div class="modal fade" id="adminEditModal" tabindex="-1" aria-labelledby="adminEditModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="<?php echo BASE_URL; ?>/admin/care_hours.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="admin_update_shift">
                <input type="hidden" name="session_id" id="modalEditSessionId" value="">

                <div class="modal-header bg-dark text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="adminEditModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Edit Care Shift Record
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Staff Agent</label>
                        <input type="text" id="modalEditAgent" class="form-control" readonly disabled>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Total Hours</label>
                            <input type="number" step="0.01" min="0" name="total_hours" id="modalEditHours" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Status</label>
                            <select name="status" id="modalEditStatus" class="form-select" required>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Approved">Approved</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Shift Type</label>
                        <select name="shift_type" id="modalEditShift" class="form-select" required>
                            <?php foreach ($shiftOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Tasks Summary</label>
                        <textarea name="tasks_summary" id="modalEditTasks" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Notes</label>
                        <textarea name="notes" id="modalEditNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-save me-1"></i> Update Shift
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const editModal = document.getElementById('adminEditModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;

            document.getElementById('modalEditSessionId').value = button.getAttribute('data-id') || '';
            document.getElementById('modalEditAgent').value = button.getAttribute('data-agent') || '';
            document.getElementById('modalEditShift').value = button.getAttribute('data-shift') || '';
            document.getElementById('modalEditHours').value = button.getAttribute('data-hours') || '0.00';
            document.getElementById('modalEditStatus').value = button.getAttribute('data-status') || 'Completed';
            document.getElementById('modalEditTasks').value = button.getAttribute('data-tasks') || '';
            document.getElementById('modalEditNotes').value = button.getAttribute('data-notes') || '';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

