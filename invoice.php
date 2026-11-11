<?php
/**
 * Chandila Web Development – Official Project Invoice & Payment Receipt
 * Fully customizable, mobile-friendly, and print-optimized (A4 PDF ready)
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDB();

// Fetch System Settings
$settings = [];
if ($pdo) {
    try {
        $sQuery = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
        while ($r = $sQuery->fetch()) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
    } catch (Exception $e) {}
}

// Configurable Invoice Settings
$invCompanyName   = $settings['invoice_company_name']   ?? ($settings['company_name'] ?? 'Chandila Web Development (Pvt) Ltd');
$invTagline       = $settings['invoice_tagline']        ?? 'Where Innovation Meets Growth';
$invAddress       = $settings['invoice_address']        ?? ($settings['company_address'] ?? 'No. 45, Galle Road, Colombo 03, Sri Lanka');
$invPhone         = $settings['invoice_phone']          ?? ($settings['company_phone'] ?? '076 843 3340');
$invEmail         = $settings['invoice_email']          ?? ($settings['company_email'] ?? 'billing@chandilaweb.com');
$invTaxNumber     = $settings['invoice_tax_number']      ?? 'PV-00284729';
$invPrefix        = $settings['invoice_prefix']         ?? 'INV-';
$invThemeColor    = $settings['invoice_theme_color']     ?? '#2563eb';
$invStampText     = $settings['invoice_paid_stamp_text'] ?? 'PAID IN FULL';
$invStampStyle    = $settings['invoice_paid_stamp_style']?? 'classic_red';
$invShowWatermark = ($settings['invoice_show_watermark'] ?? '1') !== '0';
$invFooterNotes   = $settings['invoice_footer_notes']   ?? 'Thank you for choosing Chandila Web Development. All software code, database structures, and production access rights will be permanently transferred upon complete project settlement. Please retain this official invoice for your accounting records.';

$bankName    = $settings['bank_name'] ?? 'Commercial Bank of Ceylon';
$bankAccName = $settings['bank_account_name'] ?? 'Chandila Web Development (Pvt) Ltd';
$bankAccNum  = $settings['bank_account_number'] ?? '800123456789';
$bankBranch  = $settings['bank_branch'] ?? 'Colombo 03 Branch';

// Request Parameters
$jobId     = trim($_GET['job_id'] ?? '');
$paymentId = (int)($_GET['payment_id'] ?? 0);
$stageReq  = trim($_GET['stage'] ?? ''); // advance, milestone2, final
$isPreview = isset($_GET['preview']) && $_GET['preview'] == '1';

$project = null;
$customer = null;
$payments = [];
$targetPayment = null;

// Handle Specific Payment ID lookup
if ($paymentId > 0 && $pdo) {
    $stmtP = $pdo->prepare("
        SELECT pm.*, p.id as project_id, p.job_id, p.project_title, p.project_type, p.total_cost, p.advance_amount, 
               p.milestone2_amount, p.final_amount, p.total_paid, p.balance_due, p.status as project_status, p.created_at as project_created,
               c.full_name as customer_name, c.company_name, c.email as customer_email, c.phone as customer_phone, c.whatsapp_number
        FROM payments pm
        JOIN projects p ON pm.project_id = p.id
        JOIN customers c ON p.customer_id = c.id
        WHERE pm.id = ?
    ");
    $stmtP->execute([$paymentId]);
    $row = $stmtP->fetch();
    if ($row) {
        $targetPayment = $row;
        $jobId = $row['job_id'];
    }
}

// Handle Job ID lookup
if (!empty($jobId) && $pdo) {
    $stmtPr = $pdo->prepare("
        SELECT p.*, c.full_name as customer_name, c.company_name, c.email as customer_email, 
               c.phone as customer_phone, c.whatsapp_number
        FROM projects p
        JOIN customers c ON p.customer_id = c.id
        WHERE p.job_id = ?
    ");
    $stmtPr->execute([$jobId]);
    $project = $stmtPr->fetch();

    if ($project) {
        $stmtPay = $pdo->prepare("SELECT * FROM payments WHERE project_id = ? ORDER BY payment_date ASC, id ASC");
        $stmtPay->execute([$project['id']]);
        $payments = $stmtPay->fetchAll();
    }
}

// Fallback to Realistic Demo Data for Admin Settings Preview
if (!$project && ($isPreview || empty($jobId))) {
    $project = [
        'id'                  => 999,
        'job_id'              => 'CHW-DEMO',
        'project_title'       => 'Enterprise E-Commerce & Customer Portal',
        'project_type'        => 'E-commerce Website',
        'total_cost'          => 150000.00,
        'advance_amount'      => 45000.00,
        'milestone2_amount'   => 75000.00,
        'final_amount'        => 30000.00,
        'status'              => ($stageReq === 'final' ? 'Project Completed' : ($stageReq === 'advance' ? 'Advance Payment Confirmed' : 'Second Payment Pending')),
        'progress_percentage' => ($stageReq === 'final' ? 100 : ($stageReq === 'advance' ? 20 : 50)),
        'customer_name'       => 'Kasun Perera',
        'company_name'        => 'Ceylon Agro Export (Pvt) Ltd',
        'customer_email'      => 'kasun@ceylonagro.lk',
        'customer_phone'      => '+94 77 123 4567',
        'whatsapp_number'     => '+94 77 123 4567',
        'created_at'          => date('Y-m-d H:i:s', strtotime('-14 days')),
        'expected_completion_date' => date('Y-m-d', strtotime('+7 days'))
    ];

    if ($stageReq === 'advance') {
        $project['total_paid']  = 45000.00;
        $project['balance_due'] = 105000.00;
        $payments = [
            [
                'id' => 101,
                'payment_stage' => 'Advance Payment',
                'amount' => 45000.00,
                'payment_method' => 'Bank Transfer',
                'transaction_reference' => 'COMM-DEP-88419',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-12 days'))
            ]
        ];
    } elseif ($stageReq === 'milestone2') {
        $project['total_paid']  = 120000.00;
        $project['balance_due'] = 30000.00;
        $payments = [
            [
                'id' => 101,
                'payment_stage' => 'Advance Payment',
                'amount' => 45000.00,
                'payment_method' => 'Bank Transfer',
                'transaction_reference' => 'COMM-DEP-88419',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-14 days'))
            ],
            [
                'id' => 102,
                'payment_stage' => 'Second Payment (50% Milestone)',
                'amount' => 75000.00,
                'payment_method' => 'Online Banking Slip',
                'transaction_reference' => 'COMM-TXN-99421',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-2 days'))
            ]
        ];
    } else { // final / default preview
        $stageReq = 'final';
        $project['total_paid']  = 150000.00;
        $project['balance_due'] = 0.00;
        $payments = [
            [
                'id' => 101,
                'payment_stage' => 'Advance Payment',
                'amount' => 45000.00,
                'payment_method' => 'Bank Transfer',
                'transaction_reference' => 'COMM-DEP-88419',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-20 days'))
            ],
            [
                'id' => 102,
                'payment_stage' => 'Second Payment (50% Milestone)',
                'amount' => 75000.00,
                'payment_method' => 'Online Banking Slip',
                'transaction_reference' => 'COMM-TXN-99421',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-10 days'))
            ],
            [
                'id' => 103,
                'payment_stage' => 'Final Payment',
                'amount' => 30000.00,
                'payment_method' => 'Bank Transfer',
                'transaction_reference' => 'BOC-TXN-44912',
                'status' => 'Payment Received',
                'payment_date' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ]
        ];
    }
}

// Calculate Financials & Settlement Status
$totalCost   = (float)$project['total_cost'];
$totalPaid   = (float)$project['total_paid'];
$balanceDue  = max(0, (float)$project['balance_due']);
$advanceAmt  = (float)$project['advance_amount'];
$m2Amt       = (float)$project['milestone2_amount'];
if ($m2Amt <= 0 && $totalCost > 0) {
    $m2Amt = round($totalCost * 0.50, 2);
}
$finalAmt    = (float)$project['final_amount'];
if ($finalAmt <= 0 && $totalCost > 0) {
    $finalAmt = max(0, $totalCost - $advanceAmt - $m2Amt);
}

// Check Stage Settlement
$isFullyPaid = ($balanceDue <= 0 && $totalPaid > 0) || ($stageReq === 'final' && $balanceDue <= 0);
$isMilestone2Paid = ($totalPaid >= ($advanceAmt + $m2Amt) && $m2Amt > 0) || $stageReq === 'milestone2';
$isAdvancePaid = ($totalPaid >= $advanceAmt && $advanceAmt > 0) || $stageReq === 'advance';

// Determine Invoice Title & Number
$invoiceNo = $invPrefix . date('Y', strtotime($project['created_at'] ?? 'now')) . '-' . preg_replace('/[^A-Za-z0-9]/', '', $project['job_id']);
if ($targetPayment) {
    $invoiceNo .= '-REC' . $targetPayment['id'];
}

$pageTitle = "Official Invoice {$invoiceNo} – {$invCompanyName}";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        :root {
            --inv-theme: <?php echo htmlspecialchars($invThemeColor); ?>;
            --inv-dark: #0f172a;
            --inv-light: #f8fafc;
            --inv-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #1e293b;
            margin: 0;
            padding: 30px 15px 60px 15px;
            min-height: 100vh;
        }

        /* Top Action Bar (Hidden in Print) */
        .invoice-action-bar {
            max-width: 860px;
            margin: 0 auto 24px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Invoice Paper Sheet (A4 Proportion) */
        .invoice-sheet {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            padding: 48px 52px;
        }

        /* Top Accent Stripe */
        .invoice-sheet::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--inv-theme);
        }

        /* Watermark */
        .paid-watermark {
            position: absolute;
            top: 52%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 8.5rem;
            font-weight: 900;
            color: rgba(220, 38, 38, 0.05);
            pointer-events: none;
            letter-spacing: 16px;
            text-transform: uppercase;
            font-family: 'Space Grotesk', sans-serif;
            z-index: 0;
            white-space: nowrap;
        }

        .invoice-content {
            position: relative;
            z-index: 2;
        }

        /* Header Layout */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding-bottom: 28px;
            border-bottom: 2px solid #f1f5f9;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .brand-logo-img {
            max-height: 48px;
            width: auto;
            margin-bottom: 10px;
        }

        .invoice-title-block {
            text-align: right;
        }

        .invoice-meta-badge {
            display: inline-block;
            background: rgba(37, 99, 235, 0.08);
            color: var(--inv-theme);
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .invoice-id-display {
            font-family: 'JetBrains Mono', monospace;
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--inv-dark);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        /* Party Details Grid */
        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 32px;
        }

        .party-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
        }

        .party-title {
            font-size: 0.72rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .party-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }

        /* Project Scope Bar */
        .project-scope-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            border-radius: 10px;
            padding: 14px 22px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        /* Table Styling */
        .invoice-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .invoice-table th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
            padding: 14px 18px;
            border-bottom: 2px solid #e2e8f0;
        }

        .invoice-table td {
            padding: 16px 18px;
            font-size: 0.92rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .invoice-table tbody tr:last-child td {
            border-bottom: none;
        }

        .invoice-table tbody tr:hover {
            background-color: #fafbfd;
        }

        /* Calculation & Stamp Block */
        .settlement-summary-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            align-items: center;
        }

        /* Official PAID Rubber Stamp */
        .stamp-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
        }

        .paid-stamp-box {
            display: inline-block;
            padding: 10px 22px;
            text-transform: uppercase;
            font-weight: 800;
            border-radius: 8px;
            letter-spacing: 1.5px;
            transform: rotate(-13deg);
            user-select: none;
            position: relative;
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
            text-align: center;
        }

        .paid-stamp-box .paid-stamp-title {
            font-size: 1.6rem;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: 2px;
            font-family: 'Space Grotesk', sans-serif;
        }

        .paid-stamp-box .paid-stamp-sub {
            font-size: 0.68rem;
            letter-spacing: 1.5px;
            margin-top: 3px;
            font-weight: 700;
            opacity: 0.9;
        }

        .paid-stamp-box .paid-stamp-meta {
            font-size: 0.62rem;
            letter-spacing: 1px;
            margin-top: 4px;
            font-family: 'JetBrains Mono', monospace;
            opacity: 0.8;
        }

        /* Stamp Color Styles */
        .stamp-classic_red {
            color: #dc2626;
            border: 3.5px dashed #dc2626;
            background: rgba(220, 38, 38, 0.05);
        }

        .stamp-emerald_badge {
            color: #059669;
            border: 3.5px solid #059669;
            background: rgba(5, 150, 105, 0.06);
        }

        .stamp-electric_gold {
            color: #d97706;
            border: 3.5px double #d97706;
            background: rgba(217, 119, 6, 0.08);
        }

        .stamp-royal_navy {
            color: #0f172a;
            border: 3.5px solid #0f172a;
            background: rgba(15, 23, 42, 0.05);
        }

        /* Pending / Partial Milestone Seal */
        .milestone-stage-seal {
            border: 2px dashed #d97706;
            color: #b45309;
            background: #fffbeb;
            border-radius: 10px;
            padding: 12px 18px;
            transform: rotate(-3deg);
            text-align: center;
        }

        /* Financial Figures Box */
        .totals-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px 22px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            font-size: 0.92rem;
            color: #475569;
        }

        .total-row.highlight {
            border-top: 2px solid #e2e8f0;
            margin-top: 8px;
            padding-top: 12px;
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f172a;
        }

        .balance-due-pill {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        /* Bank Instructions Box */
        .bank-instruction-box {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }

        /* Footer & Signatures */
        .invoice-footer {
            border-top: 1.5px solid #f1f5f9;
            padding-top: 24px;
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 20px;
            flex-wrap: wrap;
        }

        .terms-text {
            font-size: 0.76rem;
            color: #64748b;
            line-height: 1.6;
            max-width: 520px;
        }

        .signatory-box {
            text-align: right;
        }

        .digital-signature-seal {
            display: inline-block;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 6px;
            margin-bottom: 4px;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            color: #0f172a;
            font-size: 0.95rem;
        }

        /* Print Media Styles */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                color: #000000 !important;
            }

            .invoice-action-bar,
            .no-print {
                display: none !important;
            }

            .invoice-sheet {
                box-shadow: none !important;
                border: none !important;
                padding: 20px !important;
                max-width: 100% !important;
            }

            .paid-watermark {
                opacity: 0.08 !important;
            }
        }
    </style>
