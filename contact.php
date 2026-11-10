<?php
$pageTitle = 'Contact Us – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';

$sentSuccess = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'contact_inquiry') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $errorMessage = "Security validation failed. Please reload and try again.";
    } else {
        $name    = trim($_POST['name'] ?? '');
        $email   = trim($_POST['email'] ?? '');
        $phone   = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if (empty($name) || empty($email) || empty($message)) {
            $errorMessage = "Please fill in all required fields.";
        } else {
            // Simulated or saved inquiry
            $sentSuccess = true;
        }
    }
}
?>

<div class="py-5" style="background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);">
    <div class="container py-4">

        <!-- Page Header -->
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20 px-3 py-2 mb-2 fw-semibold">
                GET IN TOUCH
            </span>
            <h1 class="text-dark fw-bold">Contact Chandila Web Development</h1>
            <p class="text-muted">
                Have an inquiry about an upcoming project or need technical consultation? Reach out through our official channels.
            </p>
        </div>

        <div class="row g-5">
            <!-- Left Contact Cards -->
            <div class="col-lg-5">
                <div class="d-flex flex-column gap-3 mb-4">
                    <!-- Card 1: Office Location -->
                    <div class="chandila-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="service-icon-box mb-0">
                                <i class="fa-solid fa-map-location-dot"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">Our Colombo Office</h5>
                                <p class="text-muted small mb-0">No. 45, Galle Road, Colombo 03, Western Province, Sri Lanka</p>
                            </div>
                        </div>
                    </div>

                    <!-- Card 2: WhatsApp & Direct Call -->
                    <div class="chandila-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="service-icon-box mb-0" style="background: rgba(37, 211, 102, 0.12); border-color: #25D366; color: #25D366;">
                                <i class="fa-brands fa-whatsapp"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">WhatsApp & Direct Phone</h5>
                                <p class="text-muted small mb-1"><strong>076 843 3340</strong> (24/7 WhatsApp Connected)</p>
                                <a href="https://wa.me/9476843340" target="_blank" class="text-success fw-bold small">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Open WhatsApp Chat
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Card 3: Email Support -->
                    <div class="chandila-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="service-icon-box mb-0">
                                <i class="fa-solid fa-envelope-open-text"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">Email Inquiries</h5>
                                <p class="text-muted small mb-1">contact@chandilaweb.com</p>
                                <span class="badge bg-primary bg-opacity-10 text-primary">Typical response time: under 2 hours</span>
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Working Hours -->
                    <div class="chandila-card p-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="service-icon-box mb-0">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                            <div>
                                <h5 class="text-dark mb-1 fw-bold">Business Hours</h5>
                                <p class="text-muted small mb-0">Monday – Saturday: 9:00 AM – 6:00 PM (Asia/Colombo)</p>
                            </div>
                        </div>
                    </div>

                    <!-- Colombo Office Headquarters Graphic Card (High-Res Stock Image) -->
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden position-relative hover-elevate">
                        <img src="<?php echo BASE_URL; ?>/assets/images/colombo_office.jpg" alt="Chandila Colombo Headquarters" class="img-fluid w-100 object-fit-cover" style="max-height: 260px;">
                        <div class="position-absolute bottom-0 start-0 m-3 p-2 bg-dark bg-opacity-90 rounded-3 text-white border border-warning shadow">
                            <div class="small fw-bold text-warning mb-0"><i class="fa-solid fa-location-dot me-1"></i> Colombo 03 Headquarters</div>
                            <div class="text-light" style="font-size: 0.72rem;">No. 45, Galle Road, Colombo 03, Sri Lanka</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Interactive Contact Form -->
            <div class="col-lg-7">
                <div class="chandila-card p-4 p-md-5">
                    <h3 class="text-dark fw-bold mb-2">Send an Inquiry</h3>
                    <p class="text-muted small mb-4">Our project management team will reply with technical advice and timeline guidance.</p>

                    <?php if ($sentSuccess): ?>
                        <div class="alert alert-success border-success text-success bg-success bg-opacity-10 mb-4">
                            <i class="fa-solid fa-circle-check me-2"></i> Thank you! Your inquiry has been transmitted successfully. We will contact you shortly.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($errorMessage)): ?>
                        <div class="alert alert-danger border-danger text-danger bg-danger bg-opacity-10 mb-4">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="row g-3">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="contact_inquiry">

                        <div class="col-md-6">
                            <label for="name" class="form-label text-dark small fw-semibold">Your Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-chandila" id="name" name="name" placeholder="Kasun Perera" required>
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label text-dark small fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-chandila" id="email" name="email" placeholder="kasun@example.lk" required>
                        </div>

                        <div class="col-md-6">
                            <label for="phone" class="form-label text-dark small fw-semibold">WhatsApp / Phone Number</label>
                            <input type="text" class="form-control form-control-chandila" id="phone" name="phone" placeholder="e.g. 076 843 3340">
                        </div>

                        <div class="col-md-6">
                            <label for="subject" class="form-label text-dark small fw-semibold">Subject</label>
                            <input type="text" class="form-control form-control-chandila" id="subject" name="subject" placeholder="General Inquiry / Project Consultation">
                        </div>

                        <div class="col-12">
                            <label for="message" class="form-label text-dark small fw-semibold">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-chandila" id="message" name="message" rows="5" placeholder="Tell us how we can help your business..." required></textarea>
                        </div>

                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-chandila-primary w-100 py-3 fw-bold shadow-sm">
                                <i class="fa-solid fa-paper-plane me-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="mt-5 pt-5 border-top">
            <div class="text-center mb-5">
                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 mb-2 fw-semibold">FREQUENTLY ASKED QUESTIONS</span>
                <h2 class="text-dark fw-bold">Common Inquiries & Answers</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-9">
                    <div class="accordion accordion-flush" id="faqAccordion">
                        <div class="accordion-item mb-3 rounded-3 overflow-hidden shadow-sm" style="background: #ffffff; border: 1.5px solid #e2e8f0;">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How does the 3-Stage Payment arrangement work?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted small">
                                    Our standard arrangement divides the agreed total cost into three verified milestones: (1) Advance Payment upon project review to officially confirm development; (2) Second Payment (typically 50% milestone) before the scheduled completion date; and (3) Final outstanding payment upon completion of development and client review, right before we deploy to live hosting.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 rounded-3 overflow-hidden shadow-sm" style="background: #ffffff; border: 1.5px solid #e2e8f0;">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What is the Job ID and how do I track my project?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted small">
                                    Every project submitted through our system is automatically assigned an atomic, unique tracking ID (e.g., <code>CHW-2026-0001</code>). You will receive this via WhatsApp immediately. You can enter this Job ID along with your registered phone number on our public "Track My Project" page anytime to check live progress, stage timeline, and payment statuses without needing an account.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item mb-3 rounded-3 overflow-hidden shadow-sm" style="background: #ffffff; border: 1.5px solid #e2e8f0;">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed bg-white text-dark fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Do you provide cloud hosting and domain setup?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body text-muted small">
                                    Yes! We configure production cloud servers, install Free SSL certificates, configure MySQL databases, setup automated backups, and handle DNS linking for your chosen domain name. Once final payment is confirmed, we deploy the live website and provide full ownership credentials.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
