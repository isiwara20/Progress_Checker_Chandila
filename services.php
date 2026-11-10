<?php
$pageTitle = 'Our Services – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';
?>

<div class="py-5" style="background: radial-gradient(circle at 10% 20%, rgba(245, 158, 11, 0.05) 0%, transparent 60%), #f8fafc;">
    <div class="container py-4">

        <!-- Page Header -->
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning px-3 py-2 mb-2 fw-bold anim-fade-down">
                <i class="fa-solid fa-trophy me-1"></i> 500+ DELIVERED PROJECTS
            </span>
            <h1 class="text-dark fw-bold display-5 anim-fade-up anim-delay-1">Full-Lifecycle Web Engineering Services</h1>
            <p class="text-secondary fs-5 anim-fade-up anim-delay-2">
                We handle every stage of your digital journey from specification gathering and UI/UX design to database architecture, security hardening, and high-availability hosting.
            </p>
        </div>

        <!-- Services Grid -->
        <div class="row g-4 anim-fade-up anim-delay-3">
            <!-- 1. Business Website -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_business.jpg" alt="Corporate & Business Websites" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-briefcase"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">Corporate & Business Websites</h4>
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Branding & Lead Generation</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Tailored business websites built for modern companies looking to dominate their market. Clean typography, fast load times, on-page SEO optimization, contact forms with WhatsApp routing, and CMS support.
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>Custom UI/UX designed in Bootstrap 5 without bulky templates</li>
                        <li>High Google PageSpeed scores & mobile responsiveness</li>
                        <li>Automated WhatsApp lead capture alerts</li>
                        <li>Google Analytics & Meta Pixel integration</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=Business+Website" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request Business Website Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. E-commerce Website -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_ecommerce.jpg" alt="E-Commerce Platforms & Online Stores" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">E-Commerce Platforms & Online Stores</h4>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Sales & Payments</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Scalable online stores capable of handling thousands of products and orders (e.g. <a href="https://aquasuper.lk" target="_blank" class="text-primary fw-bold">Aqua Super</a>, <a href="https://arigato.lk" target="_blank" class="text-primary fw-bold">Arigato</a>, <a href="https://lasana.com" target="_blank" class="text-primary fw-bold">Lasana</a>). Features multi-currency checkouts, local Sri Lankan payment gateways (PayHere, Commercial Bank, BOC IPG), stock control, and courier APIs.
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>PayHere, Stripe, PayPal, and Bank Slip Upload verification</li>
                        <li>Live stock level alerts & dynamic product variations</li>
                        <li>Automated invoice generation & PDF dispatch</li>
                        <li>Customer order tracking & WhatsApp dispatch notifications</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=E-commerce+Website" class="btn btn-outline-success btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request E-Commerce Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- 3. Web Application & Portals -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_webapp.jpg" alt="Web Applications & SaaS Portals" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">Web Applications & SaaS Portals</h4>
                            <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-25">Custom Software</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Purpose-built cloud software for internal business operations, customer portals, billing platforms, inventory management, and B2B ordering systems.
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>Role-Based Access Control (Admin, Staff, Customer tiers)</li>
                        <li>Interactive Chart.js business analytics & exportable reports</li>
                        <li>Secure session management & CSRF/XSS protection</li>
                        <li>RESTful API endpoints for mobile app connectivity</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=Web+Application" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request Web Application Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- 4. Cloud POS Systems -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_pos.jpg" alt="Cloud Point of Sale (POS) Systems" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-cash-register"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">Cloud Point of Sale (POS) Systems</h4>
                            <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning border-opacity-30">Retail & Supermarket Billing</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Fast, reliable billing and stock control software engineered for retail chains and restaurants (as proven in <a href="https://lesipos.lk" target="_blank" class="text-primary fw-bold">Lesi POS</a> and <a href="https://pos.etrending.lk" target="_blank" class="text-primary fw-bold">POS by E-Trending</a>).
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>Barcode scanner and thermal printer receipt integration</li>
                        <li>Multi-branch inventory ledger and transfer tracking</li>
                        <li>Shift cashier reconciliation & sales auditing</li>
                        <li>Cloud synchronized offline-resilient architecture</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=Custom+Web+Application" class="btn btn-gold btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request Cloud POS Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- 5. Student Management System -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_lms.jpg" alt="Student Management Systems (SMS)" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">Student Management Systems (SMS)</h4>
                            <span class="badge bg-info bg-opacity-10 text-primary border border-primary border-opacity-25">Education & Academies</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Complete institutional management for schools, tuition institutes, universities, and training academies. Tracks student enrollments, batches, timetables, and teacher allocations.
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>QR Code / Barcode student attendance check-in</li>
                        <li>Student monthly fee vouchers & online payment verification</li>
                        <li>Exam grading, report card generation, and marks analytics</li>
                        <li>WhatsApp / SMS attendance alerts to parents</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=Student+Management+System" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request Student Management Quote
                        </a>
                    </div>
                </div>
            </div>

            <!-- 6. Booking System -->
            <div class="col-lg-6">
                <div class="chandila-card p-4 h-100 d-flex flex-column">
                    <div class="mb-3 overflow-hidden rounded-3 border border-secondary border-opacity-15 shadow-sm">
                        <img src="<?php echo BASE_URL; ?>/assets/images/services/service_hosting.jpg" alt="Online Booking & Reservation Systems" class="w-100 object-fit-cover" style="height: 190px;">
                    </div>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="service-icon-box mb-0">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <div>
                            <h4 class="text-dark mb-1">Online Booking & Reservation Systems</h4>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Appointments & Tourism</span>
                        </div>
                    </div>
                    <p class="text-secondary small mb-3">
                        Real-time booking engines for clinic appointments (like <a href="https://ceylontherapist.lk" target="_blank" class="text-primary fw-bold">Ceylon Therapist</a>), holiday tours (<a href="https://himaratravels.lk" target="_blank" class="text-primary fw-bold">Himara Travels</a>), and vehicle rentals.
                    </p>
                    <ul class="text-secondary small mb-4 ps-3">
                        <li>Interactive calendar with real-time slot availability</li>
                        <li>Advance booking deposit collection via payment gateway</li>
                        <li>Automated WhatsApp appointment confirmation & reminders</li>
                        <li>Admin reschedule & cancellation management</li>
                    </ul>
                    <div class="mt-auto">
                        <a href="<?php echo BASE_URL; ?>/request_project.php?type=Booking+System" class="btn btn-outline-success btn-sm">
                            <i class="fa-solid fa-plus-circle me-1"></i> Request Booking System Quote
                        </a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
