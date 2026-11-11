<?php
/**
 * Chandila Web Development – Client Assistant & Order Tracking Chatbot API
 * Handles natural language inquiries, general support, and real-time order/project tracking.
 */

declare(strict_types=1);

// Enable session for conversation state & phone verification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Safe Currency Helper (protect against PHP 8.2+ intl CURRENCY_SYMBOL = 262145)
function safeChatCurrency(float $amount): string {
    if (function_exists('formatCurrency')) {
        return formatCurrency($amount);
    }
    $sym = function_exists('getCurrencySymbol') ? getCurrencySymbol() : (defined('APP_CURRENCY_SYMBOL') ? APP_CURRENCY_SYMBOL : 'Rs.');
    return $sym . ' ' . number_format($amount, 2);
}

// Ensure database connection
$pdo = getDB();
if (!$pdo) {
    echo json_encode([
        'success' => false,
        'reply'   => "Our database is momentarily undergoing routine maintenance. Please feel free to reach our Customer Care directly on WhatsApp at [076 843 3340](https://wa.me/9476843340)."
    ]);
    exit;
}

// Receive payload (JSON or Form POST)
$rawInput = file_get_contents('php://input');
$inputData = [];
if (!empty($rawInput)) {
    $inputData = json_decode($rawInput, true) ?? [];
}
if (empty($inputData)) {
    $inputData = $_POST;
}

$action  = trim($inputData['action'] ?? 'message');
$message = trim($inputData['message'] ?? '');
$jobIdInput = trim($inputData['job_id'] ?? '');
$phoneInput = trim($inputData['phone'] ?? '');

// Helper to normalize phone numbers
function normalizePhoneNumber(string $num): string {
    $cleaned = preg_replace('/[^0-9]/', '', $num);
    if (str_starts_with($cleaned, '0094')) {
        $cleaned = substr($cleaned, 4);
    } elseif (str_starts_with($cleaned, '94')) {
        $cleaned = substr($cleaned, 2);
    } elseif (str_starts_with($cleaned, '0')) {
        $cleaned = substr($cleaned, 1);
    }
    return $cleaned;
}

// Initial Session Setup
if (!isset($_SESSION['chatbot_context'])) {
    $_SESSION['chatbot_context'] = [
        'verified_phone'  => null,
        'last_job_id'     => null,
        'pending_job_id'  => null,
        'interaction_count' => 0
    ];
}
$_SESSION['chatbot_context']['interaction_count']++;

// -------------------------------------------------------------------------
// Action: INIT (Returns Welcome Greeting & Initial Suggested Chips)
// -------------------------------------------------------------------------
if ($action === 'init') {
    echo json_encode([
        'success' => true,
        'reply'   => "Hello! 👋 Welcome to **Chandila Web Development**.\n\nI am your Virtual Assistant. I can help you **track your project progress in real-time**, explore our web engineering services, explain our **3-Stage Milestone Escrow**, or connect you with our Customer Care team.\n\nHow can I help you today?",
        'quick_chips' => [
            ['label' => '🔍 Track My Project / Order', 'action' => 'track_prompt'],
            ['label' => '💼 Web Development Services', 'action' => 'services'],
            ['label' => '💰 Pricing & 3-Stage Escrow', 'action' => 'pricing'],
            ['label' => '⏱️ Customer Care Hours', 'action' => 'hours'],
            ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp'],
            ['label' => '📝 Request Free Quote', 'action' => 'quote']
        ]
    ]);
    exit;
}

// If explicit action was requested via quick chip
if ($action === 'quick_action') {
    $cmd = strtolower($inputData['command'] ?? '');
    handleQuickCommand($cmd, $pdo);
    exit;
}

