<?php
$careTitle = 'Working Hours & Duty Desk';
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

// Strict Customer Care Gate (allows customer_care & admin)
requireCustomerCare();

$pdo = getDB();
$currentUser = getCurrentUser();
$userId = (int)$currentUser['id'];

// Default shifts
$shiftOptions = [
    'Standard Full Day (08:30 AM – 05:30 PM)',
    'Morning Shift (08:00 AM – 01:00 PM)',
    'Evening Shift (01:00 PM – 07:00 PM)',
    'Night Shift / On-Call Support',
    'Custom / Overtime Duty'
];

// Handle Actions (Clock In, Clock Out, Log Manual Hours, Update Notes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Security token expired. Please try again.');
        redirect(BASE_URL . '/care/hours.php');
    }

    $action = $_POST['action'];

    // 1. CLOCK IN ACTION
    if ($action === 'clock_in') {
        // Check for active session
        $chkStmt = $pdo->prepare("SELECT id, start_time FROM care_working_hours WHERE user_id = ? AND status = 'In Progress' LIMIT 1");
        $chkStmt->execute([$userId]);
        $activeCheck = $chkStmt->fetch();

        if ($activeCheck) {
            setFlash('warning', 'You already have an active duty session started at ' . date('h:i A', strtotime($activeCheck['start_time'])) . '. Please clock out when finished.');
        } else {
            $shiftType = trim($_POST['shift_type'] ?? 'Standard Full Day (08:30 AM – 05:30 PM)');
            $notes     = trim($_POST['notes'] ?? '');
            $now       = date('Y-m-d H:i:s');
            $today     = date('Y-m-d');

            $stmt = $pdo->prepare("
                INSERT INTO care_working_hours (user_id, work_date, shift_type, start_time, status, notes, created_at)
                VALUES (?, ?, ?, ?, 'In Progress', ?, ?)
            ");
            $stmt->execute([$userId, $today, $shiftType, $now, $notes, $now]);

            setFlash('success', '🟢 Clocked In successfully at ' . date('h:i A') . '! Your duty session is now active.');
        }
        redirect(BASE_URL . '/care/hours.php');
    }

    // 2. CLOCK OUT ACTION
    elseif ($action === 'clock_out') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $tasksSummary = trim($_POST['tasks_summary'] ?? '');
        $handoverNotes = trim($_POST['notes'] ?? '');

        // Fetch session
        if ($sessionId > 0) {
            $stmt = $pdo->prepare("SELECT * FROM care_working_hours WHERE id = ? AND user_id = ? AND status = 'In Progress'");
            $stmt->execute([$sessionId, $userId]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM care_working_hours WHERE user_id = ? AND status = 'In Progress' ORDER BY id DESC LIMIT 1");
            $stmt->execute([$userId]);
        }
        $session = $stmt->fetch();

        if (!$session) {
            setFlash('error', 'No active duty session found to clock out from.');
        } else {
            $now = date('Y-m-d H:i:s');
            $startTs = strtotime($session['start_time']);
            $endTs   = strtotime($now);
            $seconds = max(0, $endTs - $startTs);
            $hours   = round($seconds / 3600, 2);
            // Ensure minimum 0.05 hrs (3 mins) if clocked out quickly
            if ($hours < 0.05 && $seconds > 30) {
                $hours = 0.05;
            }

            $update = $pdo->prepare("
                UPDATE care_working_hours
                SET end_time = ?,
                    total_hours = ?,
                    status = 'Completed',
                    tasks_summary = ?,
                    notes = CASE WHEN ? != '' THEN ? ELSE notes END,
                    updated_at = ?
                WHERE id = ?
            ");
            $update->execute([$now, $hours, $tasksSummary, $handoverNotes, $handoverNotes, $now, $session['id']]);

            setFlash('success', '🏁 Clocked Out successfully at ' . date('h:i A') . '! Total duty logged: ' . number_format($hours, 2) . ' hours. Thank you for your work!');
        }
        redirect(BASE_URL . '/care/hours.php');
    }

    // 3. LOG MANUAL WORKING HOURS ACTION
    elseif ($action === 'log_manual_hours') {
        $workDate     = trim($_POST['work_date'] ?? date('Y-m-d'));
        $shiftType    = trim($_POST['shift_type'] ?? 'Standard Full Day (08:30 AM – 05:30 PM)');
        $startTimeRaw = trim($_POST['start_time'] ?? '');
        $endTimeRaw   = trim($_POST['end_time'] ?? '');
        $tasksSummary = trim($_POST['tasks_summary'] ?? '');
        $notes        = trim($_POST['notes'] ?? '');

        if (empty($workDate) || empty($startTimeRaw) || empty($endTimeRaw)) {
            setFlash('error', 'Please provide work date, start time, and end time.');
        } else {
            $fullStart = $workDate . ' ' . $startTimeRaw . ':00';
            $fullEnd   = $workDate . ' ' . $endTimeRaw . ':00';

            // Handle overnight shifts where end time is on next day
            if (strtotime($fullEnd) <= strtotime($fullStart)) {
                $fullEnd = date('Y-m-d H:i:s', strtotime($fullEnd . ' +1 day'));
            }

            $startTs = strtotime($fullStart);
            $endTs   = strtotime($fullEnd);
            $diffSec = max(0, $endTs - $startTs);
            $hours   = round($diffSec / 3600, 2);

            if ($hours <= 0) {
                setFlash('error', 'Invalid time range: End time must be after start time.');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO care_working_hours (user_id, work_date, shift_type, start_time, end_time, total_hours, status, tasks_summary, notes, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, 'Completed', ?, ?, NOW())
                ");
                $stmt->execute([$userId, $workDate, $shiftType, $fullStart, $fullEnd, $hours, $tasksSummary, $notes]);

                setFlash('success', '✅ Successfully logged shift for ' . date('d M Y', strtotime($workDate)) . ' (' . number_format($hours, 2) . ' hrs).');
            }
        }
        redirect(BASE_URL . '/care/hours.php');
    }

    // 4. UPDATE TASKS / NOTES
    elseif ($action === 'update_notes') {
        $sessionId = (int)($_POST['session_id'] ?? 0);
        $tasksSummary = trim($_POST['tasks_summary'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        $stmt = $pdo->prepare("
            UPDATE care_working_hours 
            SET tasks_summary = ?, notes = ?, updated_at = NOW() 
            WHERE id = ? AND (user_id = ? OR ? = 'admin')
        ");
        $stmt->execute([$tasksSummary, $notes, $sessionId, $userId, getUserRole()]);

        setFlash('success', 'Shift details updated successfully.');
        redirect(BASE_URL . '/care/hours.php');
    }

    // 5. DELETE ACCIDENTAL ENTRY
    elseif ($action === 'delete_entry') {
        $sessionId = (int)($_POST['session_id'] ?? 0);

        $stmt = $pdo->prepare("DELETE FROM care_working_hours WHERE id = ? AND (user_id = ? OR ? = 'admin')");
        $stmt->execute([$sessionId, $userId, getUserRole()]);

        setFlash('info', 'Working hours entry has been deleted.');
        redirect(BASE_URL . '/care/hours.php');
    }
}

// Check for Current Active Duty Session
$stmtActive = $pdo->prepare("
    SELECT * FROM care_working_hours 
    WHERE user_id = ? AND status = 'In Progress' 
    ORDER BY id DESC LIMIT 1
");
$stmtActive->execute([$userId]);
$activeSession = $stmtActive->fetch();

// Compute KPIs for Logged User
// 1. Total Hours This Week (Monday to Sunday)
$stmtWeek = $pdo->prepare("
    SELECT COALESCE(SUM(total_hours), 0) FROM care_working_hours
    WHERE user_id = ? 
      AND status IN ('Completed', 'Approved')
      AND work_date >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
");
$stmtWeek->execute([$userId]);
$hoursThisWeek = (float)$stmtWeek->fetchColumn();

// 2. Total Hours This Month
$stmtMonth = $pdo->prepare("
    SELECT COALESCE(SUM(total_hours), 0) FROM care_working_hours
    WHERE user_id = ? 
      AND status IN ('Completed', 'Approved')
      AND MONTH(work_date) = MONTH(CURDATE())
      AND YEAR(work_date) = YEAR(CURDATE())
");
$stmtMonth->execute([$userId]);
$hoursThisMonth = (float)$stmtMonth->fetchColumn();

// 3. Total Completed Shifts Count
$stmtCompleted = $pdo->prepare("
    SELECT COUNT(*) FROM care_working_hours
    WHERE user_id = ? AND status IN ('Completed', 'Approved')
");
$stmtCompleted->execute([$userId]);
$completedShifts = (int)$stmtCompleted->fetchColumn();

// 4. Hours Today
$stmtToday = $pdo->prepare("
    SELECT COALESCE(SUM(total_hours), 0) FROM care_working_hours
    WHERE user_id = ? 
      AND status IN ('Completed', 'Approved')
      AND work_date = CURDATE()
");
$stmtToday->execute([$userId]);
$hoursToday = (float)$stmtToday->fetchColumn();

// Fetch History with optional filter
$filterMonth = trim($_GET['month'] ?? date('Y-m'));
$querySql = "
    SELECT * FROM care_working_hours 
    WHERE user_id = ?
";
$params = [$userId];

if (!empty($filterMonth)) {
    $querySql .= " AND DATE_FORMAT(work_date, '%Y-%m') = ?";
    $params[] = $filterMonth;
}
$querySql .= " ORDER BY work_date DESC, start_time DESC LIMIT 100";

$stmtHistory = $pdo->prepare($querySql);
$stmtHistory->execute($params);
$historyList = $stmtHistory->fetchAll();

require_once __DIR__ . '/../includes/care_header.php';
?>

<!-- Breadcrumb Navigation -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/care/index.php" class="text-decoration-none">Customer Care Desk</a></li>
        <li class="breadcrumb-item active" aria-current="page">Working Hours & Duty Desk</li>
    </ol>
</nav>

<!-- Page Header Banner -->
<div class="admin-card p-4 mb-4" style="background: linear-gradient(135deg, #0b192c 0%, #1e3a8a 100%); color: #ffffff; border-radius: 16px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-warning text-dark px-3 py-1 fw-bold" style="letter-spacing: 0.5px;">
                    <i class="fa-solid fa-business-time me-1"></i> ATTENDANCE & SHIFTS
                </span>
                <span class="text-light text-opacity-75 small">Sri Lanka Time (Asia/Colombo)</span>
            </div>
            <h3 class="text-white fw-bold mb-1">Customer Care Duty & Working Hours</h3>
            <p class="text-light text-opacity-75 small mb-0">
                Log and track your daily shift hours, start and end times, support tasks completed, and shift handover reports.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <!-- Digital Clock Display -->
            <div class="px-3 py-2 rounded-3 text-center" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2);">
                <div class="text-light text-opacity-75" style="font-size: 0.7rem; font-weight: 600; text-transform: uppercase;">Current Live Time</div>
                <div id="liveDigitalClock" class="font-monospace fw-bold text-white fs-5" style="letter-spacing: 1px;">--:--:--</div>
            </div>
            <button type="button" class="btn btn-light btn-sm px-3 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#manualHoursModal">
                <i class="fa-solid fa-calendar-plus me-1 text-primary"></i> Log Past Hours
            </button>
        </div>
    </div>
</div>

<!-- KPI Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Active Duty Status -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card <?php echo $activeSession ? 'border-success' : 'border-secondary'; ?>" style="border-left: 4px solid <?php echo $activeSession ? '#10b981' : '#64748b'; ?>;">
            <div class="stat-card-title <?php echo $activeSession ? 'text-success' : 'text-secondary'; ?>">Current Duty Status</div>
            <div class="stat-card-value d-flex align-items-center gap-2 <?php echo $activeSession ? 'text-success' : 'text-secondary'; ?>" style="font-size: 1.4rem;">
                <?php if ($activeSession): ?>
                    <span class="badge bg-success px-2 py-1 text-white fs-6"><i class="fa-solid fa-circle-dot fa-fade me-1"></i> On Duty</span>
                <?php else: ?>
                    <span class="badge bg-secondary px-2 py-1 text-white fs-6"><i class="fa-regular fa-circle-pause me-1"></i> Off Duty</span>
                <?php endif; ?>
            </div>
            <div class="text-muted small mt-1">
                <?php echo $activeSession ? 'Started: ' . date('h:i A', strtotime($activeSession['start_time'])) : 'Ready to start shift'; ?>
            </div>
            <div class="stat-icon-wrapper <?php echo $activeSession ? 'text-success' : 'text-secondary'; ?>">
                <i class="fa-solid <?php echo $activeSession ? 'fa-clock' : 'fa-power-off'; ?>"></i>
            </div>
        </div>
    </div>

    <!-- Today's Logged Hours -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #06b6d4;">
            <div class="stat-card-title text-info">Hours Completed Today</div>
            <div class="stat-card-value text-info"><?php echo number_format($hoursToday, 2); ?> <span class="fs-6 fw-normal text-muted">hrs</span></div>
            <div class="text-muted small mt-1"><?php echo date('D, d M Y'); ?></div>
            <div class="stat-icon-wrapper text-info"><i class="fa-solid fa-hourglass-half"></i></div>
        </div>
    </div>

    <!-- Hours This Week -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #3b82f6;">
            <div class="stat-card-title text-primary">Hours This Week</div>
            <div class="stat-card-value text-primary"><?php echo number_format($hoursThisWeek, 2); ?> <span class="fs-6 fw-normal text-muted">hrs</span></div>
            <div class="text-muted small mt-1">Mon – Sun work cycle</div>
            <div class="stat-icon-wrapper text-primary"><i class="fa-solid fa-calendar-week"></i></div>
        </div>
    </div>

    <!-- Hours This Month -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stat-card-title text-purple" style="color: #8b5cf6;">Hours This Month</div>
            <div class="stat-card-value" style="color: #8b5cf6;"><?php echo number_format($hoursThisMonth, 2); ?> <span class="fs-6 fw-normal text-muted">hrs</span></div>
            <div class="text-muted small mt-1"><?php echo date('F Y'); ?> • <?php echo $completedShifts; ?> shifts total</div>
            <div class="stat-icon-wrapper" style="color: #8b5cf6;"><i class="fa-solid fa-chart-line"></i></div>
        </div>
    </div>
</div>

<!-- Current Duty Shift Action Box -->
<?php if ($activeSession): ?>
    <!-- Active Shift In Progress Card -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 16px; background: linear-gradient(to right, #ecfdf5, #f0fdf4); border: 2px solid #86efac !important;">
        <div class="card-body p-4">
            <div class="row align-items-center g-3">
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-success px-3 py-1 fs-6">
                            <i class="fa-solid fa-circle fa-beat-fade me-1 small"></i> ON DUTY NOW
                        </span>
                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($activeSession['shift_type']); ?></span>
                    </div>
                    <h4 class="text-dark fw-bold mb-1">Shift in Progress</h4>
                    <p class="text-secondary small mb-3">
                        Clocked in on <strong><?php echo date('d M Y', strtotime($activeSession['start_time'])); ?></strong> at <strong><?php echo date('h:i:s A', strtotime($activeSession['start_time'])); ?></strong>
                        <?php if (!empty($activeSession['notes'])): ?>
                            <br><span class="text-muted"><i class="fa-regular fa-comment-dots me-1"></i> <?php echo htmlspecialchars($activeSession['notes']); ?></span>
                        <?php endif; ?>
                    </p>
                    
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-2 px-3 rounded-3 bg-white border border-success-subtle shadow-sm">
                            <span class="text-muted small d-block">Elapsed Shift Time</span>
                            <span id="elapsedStopwatch" class="fs-4 fw-bold font-monospace text-success" data-start="<?php echo strtotime($activeSession['start_time']); ?>">00:00:00</span>
                        </div>
                        <div class="p-2 px-3 rounded-3 bg-white border border-secondary-subtle">
                            <span class="text-muted small d-block">Shift Date</span>
                            <span class="fw-semibold text-dark"><?php echo date('l, M j', strtotime($activeSession['work_date'])); ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 text-lg-end">
                    <button type="button" class="btn btn-danger btn-lg px-4 py-3 shadow" data-bs-toggle="modal" data-bs-target="#clockOutModal">
                        <i class="fa-solid fa-stopwatch-20 me-2"></i> Clock Out / End Shift
                    </button>
                    <div class="text-muted small mt-2">
                        You can record your completed tasks and handover notes when clocking out.
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- Clock In Card -->
    <div class="admin-card p-4 mb-4" style="border-radius: 16px; border: 1.5px dashed #cbd5e1;">
        <div class="row align-items-center g-3">
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-secondary px-2 py-1">OFF DUTY</span>
                    <span class="text-muted small">Ready to start today's work session</span>
                </div>
                <h4 class="text-dark fw-bold mb-1">Start Your Customer Care Shift</h4>
                <p class="text-secondary small mb-0">
                    Select your scheduled shift schedule below and click Clock In. The system will start recording your working period in real-time.
                </p>
            </div>
            <div class="col-lg-6">
                <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST" class="d-flex flex-column flex-sm-row gap-2">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="action" value="clock_in">
                    
                    <div class="flex-grow-1">
                        <select name="shift_type" class="form-select form-select-lg" required>
                            <?php foreach ($shiftOptions as $opt): ?>
                                <option value="<?php echo htmlspecialchars($opt); ?>"><?php echo htmlspecialchars($opt); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg px-4 d-flex align-items-center justify-content-center gap-2 text-nowrap">
                        <i class="fa-solid fa-play"></i> Clock In Now
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Working Hours History Section -->
<div class="admin-card mb-4" style="border-radius: 16px;">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center p-3 px-4 border-bottom gap-3">
        <div>
            <h5 class="mb-1 text-dark fw-bold">
                <i class="fa-solid fa-timeline me-2 text-primary"></i> Working Hours & Attendance History
            </h5>
            <span class="text-muted small">Comprehensive log of clocked-in shifts, durations, and tasks completed</span>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- Month Filter Form -->
            <form method="GET" action="<?php echo BASE_URL; ?>/care/hours.php" class="d-flex align-items-center gap-2">
                <input type="month" name="month" class="form-control form-control-sm" value="<?php echo htmlspecialchars($filterMonth); ?>" onchange="this.form.submit()">
                <?php if (!empty($filterMonth)): ?>
                    <a href="<?php echo BASE_URL; ?>/care/hours.php" class="btn btn-sm btn-outline-secondary" title="Clear filter">All</a>
                <?php endif; ?>
            </form>

            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i> Print Timesheet
            </button>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="hoursTable">
            <thead class="table-light">
                <tr>
                    <th class="ps-4">Date & Day</th>
                    <th>Shift Type</th>
                    <th>Working Time Period</th>
                    <th class="text-center">Total Hours</th>
                    <th>Tasks & Handover Summary</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historyList)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-clock-rotate-left fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                            <h6 class="fw-bold">No working hours logged for this period</h6>
                            <p class="small text-muted mb-3">Clock in when starting your shift or use "Log Past Hours" to register your offline duty.</p>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#manualHoursModal">
                                <i class="fa-solid fa-plus me-1"></i> Log Shift Hours
                            </button>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $totalTableHours = 0;
                    foreach ($historyList as $row): 
                        $totalTableHours += (float)($row['total_hours'] ?? 0);
                        $isInProgress = ($row['status'] === 'In Progress');
                    ?>
                        <tr class="<?php echo $isInProgress ? 'table-warning bg-opacity-25' : ''; ?>">
                            <td class="ps-4">
                                <div class="fw-bold text-dark"><?php echo date('d M Y', strtotime($row['work_date'])); ?></div>
                                <div class="text-muted small"><?php echo date('l', strtotime($row['work_date'])); ?></div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    <?php echo htmlspecialchars($row['shift_type']); ?>
                                </span>
                            </td>
                            <td>
                                <div class="font-monospace small">
                                    <span class="text-success fw-bold"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i> <?php echo date('h:i A', strtotime($row['start_time'])); ?></span>
                                    <span class="text-muted mx-1">→</span>
                                    <?php if (!empty($row['end_time'])): ?>
                                        <span class="text-danger fw-bold"><i class="fa-solid fa-arrow-right-from-bracket me-1"></i> <?php echo date('h:i A', strtotime($row['end_time'])); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success bg-opacity-25 text-success">Running Now</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($row['total_hours'])): ?>
                                    <span class="badge bg-primary fs-6 px-2 py-1">
                                        <?php echo number_format($row['total_hours'], 2); ?> <span class="small fw-normal">hrs</span>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small font-monospace">--:--</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 320px;">
                                <?php if (!empty($row['tasks_summary'])): ?>
                                    <div class="small text-dark fw-semibold text-truncate" title="<?php echo htmlspecialchars($row['tasks_summary']); ?>">
                                        <i class="fa-solid fa-list-check me-1 text-primary"></i> <?php echo htmlspecialchars($row['tasks_summary']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($row['notes'])): ?>
                                    <div class="text-muted small text-truncate" title="<?php echo htmlspecialchars($row['notes']); ?>">
                                        <i class="fa-regular fa-comment-dots me-1 text-secondary"></i> <?php echo htmlspecialchars($row['notes']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (empty($row['tasks_summary']) && empty($row['notes'])): ?>
                                    <span class="text-muted small italic">No notes recorded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'In Progress'): ?>
                                    <span class="badge bg-warning text-dark"><i class="fa-solid fa-spinner fa-spin me-1"></i> In Progress</span>
                                <?php elseif ($row['status'] === 'Approved'): ?>
                                    <span class="badge bg-success"><i class="fa-solid fa-check-double me-1"></i> Approved</span>
                                <?php else: ?>
                                    <span class="badge bg-info text-dark"><i class="fa-solid fa-check me-1"></i> Completed</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if ($isInProgress): ?>
                                    <button type="button" class="btn btn-sm btn-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#clockOutModal">
                                        <i class="fa-solid fa-stopwatch me-1"></i> Clock Out
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-light border py-1 px-2 text-secondary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editNotesModal"
                                            data-session-id="<?php echo $row['id']; ?>"
                                            data-tasks="<?php echo htmlspecialchars($row['tasks_summary'] ?? ''); ?>"
                                            data-notes="<?php echo htmlspecialchars($row['notes'] ?? ''); ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <?php if (getUserRole() === 'admin'): ?>
                                        <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this shift record?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                                            <input type="hidden" name="action" value="delete_entry">
                                            <input type="hidden" name="session_id" value="<?php echo $row['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-light border text-danger py-1 px-2">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if (!empty($historyList)): ?>
                <tfoot class="table-light">
                    <tr>
                        <th colspan="3" class="ps-4 text-end">Total Hours for Selected Period:</th>
                        <th class="text-center">
                            <span class="badge bg-dark fs-6 px-3 py-1">
                                <?php echo number_format($totalTableHours, 2); ?> hrs
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
<!-- MODAL: CLOCK OUT / END SHIFT -->
<!-- ========================================== -->
<div class="modal fade" id="clockOutModal" tabindex="-1" aria-labelledby="clockOutModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="clock_out">
                <input type="hidden" name="session_id" value="<?php echo $activeSession['id'] ?? 0; ?>">

                <div class="modal-header bg-danger text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="clockOutModalLabel">
                        <i class="fa-solid fa-stopwatch me-2"></i> End Shift & Clock Out
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-triangle-exclamation fs-4"></i>
                        <div>
                            Clocking out at <strong><?php echo date('h:i A'); ?></strong>.
                            Total hours will be calculated automatically based on your start time (<?php echo $activeSession ? date('h:i A', strtotime($activeSession['start_time'])) : '--'; ?>).
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fa-solid fa-list-check me-1 text-primary"></i> Tasks Completed During Shift *
                        </label>
                        <textarea name="tasks_summary" class="form-control" rows="3" placeholder="e.g. Processed 3 customer orders (CHW-2026-0004), verified 50% milestone payment for Aqua Super, answered 12 customer inquiries on WhatsApp..." required></textarea>
                        <div class="form-text">Summary of customer calls, order entries, assignments, and payments handled.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fa-solid fa-clipboard-list me-1 text-secondary"></i> Handover Notes for Next Shift / Admin (Optional)
                        </label>
                        <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Waiting for bank slip from Mr. Nimal for project CHW-2026-0005. Developer 01 working on shopping cart..."></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger px-4 fw-semibold">
                        <i class="fa-solid fa-check me-1"></i> Confirm & Clock Out
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: LOG PAST / MANUAL WORKING HOURS -->
<!-- ========================================== -->
<div class="modal fade" id="manualHoursModal" tabindex="-1" aria-labelledby="manualHoursModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="log_manual_hours">

                <div class="modal-header bg-primary text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="manualHoursModalLabel">
                        <i class="fa-solid fa-calendar-plus me-2"></i> Log Past / Offline Working Hours
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <p class="text-secondary small mb-3">
                        Use this form to log shifts worked offline, on-call support hours outside office, or shifts where real-time clock-in was missed.
                    </p>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">Shift Date *</label>
                            <input type="date" name="work_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" required>
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
                            <input type="time" name="start_time" id="manualStartTime" class="form-control" value="08:30" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark">End Time *</label>
                            <input type="time" name="end_time" id="manualEndTime" class="form-control" value="17:30" required>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 d-flex align-items-center justify-content-between mb-3">
                        <span class="small fw-semibold"><i class="fa-solid fa-calculator me-1"></i> Calculated Duration:</span>
                        <span id="manualDurationCalc" class="badge bg-primary fs-6">9.00 hrs</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fa-solid fa-list-check me-1 text-primary"></i> Tasks Handled / Summary *
                        </label>
                        <textarea name="tasks_summary" class="form-control" rows="3" placeholder="Enter list of customer requests, assigned tasks, WhatsApp follow-ups, and payments processed..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">
                            <i class="fa-solid fa-note-sticky me-1 text-secondary"></i> Additional Notes (Optional)
                        </label>
                        <input type="text" name="notes" class="form-control" placeholder="Optional notes for payroll or management review">
                    </div>
                </div>

                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Save Working Hours
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: EDIT NOTES -->
<!-- ========================================== -->
<div class="modal fade" id="editNotesModal" tabindex="-1" aria-labelledby="editNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow" style="border-radius: 16px;">
            <form action="<?php echo BASE_URL; ?>/care/hours.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                <input type="hidden" name="action" value="update_notes">
                <input type="hidden" name="session_id" id="editSessionId" value="">

                <div class="modal-header bg-dark text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                    <h5 class="modal-title fw-bold" id="editNotesModalLabel">
                        <i class="fa-solid fa-pen-to-square me-2"></i> Update Shift Tasks & Notes
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Tasks Summary</label>
                        <textarea name="tasks_summary" id="editTasksSummary" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Handover / Additional Notes</label>
                        <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                    </div>
                </div>

                <div class="modal-footer bg-light" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold">
                        <i class="fa-solid fa-save me-1"></i> Update Details
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts for Clock, Stopwatch and Duration Calculation -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Digital Live Clock
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('en-US', { hour12: true, hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const clockEl = document.getElementById('liveDigitalClock');
        if (clockEl) {
            clockEl.textContent = timeStr;
        }
    }
    updateClock();
    setInterval(updateClock, 1000);

    // 2. Elapsed Stopwatch for Active Duty
    const stopwatchEl = document.getElementById('elapsedStopwatch');
    if (stopwatchEl) {
        const startTs = parseInt(stopwatchEl.getAttribute('data-start'), 10) * 1000;
        function updateStopwatch() {
            const now = Date.now();
            const diff = Math.max(0, now - startTs);
            const hrs = Math.floor(diff / (1000 * 60 * 60));
            const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const secs = Math.floor((diff % (1000 * 60)) / 1000);
            stopwatchEl.textContent = 
                String(hrs).padStart(2, '0') + ':' + 
                String(mins).padStart(2, '0') + ':' + 
                String(secs).padStart(2, '0');
        }
        updateStopwatch();
        setInterval(updateStopwatch, 1000);
    }

    // 3. Manual Hours Calculator
    const startTimeInput = document.getElementById('manualStartTime');
    const endTimeInput = document.getElementById('manualEndTime');
    const durationBadge = document.getElementById('manualDurationCalc');

    function calculateDuration() {
        if (!startTimeInput || !endTimeInput || !durationBadge) return;
        const startVal = startTimeInput.value;
        const endVal = endTimeInput.value;
        if (!startVal || !endVal) return;

        const [sh, sm] = startVal.split(':').map(Number);
        const [eh, em] = endVal.split(':').map(Number);
        let startMins = sh * 60 + sm;
        let endMins = eh * 60 + em;

        if (endMins < startMins) {
            // crosses midnight
            endMins += 24 * 60;
        }

        const totalMins = endMins - startMins;
        const hours = (totalMins / 60).toFixed(2);
        durationBadge.textContent = hours + ' hrs';
    }

    if (startTimeInput && endTimeInput) {
        startTimeInput.addEventListener('change', calculateDuration);
        endTimeInput.addEventListener('change', calculateDuration);
        calculateDuration();
    }

    // 4. Populate Edit Notes Modal
    const editModal = document.getElementById('editNotesModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            if (!button) return;
            const sessionId = button.getAttribute('data-session-id');
            const tasks = button.getAttribute('data-tasks');
            const notes = button.getAttribute('data-notes');

            document.getElementById('editSessionId').value = sessionId || '';
            document.getElementById('editTasksSummary').value = tasks || '';
            document.getElementById('editNotes').value = notes || '';
        });
    }
});
</script>

<?php require_once __DIR__ . '/../includes/care_footer.php'; ?>