</head>
<body>

    <!-- Top Action Bar (Buttons hidden during printing) -->
    <div class="invoice-action-bar no-print">
        <div class="d-flex align-items-center gap-2">
            <a href="<?php echo BASE_URL; ?>/track_project.php?job_id=<?php echo urlencode($project['job_id']); ?>" class="btn btn-outline-light btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i> Track Project
            </a>
            <?php if ($isPreview): ?>
                <span class="badge bg-warning text-dark px-3 py-2 fw-bold">
                    <i class="fa-solid fa-eye me-1"></i> Admin Settings Live Preview
                </span>
            <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- Stage Switchers for Preview -->
            <?php if ($isPreview || !empty($_SESSION['admin_logged_in'])): ?>
                <div class="btn-group btn-group-sm me-2">
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?job_id=<?php echo urlencode($project['job_id']); ?>&stage=advance&preview=1" class="btn <?php echo ($stageReq === 'advance') ? 'btn-primary' : 'btn-outline-light'; ?>">
                        Advance
                    </a>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?job_id=<?php echo urlencode($project['job_id']); ?>&stage=milestone2&preview=1" class="btn <?php echo ($stageReq === 'milestone2') ? 'btn-primary' : 'btn-outline-light'; ?>">
                        50% Milestone
                    </a>
                    <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?job_id=<?php echo urlencode($project['job_id']); ?>&stage=final&preview=1" class="btn <?php echo ($stageReq === 'final' || $isFullyPaid) ? 'btn-success' : 'btn-outline-light'; ?>">
                        Full Paid (Stamp)
                    </a>
                </div>
            <?php endif; ?>

            <button type="button" class="btn btn-success btn-sm px-3 fw-bold" onclick="window.print();">
                <i class="fa-solid fa-print me-1"></i> Print / Save as PDF
            </button>
        </div>
    </div>

    <!-- Main Invoice Paper Sheet -->
    <div class="invoice-sheet">

        <!-- Background PAID Watermark (for Full Settlement) -->
        <?php if ($isFullyPaid && $invShowWatermark): ?>
            <div class="paid-watermark"><?php echo htmlspecialchars($invStampText); ?></div>
        <?php endif; ?>

        <div class="invoice-content">

            <!-- Header: Company & Invoice Identity -->
            <div class="invoice-header">
                <div>
                    <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Chandila Logo" class="brand-logo-img">
                    <div class="fw-bold fs-5 text-dark"><?php echo htmlspecialchars($invCompanyName); ?></div>
                    <div class="text-secondary small"><?php echo htmlspecialchars($invTagline); ?></div>
                    <div class="text-secondary small mt-1">
                        <i class="fa-solid fa-location-dot me-1 text-primary"></i> <?php echo htmlspecialchars($invAddress); ?><br>
                        <i class="fa-solid fa-phone me-1 text-primary"></i> <?php echo htmlspecialchars($invPhone); ?> &nbsp;|&nbsp; 
                        <i class="fa-solid fa-envelope me-1 text-primary"></i> <?php echo htmlspecialchars($invEmail); ?>
                        <?php if (!empty($invTaxNumber)): ?>
                            <br><span class="fw-semibold text-dark">BR / Tax No:</span> <?php echo htmlspecialchars($invTaxNumber); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="invoice-title-block">
                    <div class="invoice-meta-badge">
                        <?php 
                        if ($isFullyPaid) {
                            echo '<i class="fa-solid fa-check-double me-1"></i> Official Settled Invoice';
                        } elseif ($isMilestone2Paid) {
                            echo '<i class="fa-solid fa-clock-rotate-left me-1"></i> 50% Milestone Statement';
                        } else {
                            echo '<i class="fa-solid fa-receipt me-1"></i> Advance Payment Receipt';
                        }
                        ?>
                    </div>
                    <div class="invoice-id-display"><?php echo htmlspecialchars($invoiceNo); ?></div>
                    <div class="text-secondary small mt-1">
                        <strong>Invoice Date:</strong> <?php echo date('M d, Y'); ?><br>
                        <strong>Job Reference:</strong> <span class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($project['job_id']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Party Details: Billed To & Project Details -->
            <div class="invoice-parties">
                <div class="party-box">
                    <div class="party-title"><i class="fa-solid fa-user-check text-primary me-1"></i> Invoiced To (Client)</div>
                    <div class="party-name"><?php echo htmlspecialchars($project['customer_name']); ?></div>
                    <?php if (!empty($project['company_name'])): ?>
                        <div class="fw-semibold text-secondary small mb-1"><?php echo htmlspecialchars($project['company_name']); ?></div>
                    <?php endif; ?>
                    <div class="text-secondary small">
                        <div><i class="fa-solid fa-envelope me-1"></i> <?php echo htmlspecialchars($project['customer_email']); ?></div>
                        <div><i class="fa-solid fa-phone me-1"></i> <?php echo htmlspecialchars($project['whatsapp_number'] ?: $project['customer_phone']); ?></div>
                    </div>
                </div>

                <div class="party-box">
                    <div class="party-title"><i class="fa-solid fa-briefcase text-primary me-1"></i> Project Context</div>
                    <div class="party-name" style="font-size: 1rem;"><?php echo htmlspecialchars($project['project_title']); ?></div>
                    <div class="text-secondary small mb-1"><?php echo htmlspecialchars($project['project_type']); ?></div>
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <span class="badge bg-primary"><?php echo (int)$project['progress_percentage']; ?>% Progress</span>
                        <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($project['status']); ?></span>
                    </div>
                </div>
            </div>

            <!-- Itemized Milestone Breakdown Table -->
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th style="width: 45%;">Milestone Schedule Description</th>
                        <th class="text-center" style="width: 18%;">Status</th>
                        <th style="width: 22%;">Transaction Ref</th>
                        <th class="text-end" style="width: 15%;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Milestone 1: Advance Payment -->
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">1. Advance Project Deposit</div>
                            <div class="text-secondary small">Initial confirmation deposit before development kickoff.</div>
                        </td>
                        <td class="text-center">
                            <?php if ($isAdvancePaid): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i> Paid
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1">
                                    Pending
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace small text-secondary">
                            <?php echo htmlspecialchars($payments[0]['transaction_reference'] ?? ($isAdvancePaid ? 'VERIFIED-01' : '—')); ?>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            <?php echo formatCurrency($advanceAmt); ?>
                        </td>
                    </tr>

                    <!-- Milestone 2: 50% Milestone -->
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">2. Mid-Development Milestone (50%)</div>
                            <div class="text-secondary small">Core module completion, backend logic, and database scaffolding.</div>
                        </td>
                        <td class="text-center">
                            <?php if ($isMilestone2Paid || $isFullyPaid): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i> Paid
                                </span>
                            <?php elseif ($project['status'] === 'Second Payment Pending'): ?>
                                <span class="badge bg-warning text-dark px-2 py-1">
                                    <i class="fa-solid fa-bell me-1"></i> Due Now
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary px-2 py-1">Scheduled</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace small text-secondary">
                            <?php echo htmlspecialchars($payments[1]['transaction_reference'] ?? (($isMilestone2Paid || $isFullyPaid) ? 'VERIFIED-02' : '—')); ?>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            <?php echo formatCurrency($m2Amt); ?>
                        </td>
                    </tr>

                    <!-- Milestone 3: Final Payment -->
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">3. Final Settlement & Production Hosting</div>
                            <div class="text-secondary small">Client sign-off, live domain deployment, SSL security, and delivery.</div>
                        </td>
                        <td class="text-center">
                            <?php if ($isFullyPaid): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-check me-1"></i> Paid
                                </span>
                            <?php elseif ($project['status'] === 'Final Payment Pending'): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                    <i class="fa-solid fa-bell me-1"></i> Due Upon Review
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary px-2 py-1">On Completion</span>
                            <?php endif; ?>
                        </td>
                        <td class="font-monospace small text-secondary">
                            <?php echo htmlspecialchars($payments[2]['transaction_reference'] ?? ($isFullyPaid ? 'VERIFIED-03' : '—')); ?>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            <?php echo formatCurrency($finalAmt); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Financial Settlement Totals & Official PAID Stamp -->
            <div class="settlement-summary-grid">
                <!-- Left Column: Official Stamp or Milestone Notice -->
                <div class="stamp-container">
                    <?php if ($isFullyPaid): ?>
                        <!-- THE OFFICIAL PAID IN FULL RUBBER STAMP -->
                        <div class="paid-stamp-box stamp-<?php echo htmlspecialchars($invStampStyle); ?>">
                            <div class="paid-stamp-title">&#9733; <?php echo htmlspecialchars($invStampText); ?> &#9733;</div>
                            <div class="paid-stamp-sub">OFFICIALLY VERIFIED &amp; SETTLED</div>
                            <div class="paid-stamp-meta"><?php echo htmlspecialchars($invCompanyName); ?> &bull; <?php echo date('d M Y'); ?></div>
                        </div>
                    <?php elseif ($isMilestone2Paid): ?>
                        <div class="milestone-stage-seal">
                            <div class="fw-bold fs-6"><i class="fa-solid fa-certificate me-1"></i> 50% MILESTONE VERIFIED</div>
                            <div class="small mt-1">Second scheduled payment received and credited. Final balance due on delivery.</div>
                        </div>
                    <?php elseif ($isAdvancePaid): ?>
                        <div class="milestone-stage-seal" style="border-color: #059669; color: #065f46; background: #ecfdf5;">
                            <div class="fw-bold fs-6"><i class="fa-solid fa-circle-check me-1"></i> ADVANCE DEPOSIT CONFIRMED</div>
                            <div class="small mt-1">Project officially commenced. Next milestone due at 50% development.</div>
                        </div>
                    <?php else: ?>
                        <div class="text-secondary small text-center p-3 border rounded">
                            <i class="fa-solid fa-file-invoice-dollar fs-4 text-warning mb-2 d-block"></i>
                            Initial advance payment pending confirmation.
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Financial Breakdown -->
                <div class="totals-card">
                    <div class="total-row">
                        <span>Total Agreed Project Cost:</span>
                        <span class="fw-bold text-dark"><?php echo formatCurrency($totalCost); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Total Paid to Date:</span>
                        <span class="fw-bold text-success">- <?php echo formatCurrency($totalPaid); ?></span>
                    </div>
                    <div class="total-row highlight">
                        <span>Net Balance Due:</span>
                        <?php if ($balanceDue <= 0 && $totalPaid > 0): ?>
                            <span class="text-success"><i class="fa-solid fa-circle-check me-1"></i> Rs. 0.00</span>
                        <?php else: ?>
                            <span class="text-danger"><?php echo formatCurrency($balanceDue); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Customer Bank Deposit Coordinates (Shown when balance is due) -->
            <?php if ($balanceDue > 0): ?>
                <div class="bank-instruction-box">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-dark small text-uppercase">
                            <i class="fa-solid fa-building-columns text-primary me-1"></i> Customer Bank Transfer Coordinates
                        </span>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 small">Payment Reference: <?php echo htmlspecialchars($project['job_id']); ?></span>
                    </div>
                    <div class="row g-2 text-dark small">
                        <div class="col-md-6">
                            <strong>Bank Name:</strong> <?php echo htmlspecialchars($bankName); ?><br>
                            <strong>Account Name:</strong> <?php echo htmlspecialchars($bankAccName); ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Account Number:</strong> <span class="font-monospace fw-bold text-primary"><?php echo htmlspecialchars($bankAccNum); ?></span><br>
                            <strong>Branch:</strong> <?php echo htmlspecialchars($bankBranch); ?>
                        </div>
                    </div>
                    <div class="text-secondary small mt-2">
                        <em>Please include Job ID <strong><?php echo htmlspecialchars($project['job_id']); ?></strong> as your deposit slip reference and upload your receipt on the project tracking page.</em>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footer: Legal Notes, Policy & Authorized Signatory -->
            <div class="invoice-footer">
                <div class="terms-text">
                    <strong>Invoice Terms &amp; Policies:</strong><br>
                    <?php echo nl2br(htmlspecialchars($invFooterNotes)); ?>
                </div>

                <div class="signatory-box">
                    <div class="digital-signature-seal"><?php echo htmlspecialchars($invCompanyName); ?></div>
                    <div class="text-secondary small">Authorized Finance Division</div>
                    <div class="text-muted" style="font-size: 0.65rem;">Electronic Verification Record</div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>