// -------------------------------------------------------------------------
// Normal Message Handling & Intent Detection
// -------------------------------------------------------------------------
if (empty($message) && empty($jobIdInput) && empty($phoneInput)) {
    echo json_encode([
        'success' => true,
        'reply'   => "Please type a message, ask a question, or provide your **Job ID** (e.g. `CHW-2026-0001`) to track your project progress.",
        'quick_chips' => [
            ['label' => '🔍 Track My Project', 'action' => 'track_prompt'],
            ['label' => '💼 Our Services', 'action' => 'services'],
            ['label' => '💰 Milestone Escrow', 'action' => 'pricing'],
            ['label' => '💬 WhatsApp Support', 'action' => 'whatsapp']
        ]
    ]);
    exit;
}

// Combined search text
$searchText = $message . ' ' . $jobIdInput . ' ' . $phoneInput;

// 1. Detect Job ID pattern (e.g., CHW-2026-0001, CHW-2026-0002, or partial CHW-...)
$detectedJobId = null;
if (!empty($jobIdInput)) {
    $detectedJobId = strtoupper($jobIdInput);
} elseif (preg_match('/(CHW-\d{4}-\d{3,5})/i', $searchText, $matches)) {
    $detectedJobId = strtoupper($matches[1]);
} elseif (preg_match('/(CHW-[A-Z0-9\-]+)/i', $searchText, $matches)) {
    $detectedJobId = strtoupper($matches[1]);
} elseif (preg_match('/\b(2026-\d{4})\b/', $searchText, $matches)) {
    $detectedJobId = 'CHW-' . $matches[1];
}

// 2. Detect Phone Number pattern
$detectedPhone = null;
if (!empty($phoneInput)) {
    $detectedPhone = normalizePhoneNumber($phoneInput);
} elseif (preg_match('/(?:\+?94|0)?(7[0-9]{8})\b/', $searchText, $matches)) {
    $detectedPhone = normalizePhoneNumber($matches[0]);
} elseif (preg_match('/\b(0\d{9})\b/', $searchText, $matches)) {
    $detectedPhone = normalizePhoneNumber($matches[1]);
}

// If the user previously had a pending Job ID and now only entered a phone number:
if (!$detectedJobId && !empty($_SESSION['chatbot_context']['pending_job_id']) && !empty($detectedPhone)) {
    $detectedJobId = $_SESSION['chatbot_context']['pending_job_id'];
}

// 3. Process Tracking Intent if Job ID or Phone is provided or tracking keywords are used
$lowerMsg = strtolower($message);
$isTrackingIntent = (
    !empty($detectedJobId) || 
    !empty($detectedPhone) ||
    str_contains($lowerMsg, 'track') ||
    str_contains($lowerMsg, 'order status') ||
    str_contains($lowerMsg, 'project status') ||
    str_contains($lowerMsg, 'my project') ||
    str_contains($lowerMsg, 'my order') ||
    str_contains($lowerMsg, 'where is my') ||
    str_contains($lowerMsg, 'job id') ||
    str_contains($lowerMsg, 'job status') ||
    str_contains($lowerMsg, 'progress')
);

if ($isTrackingIntent) {
    handleTrackingLookup($detectedJobId, $detectedPhone, $searchText, $pdo);
    exit;
}

// 4. General Intent Router
handleGeneralInquiries($lowerMsg, $pdo);

// =========================================================================
// HELPER FUNCTIONS
// =========================================================================

/**
 * Handle Order / Project Tracking Inquiries
 */
