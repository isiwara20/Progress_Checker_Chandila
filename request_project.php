<?php
$pageTitle = 'Request a Project – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/whatsapp_config.php';

$pdo = getDB();
$submissionSuccess = false;
$generatedJobId = '';
$customerName = '';
$customerPhone = '';
$errorMessage = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_project') {
    // CSRF verification
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = "Security validation failed. Please reload the page and try again.";
    } else {
        // Collect & Sanitize Inputs
        $fullName               = trim($_POST['full_name'] ?? '');
        $companyName            = trim($_POST['company_name'] ?? '');
        $email                  = trim($_POST['email'] ?? '');
        $phone                  = trim($_POST['phone'] ?? '');
        $projectType            = trim($_POST['project_type'] ?? 'Business Website');
        $projectTitle           = trim($_POST['project_title'] ?? '');
        $description            = trim($_POST['description'] ?? '');
        $requiredFeatures       = trim($_POST['required_features'] ?? '');
        $referenceUrl           = trim($_POST['reference_url'] ?? '');
        $expectedBudget         = trim($_POST['expected_budget'] ?? '');
        $expectedCompletionDate = !empty($_POST['expected_completion_date']) ? $_POST['expected_completion_date'] : null;
        $additionalRequirements = trim($_POST['additional_requirements'] ?? '');

        // Validation
        if (empty($fullName) || empty($email) || empty($phone) || empty($projectTitle) || empty($description) || empty($requiredFeatures)) {
            $errorMessage = "Please fill in all required fields marked with an asterisk (*).";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMessage = "Please enter a valid email address.";
        } else {
            try {
                $pdo->beginTransaction();

                // 1. Create or Find Customer
                $findCustStmt = $pdo->prepare("SELECT id FROM customers WHERE email = ? OR phone = ? LIMIT 1");
                $findCustStmt->execute([$email, $phone]);
                $existingCustId = $findCustStmt->fetchColumn();

                if ($existingCustId) {
                    $customerId = (int)$existingCustId;
                    // Update latest company or whatsapp if provided
                    $upCust = $pdo->prepare("UPDATE customers SET full_name = ?, company_name = ?, whatsapp_number = ? WHERE id = ?");
                    $upCust->execute([$fullName, $companyName, $phone, $customerId]);
                } else {
                    $insCust = $pdo->prepare("
                        INSERT INTO customers (full_name, company_name, email, phone, whatsapp_number, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $insCust->execute([$fullName, $companyName, $email, $phone, $phone]);
                    $customerId = (int)$pdo->lastInsertId();
                }

                // 2. Generate Guaranteed Unique Job ID
                $jobId = generateJobId($pdo);

                // 3. Insert Project
                $insProj = $pdo->prepare("
                    INSERT INTO projects (
                        job_id, customer_id, project_title, project_type, description,
                        required_features, reference_url, expected_budget, expected_completion_date,
                        additional_requirements, status, progress_percentage, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, ?, ?,
                        ?, 'Request Submitted', 0, NOW()
                    )
                ");
                $insProj->execute([
                    $jobId,
                    $customerId,
                    $projectTitle,
                    $projectType,
                    $description,
                    $requiredFeatures,
                    $referenceUrl ?: null,
                    $expectedBudget ?: null,
                    $expectedCompletionDate,
                    $additionalRequirements ?: null
                ]);
                $projectId = (int)$pdo->lastInsertId();

                // 4. Handle Secure File Attachment (Field 13)
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadRes = handleSecureUpload($_FILES['attachment'], $projectId, 'customer');
                    if (!$uploadRes['success']) {
                        // Soft warning or log
                        error_log("Attachment upload issue: " . $uploadRes['message']);
                    }
                }

                // 5. Log Activity
                logActivity(
                    $pdo,
                    $jobId,
                    $projectId,
                    'Project Submitted',
                    "Customer '{$fullName}' submitted project requirement for '{$projectTitle}'.",
                    $fullName
                );

                // 6. Send Automatic Multi-Channel Confirmation (WhatsApp & Email Event 1)
                $waResult = WhatsAppService::send(
                    $pdo,
                    $phone,
                    'Project Request Submitted',
                    [
                        'job_id'         => $jobId,
                        'customer_name'  => $fullName,
                        'customer_email' => $email,
                        'project_title'  => $projectTitle,
                        'project_type'   => $projectType,
                        'expected_budget'=> $expectedBudget
                    ],
                    $projectId,
                    $jobId
                );

                $pdo->commit();

                $submissionSuccess = true;
                $generatedJobId = $jobId;
                $customerName = $fullName;
                $customerPhone = $phone;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errorMessage = "An error occurred while saving your project request: " . $e->getMessage();
            }
        }
    }
}
?>

<div class="py-5" style="background: radial-gradient(circle at 50% 10%, rgba(245, 158, 11, 0.05) 0%, transparent 60%), #f8fafc;">
    <div class="container py-4">

        <?php if ($submissionSuccess): ?>
            <!-- Success Modal / Card -->
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="chandila-card p-4 p-md-5 text-center border-success" style="border-width: 2px;">
                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-4" 
                             style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.15); border: 2px solid #10b981;">
                            <i class="fa-solid fa-check fa-3x text-success"></i>
                        </div>

                        <span class="badge bg-success px-3 py-2 fs-6 mb-3">Project Request Successfully Received!</span>
                        <h2 class="text-dark fw-bold mb-3">Thank You, <?php echo htmlspecialchars($customerName); ?>!</h2>
                        <p class="text-secondary mx-auto mb-4" style="max-width: 580px;">
                            Your project specifications have been securely recorded into our database. Our project management and technical review team will analyze your requirements and reach out shortly.
                        </p>

                        <!-- Generated Job ID Box -->
                        <div class="p-4 rounded-3 mb-4 mx-auto bg-white" style="border: 2px dashed #d97706; max-width: 480px; box-shadow: 0 4px 15px rgba(217,119,6,0.08);">
                            <div class="text-secondary small text-uppercase fw-semibold mb-1">Your Official Project Tracking ID</div>
                            <div class="display-6 fw-bold text-dark mb-2" id="displayJobId"><?php echo htmlspecialchars($generatedJobId); ?></div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-copy-job-id" data-job-id="<?php echo htmlspecialchars($generatedJobId); ?>">
                                <i class="fa-regular fa-copy me-1"></i> Copy Job ID
                            </button>
                        </div>

                        <!-- WhatsApp Notification Notice -->
                        <div class="alert alert-success bg-success bg-opacity-10 border-success text-start mx-auto mb-4" style="max-width: 600px;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fa-brands fa-whatsapp fa-2x text-success"></i>
                                <div>
                                    <strong class="text-dark">WhatsApp Confirmation Dispatched</strong>
                                    <div class="small text-secondary">
                                        An automated confirmation message with your Job ID (<strong><?php echo htmlspecialchars($generatedJobId); ?></strong>) was sent to <strong><?php echo htmlspecialchars($customerPhone); ?></strong>.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex flex-wrap justify-content-center gap-3">
                            <a href="<?php echo BASE_URL; ?>/track_project.php?job_id=<?php echo urlencode($generatedJobId); ?>&phone=<?php echo urlencode($customerPhone); ?>" class="btn btn-gold btn-lg">
                                <i class="fa-solid fa-crosshairs me-2"></i> Track Project Live
                            </a>
                            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fa-solid fa-house me-2"></i> Return Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <?php else: ?>

            <!-- Page Title -->
            <div class="text-center max-w-700 mx-auto mb-5">
                <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning px-3 py-2 mb-2 fw-bold anim-fade-down">
                    <i class="fa-solid fa-trophy me-1"></i> START YOUR PROJECT
                </span>
                <h1 class="text-dark fw-bold display-5 anim-fade-up anim-delay-1">Request a Web Development Project</h1>
                <p class="text-secondary fs-5 anim-fade-up anim-delay-2">
                    Please provide detailed specifications for your web application. You will receive an official Job ID to monitor live progress and payment milestones.
                </p>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="row justify-content-center mb-4">
                    <div class="col-lg-10">
                        <div class="alert alert-danger bg-danger bg-opacity-10 border-danger text-danger">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- The 13-Field Project Request Form -->
            <div class="row justify-content-center anim-fade-up anim-delay-3">
                <div class="col-lg-10">
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data" class="chandila-card p-4 p-md-5">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="submit_project">

                        <!-- Section 1: Customer Contact Details -->
                        <div class="border-bottom border-secondary border-opacity-15 pb-3 mb-4">
                            <h4 class="text-dark mb-1"><i class="fa-solid fa-user-circle text-primary me-2"></i> 1. Contact & Business Information</h4>
                            <p class="text-secondary small mb-0">Used for official job assignment, notifications, and client portal verification.</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Field 1: Customer Full Name -->
                            <div class="col-md-6">
                                <label for="full_name" class="form-label text-dark small fw-semibold">
                                    Customer Full Name <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-chandila" id="full_name" name="full_name" placeholder="e.g. Kasun Perera" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>

                            <!-- Field 2: Company / Business Name (Optional) -->
                            <div class="col-md-6">
                                <label for="company_name" class="form-label text-dark small fw-semibold">
                                    Company / Business Name <span class="text-secondary">(Optional)</span>
                                </label>
                                <input type="text" class="form-control form-control-chandila" id="company_name" name="company_name" placeholder="e.g. Ceylon Logistics (Pvt) Ltd" value="<?php echo htmlspecialchars($_POST['company_name'] ?? ''); ?>">
                            </div>

                            <!-- Field 3: Email Address -->
                            <div class="col-md-6">
                                <label for="email" class="form-label text-dark small fw-semibold">
                                    Email Address <span class="text-danger">*</span>
                                </label>
                                <input type="email" class="form-control form-control-chandila" id="email" name="email" placeholder="e.g. kasun@example.lk" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>

                            <!-- Field 4: WhatsApp / Contact Number -->
                            <div class="col-md-6">
                                <label for="phone" class="form-label text-dark small fw-semibold">
                                    WhatsApp / Contact Number <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-secondary border-opacity-25 text-success"><i class="fa-brands fa-whatsapp"></i></span>
                                    <input type="text" class="form-control form-control-chandila" id="phone" name="phone" placeholder="e.g. 0768433340 or +9476843340" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="form-text text-secondary small">Important: Automated Job ID & milestone notifications are sent to this WhatsApp number.</div>
                            </div>
                        </div>

                        <!-- Section 2: Project Specifications -->
                        <div class="border-bottom border-secondary border-opacity-15 pb-3 mb-4 mt-5">
                            <h4 class="text-dark mb-1"><i class="fa-solid fa-diagram-project text-primary me-2"></i> 2. Technical & Scope Details</h4>
                            <p class="text-secondary small mb-0">Help our development team assess architectural requirements and estimate schedules.</p>
                        </div>

                        <div class="row g-3 mb-4">
                            <!-- Field 5: Project Type -->
                            <div class="col-md-6">
                                <label for="project_type" class="form-label text-dark small fw-semibold">
                                    Project Type <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-chandila" id="project_type" name="project_type" required>
                                    <?php 
                                    $selectedType = $_GET['type'] ?? ($_POST['project_type'] ?? 'Business Website');
                                    foreach (PROJECT_TYPES as $type): 
                                    ?>
                                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($selectedType === $type) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Field 6: Project Title -->
                            <div class="col-md-6">
                                <label for="project_title" class="form-label text-dark small fw-semibold">
                                    Project Title <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control form-control-chandila" id="project_title" name="project_title" placeholder="e.g. Multi-Vendor E-Commerce Store with Payment Gateway" value="<?php echo htmlspecialchars($_POST['project_title'] ?? ''); ?>" required>
                            </div>

                            <!-- Field 7: Project Description -->
                            <div class="col-12">
                                <label for="description" class="form-label text-dark small fw-semibold">
                                    Project Description & Objectives <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control form-control-chandila" id="description" name="description" rows="4" placeholder="Describe the purpose, target audience, and primary workflow of the system..." required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <!-- Field 8: Required Features -->
                            <div class="col-12">
                                <label for="required_features" class="form-label text-dark small fw-semibold">
                                    Key Required Features & Modules <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control form-control-chandila" id="required_features" name="required_features" rows="4" placeholder="List essential features: e.g. User login, Stripe & PayHere checkout, admin analytics, automated email receipts, PDF invoices..." required><?php echo htmlspecialchars($_POST['required_features'] ?? ''); ?></textarea>
                            </div>

                            <!-- Field 9: Reference Website URL (Optional) -->
                            <div class="col-md-6">
                                <label for="reference_url" class="form-label text-dark small fw-semibold">
                                    Reference Website URL <span class="text-secondary">(Optional)</span>
                                </label>
                                <input type="url" class="form-control form-control-chandila" id="reference_url" name="reference_url" placeholder="https://example.com" value="<?php echo htmlspecialchars($_POST['reference_url'] ?? ($_GET['ref'] ?? '')); ?>">
                                <div class="form-text text-secondary small">Websites with design or functionality you like (e.g. aquasuper.lk, arigato.lk, etc.).</div>
                            </div>

                            <!-- Field 10: Expected Budget (Optional) -->
                            <div class="col-md-6">
                                <label for="expected_budget" class="form-label text-dark small fw-semibold">
                                    Expected Budget Range <span class="text-secondary">(Optional)</span>
                                </label>
                                <input type="text" class="form-control form-control-chandila" id="expected_budget" name="expected_budget" placeholder="e.g. Rs. 150,000 - 250,000 or USD 800" value="<?php echo htmlspecialchars($_POST['expected_budget'] ?? ''); ?>">
                            </div>

                            <!-- Field 11: Expected Completion Date -->
                            <div class="col-md-6">
                                <label for="expected_completion_date" class="form-label text-dark small fw-semibold">
                                    Target Completion Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-chandila" id="expected_completion_date" name="expected_completion_date" min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" value="<?php echo htmlspecialchars($_POST['expected_completion_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>" required>
                            </div>

                            <!-- Field 12: Additional Requirements -->
                            <div class="col-12">
                                <label for="additional_requirements" class="form-label text-dark small fw-semibold">
                                    Additional Requirements or Notes <span class="text-secondary">(Optional)</span>
                                </label>
                                <textarea class="form-control form-control-chandila" id="additional_requirements" name="additional_requirements" rows="2" placeholder="Any specific hosting preferences, third-party APIs, or domain names..."><?php echo htmlspecialchars($_POST['additional_requirements'] ?? ''); ?></textarea>
                            </div>

                            <!-- Field 13: File Attachments (Optional) -->
                            <div class="col-12">
                                <label for="project_attachment" class="form-label text-dark small fw-semibold">
                                    File Attachments (Wireframes, BRD, Logo, Assets) <span class="text-secondary">(Optional)</span>
                                </label>
                                <input class="form-control form-control-chandila" type="file" id="project_attachment" name="attachment">
                                <div class="form-text text-secondary small">
                                    Allowed file formats: PDF, DOC, DOCX, PNG, JPG, ZIP (Max: 10MB).
                                </div>
                                <div id="file_selected_helper" class="text-primary small mt-1 d-none"></div>
                            </div>
                        </div>

                        <!-- Submission Button -->
                        <div class="pt-4 border-top border-secondary border-opacity-15 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3">
                            <div class="text-secondary small">
                                <i class="fa-solid fa-lock text-success me-1"></i> Submissions are encrypted & confidential.
                            </div>
                            <button type="submit" class="btn btn-gold btn-lg px-5">
                                <i class="fa-solid fa-paper-plane me-2"></i> Submit & Receive Job ID
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