function handleTrackingLookup(?string $jobId, ?string $phone, string $rawText, PDO $pdo): void {
    // If no Job ID and no phone was detected, ask the user for details
    if (empty($jobId) && empty($phone)) {
        echo json_encode([
            'success' => true,
            'intent'  => 'track_prompt',
            'reply'   => "I'd be glad to track your project in real-time! 🚀\n\nPlease provide your **Job ID** (e.g. `CHW-2026-0001`) or your **registered WhatsApp / phone number** (e.g. `077 112 2334`).",
            'quick_chips' => [
                ['label' => 'Demo: CHW-2026-0001', 'action' => 'send_text', 'text' => 'CHW-2026-0001'],
                ['label' => 'Demo: CHW-2026-0002', 'action' => 'send_text', 'text' => 'CHW-2026-0002'],
                ['label' => 'Demo: CHW-2026-0003', 'action' => 'send_text', 'text' => 'CHW-2026-0003'],
                ['label' => '💬 Ask Customer Care', 'action' => 'whatsapp']
            ]
        ]);
        return;
    }

    $verifiedPhoneInSession = $_SESSION['chatbot_context']['verified_phone'] ?? null;
    $currentPhone = $phone ?: $verifiedPhoneInSession;

    // Case A: Job ID is provided
    if (!empty($jobId)) {
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   c.full_name AS customer_name, 
                   c.company_name, 
                   c.phone AS customer_phone, 
                   c.whatsapp_number,
                   u.full_name AS dev_name,
                   u.role AS dev_role
            FROM projects p
            JOIN customers c ON p.customer_id = c.id
            LEFT JOIN users u ON p.assigned_developer_id = u.id
            WHERE UPPER(p.job_id) = UPPER(?)
            LIMIT 1
        ");
        $stmt->execute([$jobId]);
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            echo json_encode([
                'success' => true,
                'found'   => false,
                'reply'   => "⚠️ We couldn't locate an active project with Job ID **{$jobId}**.\n\nPlease double-check the ID printed on your confirmation email, WhatsApp notice, or official invoice. You can also search using your **registered phone number** or contact our Customer Care team directly.",
                'quick_chips' => [
                    ['label' => '🔍 Try Phone Search', 'action' => 'track_prompt'],
                    ['label' => '💬 Chat on WhatsApp (076 843 3340)', 'action' => 'whatsapp'],
                    ['label' => '🌐 Open Public Tracker', 'action' => 'open_link', 'url' => BASE_URL . '/track_project.php']
                ]
            ]);
            return;
        }

        // Check Phone Match
        $dbPhone = normalizePhoneNumber($project['customer_phone'] ?? '');
        $dbWa    = normalizePhoneNumber($project['whatsapp_number'] ?? '');
        $isPhoneVerified = false;

        if (!empty($currentPhone)) {
            $inputTail = substr($currentPhone, -7);
            if (strlen($inputTail) >= 6 && (str_contains($dbPhone, $inputTail) || str_contains($dbWa, $inputTail))) {
                $isPhoneVerified = true;
                $_SESSION['chatbot_context']['verified_phone'] = $currentPhone;
                $_SESSION['chatbot_context']['last_job_id']    = $project['job_id'];
                $_SESSION['chatbot_context']['pending_job_id'] = null;
            }
        }

        // Fetch latest progress log note
        $progStmt = $pdo->prepare("
            SELECT percentage, status_stage, description, created_at 
            FROM project_progress 
            WHERE project_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $progStmt->execute([$project['id']]);
        $latestProgress = $progStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch milestones summary
        $mileStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_milestones,
                SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed_milestones
            FROM project_milestones 
            WHERE project_id = ?
        ");
        $mileStmt->execute([$project['id']]);
        $mileSummary = $mileStmt->fetch(PDO::FETCH_ASSOC) ?: ['total_milestones' => 0, 'completed_milestones' => 0];

        $pct = (int)$project['progress_percentage'];
        $stageNumber = function_exists('getStageStepNumber') ? getStageStepNumber($project['status']) : 1;
        $formattedCompletion = !empty($project['expected_completion_date']) ? date('M d, Y', strtotime($project['expected_completion_date'])) : 'To Be Announced';

        // Prepare structured project card payload
        $cardData = [
            'job_id'              => $project['job_id'],
            'project_title'       => $project['project_title'],
            'project_type'        => $project['project_type'],
            'status'              => $project['status'],
            'stage_number'        => $stageNumber,
            'progress_percentage' => $pct,
            'expected_completion' => $formattedCompletion,
            'hosting_url'         => $project['hosting_url'],
            'developer_name'      => !empty($project['dev_name']) ? $project['dev_name'] : 'Senior Engineering Team',
            'latest_update'       => $latestProgress ? $latestProgress['description'] : 'Project milestones established.',
            'latest_update_date'  => $latestProgress ? date('M d, Y h:i A', strtotime($latestProgress['created_at'])) : null,
            'milestones_total'    => (int)($mileSummary['total_milestones'] ?? 0),
            'milestones_done'     => (int)($mileSummary['completed_milestones'] ?? 0),
            'is_verified'         => $isPhoneVerified,
            'tracker_url'         => BASE_URL . '/track_project.php?job_id=' . urlencode($project['job_id']) . ($isPhoneVerified && $currentPhone ? '&phone=' . urlencode($currentPhone) : ''),
            'invoice_url'         => BASE_URL . '/invoice.php?job_id=' . urlencode($project['job_id'])
        ];

        if ($isPhoneVerified) {
            $cardData['customer_name'] = $project['customer_name'];
            $cardData['company_name']  = $project['company_name'];
            $cardData['total_cost']    = safeChatCurrency((float)$project['total_cost']);
            $cardData['total_paid']    = safeChatCurrency((float)$project['total_paid']);
            $cardData['balance_due']   = safeChatCurrency((float)$project['balance_due']);

            $reply = "✅ **Identity Verified!** Here is the live status for **{$project['job_id']}**:\n\n"
                   . "📌 **{$project['project_title']}**\n"
                   . "• **Current Stage:** {$project['status']} (Stage {$stageNumber} of 13)\n"
                   . "• **Live Progress:** **{$pct}%** Completed\n"
                   . "• **Est. Delivery:** {$formattedCompletion}\n"
                   . "• **Financial Status:** Paid {$cardData['total_paid']} of {$cardData['total_cost']} (Balance: {$cardData['balance_due']})\n"
                   . "• **Lead Developer:** {$cardData['developer_name']}\n\n"
                   . "💡 *Latest Progress Update:* \"{$cardData['latest_update']}\"";

            $quickChips = [
                ['label' => '🌐 View Full Tracker', 'action' => 'open_link', 'url' => $cardData['tracker_url']],
                ['label' => '📄 View Official Invoice', 'action' => 'open_link', 'url' => $cardData['invoice_url']],
                ['label' => '💬 WhatsApp Project Lead', 'action' => 'whatsapp_job', 'job_id' => $project['job_id']],
                ['label' => '❓ Ask Question', 'action' => 'send_text', 'text' => 'When is the next milestone update?']
            ];
        } else {
            // Masked / Public preview
            $_SESSION['chatbot_context']['pending_job_id'] = $project['job_id'];
            $maskedCustomer = !empty($project['customer_name']) ? preg_replace('/(?<=.).(?=.*@)|(?<=\b\w)\w+/u', '***', $project['customer_name']) : 'Verified Client';

            $reply = "🔍 Found project **{$project['job_id']}** (*{$project['project_title']}*).\n\n"
                   . "• **Status:** {$project['status']} (**{$pct}%**)\n"
                   . "• **Target Delivery:** {$formattedCompletion}\n\n"
                   . "🔒 **Security Verification:** To unlock your full financial summary (advance payment, milestone escrow balance, and official invoice), please enter your **registered phone number or WhatsApp number**.";

            $quickChips = [
                ['label' => '📱 Enter Phone to Unlock', 'action' => 'focus_input'],
                ['label' => '🌐 Open Public Tracker', 'action' => 'open_link', 'url' => $cardData['tracker_url']],
                ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp_job', 'job_id' => $project['job_id']]
            ];
        }

        echo json_encode([
            'success'      => true,
            'found'        => true,
            'project_card' => $cardData,
            'reply'        => $reply,
            'quick_chips'  => $quickChips
        ]);
        return;
    }

    // Case B: Only Phone Number is provided (Search projects by phone)
    if (!empty($phone)) {
        $cleanTail = substr($phone, -7);
        $stmt = $pdo->prepare("
            SELECT p.id, p.job_id, p.project_title, p.project_type, p.status, p.progress_percentage,
                   p.total_cost, p.total_paid, p.balance_due, p.expected_completion_date,
                   c.full_name AS customer_name, c.company_name, c.phone, c.whatsapp_number
            FROM projects p
            JOIN customers c ON p.customer_id = c.id
            WHERE c.phone LIKE ? OR c.whatsapp_number LIKE ?
            ORDER BY p.id DESC
            LIMIT 5
        ");
        $likePattern = '%' . $cleanTail . '%';
        $stmt->execute([$likePattern, $likePattern]);
        $matchedProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($matchedProjects)) {
            echo json_encode([
                'success' => true,
                'found'   => false,
                'reply'   => "⚠️ We couldn't find an active project registered under phone number **{$phone}**.\n\nPlease check your number or provide your **Job ID** (e.g. `CHW-2026-0001`). If you just placed an order, our Customer Care team may still be finalizing your setup.",
                'quick_chips' => [
                    ['label' => '💬 Message Customer Care on WhatsApp', 'action' => 'whatsapp'],
                    ['label' => '🌐 Open Track Project Page', 'action' => 'open_link', 'url' => BASE_URL . '/track_project.php']
                ]
            ]);
            return;
        }

        // If exactly 1 project matched, show verified project directly!
        if (count($matchedProjects) === 1) {
            $_SESSION['chatbot_context']['verified_phone'] = $phone;
            handleTrackingLookup($matchedProjects[0]['job_id'], $phone, $rawText, $pdo);
            return;
        }

        // If multiple projects matched, present a choice list
        $_SESSION['chatbot_context']['verified_phone'] = $phone;
        $reply = "✅ Found **" . count($matchedProjects) . " projects** registered under your contact number! Please select which project you would like to track:";
        $chips = [];
        foreach ($matchedProjects as $proj) {
            $chips[] = [
                'label'  => "{$proj['job_id']} – {$proj['project_title']} ({$proj['progress_percentage']}%)",
                'action' => 'send_text',
                'text'   => $proj['job_id']
            ];
        }
        $chips[] = ['label' => '💬 Speak with Customer Care', 'action' => 'whatsapp'];

        echo json_encode([
            'success'     => true,
            'found'       => true,
            'is_multiple' => true,
            'reply'       => $reply,
            'quick_chips' => $chips
        ]);
        return;
    }
}

/**
 * Handle Quick Action Commands from interactive chips
 */
function handleQuickCommand(string $cmd, PDO $pdo): void {
    switch ($cmd) {
        case 'track_prompt':
            echo json_encode([
                'success' => true,
                'reply'   => "Please type your **Job ID** (e.g. `CHW-2026-0001`) or your **registered phone / WhatsApp number** into the chat below. I will fetch your live progress immediately!",
                'quick_chips' => [
                    ['label' => 'CHW-2026-0001 (Aqua Super)', 'action' => 'send_text', 'text' => 'CHW-2026-0001'],
                    ['label' => 'CHW-2026-0002 (Apex Academy)', 'action' => 'send_text', 'text' => 'CHW-2026-0002'],
                    ['label' => 'CHW-2026-0003 (Lesi POS)', 'action' => 'send_text', 'text' => 'CHW-2026-0003'],
                    ['label' => '💬 Contact Care on WhatsApp', 'action' => 'whatsapp']
                ]
            ]);
            break;

        case 'services':
            echo json_encode([
                'success' => true,
                'reply'   => "🛠️ **Chandila Web Development Engineering Solutions:**\n\n"
                           . "1. **Tailored Corporate & Business Websites** – Modern, lightning-fast responsive sites built with SEO, schema, and mobile-first layouts.\n"
                           . "2. **E-Commerce & Online Stores** – High-conversion carts, multi-currency support, PayHere / Stripe gateway, automated invoices.\n"
                           . "3. **Custom Cloud Web Portals** – Robust ERP, inventory, customer CRM, and staff dispatch systems.\n"
                           . "4. **Student Management & LMS** – Course registration, QR attendance tracking, marksheets, parent SMS/WhatsApp notices.\n"
                           . "5. **Booking & Appointment Engines** – Real-time availability calendars, slot reservation, and automated WhatsApp reminders.\n"
                           . "6. **Cloud Hosting & Maintenance** – SSL certificates, high-speed SSD servers, 99.9% uptime, daily cloud backups.\n\n"
                           . "Would you like a tailored quote for your project?",
                'quick_chips' => [
                    ['label' => '📝 Request Free Quote', 'action' => 'quote'],
                    ['label' => '💰 Pricing & 3-Stage Escrow', 'action' => 'pricing'],
                    ['label' => '🌟 View 500+ Portfolio', 'action' => 'open_link', 'url' => BASE_URL . '/portfolio.php'],
                    ['label' => '💬 Consult on WhatsApp', 'action' => 'whatsapp']
                ]
            ]);
            break;

        case 'pricing':
            echo json_encode([
                'success' => true,
                'reply'   => "💰 **Transparent Pricing & 3-Stage Milestone Escrow Protection:**\n\n"
                           . "We eliminate client risk through our industry-leading **3-Stage Milestone Escrow Guarantee**:\n\n"
                           . "• **Stage 1 (Advance / Kickoff ~30%)**: Project confirmation, architectural blueprints, database schema & wireframe approval.\n"
                           . "• **Stage 2 (Mid-Way Demo ~40-50%)**: You review a live clickable staging demo. Second payment is released only after you test and approve core features!\n"
                           . "• **Stage 3 (Final Settlement & Handover)**: Released upon complete production deployment, DNS configuration, and permanent source code transfer.\n\n"
                           . "💼 Packages start from **Rs. 45,000** for Starter Business Sites up to custom enterprise portals.",
                'quick_chips' => [
                    ['label' => '📝 Request a Custom Quote', 'action' => 'quote'],
                    ['label' => '🔍 Track Live Payment', 'action' => 'track_prompt'],
                    ['label' => '💬 Discuss Budget on WhatsApp', 'action' => 'whatsapp']
                ]
            ]);
            break;

        case 'hours':
            echo json_encode([
                'success' => true,
                'reply'   => "⏱️ **Customer Care & Operational Hours:**\n\n"
                           . "• **Office Support:** Monday – Saturday: 9:00 AM – 6:00 PM (IST)\n"
                           . "• **Sunday & Public Holidays:** On-call emergency deployment support.\n"
                           . "• **Customer Care Shift:** Dedicated care staff continuously review incoming job requests, verify payment slips, and assign lead developers.\n"
                           . "• **WhatsApp Hotline (076 843 3340):** Monitored 24/7 for project updates and urgent assistance.",
                'quick_chips' => [
                    ['label' => '💬 Open WhatsApp Support', 'action' => 'whatsapp'],
                    ['label' => '📞 Call Office (076 843 3340)', 'action' => 'call'],
                    ['label' => '📍 Office Location', 'action' => 'contact']
                ]
            ]);
            break;

        case 'quote':
            echo json_encode([
                'success' => true,
                'reply'   => "🚀 **Ready to bring your project to life?**\n\n"
                           . "You can submit your project requirements online in under 2 minutes through our instant request form, or chat directly with our Lead Architect on WhatsApp.",
                'quick_chips' => [
                    ['label' => '📝 Fill Request Form', 'action' => 'open_link', 'url' => BASE_URL . '/request_project.php'],
                    ['label' => '💬 Chat on WhatsApp (Instant Reply)', 'action' => 'whatsapp'],
                    ['label' => '💼 Explore Services', 'action' => 'services']
                ]
            ]);
            break;

        case 'contact':
            echo json_encode([
                'success' => true,
                'reply'   => "📍 **Chandila Web Development Contact Details:**\n\n"
                           . "• **Headquarters:** No. 45, Galle Road, Colombo 03, Sri Lanka.\n"
                           . "• **Telephone:** [076 843 3340](tel:0768433340)\n"
                           . "• **WhatsApp Hotline:** [076 843 3340](https://wa.me/9476843340)\n"
                           . "• **Email:** [contact@chandilaweb.com](mailto:contact@chandilaweb.com)\n"
                           . "• **Billing:** [billing@chandilaweb.com](mailto:billing@chandilaweb.com)\n\n"
                           . "Feel free to drop by our Colombo office or schedule a Google Meet / Zoom session!",
                'quick_chips' => [
                    ['label' => '💬 Chat on WhatsApp', 'action' => 'whatsapp'],
                    ['label' => '📝 Request Free Quote', 'action' => 'quote'],
                    ['label' => '🔍 Track Order', 'action' => 'track_prompt']
                ]
            ]);
            break;

        case 'whatsapp':
            echo json_encode([
                'success' => true,
                'reply'   => "Connecting you with our Customer Care team on WhatsApp... Click the button below to start chatting directly!",
                'quick_chips' => [
                    ['label' => '💬 Open WhatsApp (076 843 3340)', 'action' => 'open_link', 'url' => 'https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20am%20chatting%20from%20the%20website%20and%20would%20like%20assistance.']
                ]
            ]);
            break;

        default:
            echo json_encode([
                'success' => true,
                'reply'   => "How else may I assist you with your web project?",
                'quick_chips' => [
                    ['label' => '🔍 Track Order', 'action' => 'track_prompt'],
                    ['label' => '💼 Our Services', 'action' => 'services'],
                    ['label' => '💰 Milestone Escrow', 'action' => 'pricing'],
                    ['label' => '💬 WhatsApp Support', 'action' => 'whatsapp']
                ]
            ]);
            break;
    }
}

/**
 * Handle Natural Language Questions & Chat Inquiries
 */
function handleGeneralInquiries(string $text, PDO $pdo): void {
    // 1. Greetings
    if (preg_match('/\b(hi|hello|hey|good morning|good afternoon|good evening|ayubowan|vanakkam)\b/i', $text)) {
        echo json_encode([
            'success' => true,
            'reply'   => "Hello! 👋 Great to have you here at **Chandila Web Development**.\n\nI can answer questions regarding our web development solutions, calculate pricing estimates, or **track your live project stage** using your Job ID or phone number. What would you like to explore?",
            'quick_chips' => [
                ['label' => '🔍 Track My Project / Order', 'action' => 'track_prompt'],
                ['label' => '💼 Web Solutions', 'action' => 'services'],
                ['label' => '💰 Milestone Escrow & Pricing', 'action' => 'pricing'],
                ['label' => '⏱️ Customer Care Hours', 'action' => 'hours']
            ]
        ]);
        return;
    }

    // 2. Who are you / About
    if (str_contains($text, 'who are you') || str_contains($text, 'what is this') || str_contains($text, 'about')) {
        echo json_encode([
            'success' => true,
            'reply'   => "I am the **Chandila Web Virtual Assistant**! 🤖\n\nChandila Web Development is Sri Lanka's leading digital engineering agency with over **500+ delivered projects**. We specialize in high-performance corporate websites, e-commerce stores, custom cloud portals, and Student LMS platforms with transparent milestone escrow.",
            'quick_chips' => [
                ['label' => '🌟 View 500+ Projects', 'action' => 'open_link', 'url' => BASE_URL . '/portfolio.php'],
                ['label' => '🔍 Track My Project', 'action' => 'track_prompt'],
                ['label' => '💬 WhatsApp Us', 'action' => 'whatsapp']
            ]
        ]);
        return;
    }

    // 3. Pricing / Cost / Budget
    if (str_contains($text, 'price') || str_contains($text, 'cost') || str_contains($text, 'fee') || str_contains($text, 'budget') || str_contains($text, 'rate') || str_contains($text, 'how much')) {
        handleQuickCommand('pricing', $pdo);
        return;
    }

    // 4. Services / Tech Stack / Features
    if (str_contains($text, 'service') || str_contains($text, 'ecommerce') || str_contains($text, 'website') || str_contains($text, 'portal') || str_contains($text, 'lms') || str_contains($text, 'pos') || str_contains($text, 'build')) {
        handleQuickCommand('services', $pdo);
        return;
    }

    // 5. Working Hours / Shift / Care Team
    if (str_contains($text, 'hour') || str_contains($text, 'time') || str_contains($text, 'open') || str_contains($text, 'shift') || str_contains($text, 'available') || str_contains($text, 'support')) {
        handleQuickCommand('hours', $pdo);
        return;
    }

    // 6. Contact / Address / Phone / WhatsApp
    if (str_contains($text, 'contact') || str_contains($text, 'phone') || str_contains($text, 'call') || str_contains($text, 'address') || str_contains($text, 'location') || str_contains($text, 'office') || str_contains($text, 'email')) {
        handleQuickCommand('contact', $pdo);
        return;
    }

    // 7. Human Support / WhatsApp
    if (str_contains($text, 'human') || str_contains($text, 'agent') || str_contains($text, 'person') || str_contains($text, 'real person') || str_contains($text, 'whatsapp') || str_contains($text, 'speak')) {
        echo json_encode([
            'success' => true,
            'reply'   => "Our Customer Care and Engineering team is standing by! You can connect with a live representative on WhatsApp immediately:",
            'quick_chips' => [
                ['label' => '💬 Chat on WhatsApp (076 843 3340)', 'action' => 'open_link', 'url' => 'https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Customer%20Care,%20I%20would%20like%20to%20speak%20with%20a%20human%20representative.'],
                ['label' => '📞 Call Office Directly', 'action' => 'open_link', 'url' => 'tel:0768433340']
            ]
        ]);
        return;
    }

    // 8. Thank you / Bye
    if (preg_match('/\b(thank you|thanks|thx|bye|goodbye|see you)\b/i', $text)) {
        echo json_encode([
            'success' => true,
            'reply'   => "You're very welcome! 😊 Thank you for choosing **Chandila Web Development**. If you need anything else or want to track your project progress anytime, I'm always right here!",
            'quick_chips' => [
                ['label' => '🔍 Track Another Order', 'action' => 'track_prompt'],
                ['label' => '💬 WhatsApp Care Team', 'action' => 'whatsapp']
            ]
        ]);
        return;
    }

    // 9. Default Fallback with Smart Chips
    echo json_encode([
        'success' => true,
        'reply'   => "I understand you're inquiring about \"**" . htmlspecialchars(substr($text, 0, 80)) . "**\".\n\nCould you please select what you'd like to do, or provide your **Job ID** (e.g. `CHW-2026-0001`) to track your project?",
        'quick_chips' => [
            ['label' => '🔍 Track My Project / Order', 'action' => 'track_prompt'],
            ['label' => '💼 Web Development Services', 'action' => 'services'],
            ['label' => '💰 Pricing & 3-Stage Escrow', 'action' => 'pricing'],
            ['label' => '💬 Chat with Live Customer Care', 'action' => 'whatsapp'],
            ['label' => '📝 Request Free Quote', 'action' => 'quote']
        ]
    ]);
}

