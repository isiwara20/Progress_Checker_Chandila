<?php
/**
 * Chandila Web Development – About Us Page
 * Content Rebuilt based on Official Brand Guidelines (chandila.site/about)
 * Contact Numbers: 076 843 3340 / WhatsApp: 076 843 3340
 */
$pageTitle = 'About Us – More Than a Digital Agency, Your Growth Partner | Chandila Web Development';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 1. Hero Header Section -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10 position-relative overflow-hidden">
    <div class="container py-4 text-center">
        <span class="badge bg-warning bg-opacity-15 text-warning-emphasis border border-warning px-3 py-2 rounded-pill mb-3 fw-bold anim-fade-down">
            <i class="fa-solid fa-crown me-1"></i> ABOUT CHANDILA
        </span>
        <h1 class="display-4 fw-bold text-dark mb-3 anim-fade-up anim-delay-1">
            More Than a Digital Agency — <span class="text-gradient">Your Growth Partner</span>
        </h1>
        <p class="text-secondary mx-auto fs-5 mb-5 anim-fade-up anim-delay-2" style="max-width: 760px;">
            We combine strategy, technology, and creativity to build digital solutions that transform businesses and deliver lasting impact.
        </p>

        <!-- 4 Metric Counter Cards -->
        <div class="row g-4 justify-content-center anim-fade-up anim-delay-3">
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-light text-center hover-elevate">
                    <div class="display-6 fw-bold text-primary mb-1">120+</div>
                    <div class="text-dark fw-bold mb-0">Happy Clients</div>
                    <div class="text-muted small mt-1">Across 15+ Countries</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-light text-center hover-elevate">
                    <div class="display-6 fw-bold text-warning mb-1">250+</div>
                    <div class="text-dark fw-bold mb-0">Projects Delivered</div>
                    <div class="text-muted small mt-1">500+ Milestone Systems</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-light text-center hover-elevate">
                    <div class="display-6 fw-bold text-success mb-1">8+</div>
                    <div class="text-dark fw-bold mb-0">Years of Experience</div>
                    <div class="text-muted small mt-1">Founded in 2016</div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-light text-center hover-elevate">
                    <div class="display-6 fw-bold text-info mb-1">15+</div>
                    <div class="text-dark fw-bold mb-0">Countries Served</div>
                    <div class="text-muted small mt-1">Global Client Trust</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Our Story Section with Downloaded Image Graphic -->
<section class="py-5 bg-light border-bottom border-secondary border-opacity-10">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill mb-2">OUR STORY</span>
                <h2 class="display-6 fw-bold text-dark mb-4">
                    Built on Passion, <span class="text-gradient">Driven by Purpose</span>
                </h2>
                
                <p class="text-secondary mb-3 fs-6" style="line-height: 1.7;">
                    <strong>CHANDILA</strong> was founded in <strong>2016</strong> with a clear mission: to make world-class digital solutions accessible to businesses of every size. What started as a small team of passionate developers in Colombo, Sri Lanka, has grown into a full-service digital solutions company trusted by clients across <strong>15+ countries</strong>.
                </p>
                <p class="text-secondary mb-3 fs-6" style="line-height: 1.7;">
                    We believe technology should empower businesses, not complicate them. That's why we combine deep technical expertise with a genuine understanding of business strategy — ensuring every solution we deliver isn't just technically sound, but commercially impactful.
                </p>
                <p class="text-secondary mb-4 fs-6" style="line-height: 1.7;">
                    From building scalable e-commerce platforms to powering educational institutions with custom LMS solutions, we've helped over <strong>120 businesses</strong> achieve measurable growth. Today, we're expanding into new frontiers — including our upcoming <strong>five-star Chandila Hotel</strong> — while staying true to the values that built us.
                </p>

                <div class="d-flex flex-wrap gap-3 align-items-center pt-2">
                    <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn-gold btn-lg">
                        <i class="fa-solid fa-rocket me-2"></i> Start Your Journey
                    </a>
                    <a href="https://wa.me/9476843340" target="_blank" class="btn btn-outline-success btn-lg">
                        <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp: 076 843 3340
                    </a>
                </div>
            </div>

            <!-- Story Visual Graphic Card (High-Res Stock Image) -->
            <div class="col-lg-6">
                <div class="chandila-card p-2 shadow-lg border-0 overflow-hidden hover-elevate position-relative">
                    <img src="<?php echo BASE_URL; ?>/assets/images/about_story.jpg" 
                         alt="Chandila Web Development - Built on Passion, Driven by Purpose" 
                         class="img-fluid rounded-4 w-100 object-fit-cover shadow-sm" style="max-height: 420px; min-height: 320px;">
                    <div class="position-absolute bottom-0 start-0 m-3 p-3 bg-dark bg-opacity-90 rounded-3 text-white border border-warning shadow">
                        <div class="h6 fw-bold text-warning mb-0"><i class="fa-solid fa-trophy me-1"></i> 8+ Years of Excellence</div>
                        <div class="small text-light" style="font-size: 0.78rem;">Colombo, Sri Lanka to 15+ Countries</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 3. Our Mission & Vision -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="row g-4">
            <!-- Mission Card -->
            <div class="col-md-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 p-md-5 shadow-sm bg-white hover-elevate">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-4" style="width: 56px; height: 56px; background: #eff6ff; color: #2563eb; font-size: 1.6rem;">
                        <i class="fa-solid fa-bullseye"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-3">Our Mission</h3>
                    <p class="text-secondary fs-6 mb-0" style="line-height: 1.8;">
                        To empower businesses worldwide with innovative, reliable, and scalable digital solutions that drive measurable growth — while maintaining the highest standards of quality, transparency, and client satisfaction.
                    </p>
                </div>
            </div>

            <!-- Vision Card -->
            <div class="col-md-6">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 p-md-5 shadow-sm bg-white hover-elevate">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-3 mb-4" style="width: 56px; height: 56px; background: #fef3c7; color: #d97706; font-size: 1.6rem;">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-3">Our Vision</h3>
                    <p class="text-secondary fs-6 mb-0" style="line-height: 1.8;">
                        To be the most trusted digital solutions partner for businesses across the globe — known for our innovation, integrity, and unwavering commitment to delivering transformative results that stand the test of time.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 4. How We Work (Our Approach - 01 to 04) -->
<section class="py-5 bg-light border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">HOW WE WORK</span>
            <h2 class="display-6 fw-bold text-dark mb-2">Our Proven Approach</h2>
            <p class="text-secondary mx-auto" style="max-width: 650px;">
                A proven methodology that ensures clarity, quality, and results at every stage of your digital engagement.
            </p>
        </div>

        <div class="row g-4">
            <!-- 01 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white hover-elevate position-relative">
                    <div class="display-5 fw-bold text-primary opacity-25 mb-2 font-monospace">01</div>
                    <h5 class="fw-bold text-dark mb-2">Listen & Understand</h5>
                    <p class="text-secondary small mb-0">
                        We start by deeply understanding your business, challenges, and goals before proposing any technical solution.
                    </p>
                </div>
            </div>

            <!-- 02 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white hover-elevate position-relative">
                    <div class="display-5 fw-bold text-warning opacity-25 mb-2 font-monospace">02</div>
                    <h5 class="fw-bold text-dark mb-2">Strategise & Plan</h5>
                    <p class="text-secondary small mb-0">
                        We craft a tailored roadmap with clear milestones, timelines, and deliverables that align with your vision.
                    </p>
                </div>
            </div>

            <!-- 03 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white hover-elevate position-relative">
                    <div class="display-5 fw-bold text-info opacity-25 mb-2 font-monospace">03</div>
                    <h5 class="fw-bold text-dark mb-2">Build & Iterate</h5>
                    <p class="text-secondary small mb-0">
                        Agile development with regular feedback loops ensures what we build is exactly what you need — delivered fast.
                    </p>
                </div>
            </div>

            <!-- 04 -->
            <div class="col-md-6 col-lg-3">
                <div class="card h-100 border-0 shadow-sm p-4 rounded-4 bg-white hover-elevate position-relative">
                    <div class="display-5 fw-bold text-success opacity-25 mb-2 font-monospace">04</div>
                    <h5 class="fw-bold text-dark mb-2">Launch & Grow</h5>
                    <p class="text-secondary small mb-0">
                        Post-launch, we optimise, maintain, and scale your solution so it continues to deliver results long-term.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 5. Our Core Values (What Drives Us) -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill mb-2">OUR CORE VALUES</span>
            <h2 class="display-6 fw-bold text-dark mb-2">What Drives Us Every Day</h2>
            <p class="text-secondary mx-auto" style="max-width: 650px;">
                These principles guide every decision we make and every digital solution we engineer.
            </p>
        </div>

        <div class="row g-4">
            <!-- Value 1 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #eff6ff; color: #2563eb; border-color: #bfdbfe;">
                        <i class="fa-solid fa-lightbulb"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Innovation</h5>
                    <p class="text-secondary small mb-0">
                        We embrace emerging technologies and creative thinking to build forward-looking solutions that keep our clients ahead of the curve.
                    </p>
                </div>
            </div>

            <!-- Value 2 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #fef3c7; color: #d97706; border-color: #fde68a;">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Quality & Reliability</h5>
                    <p class="text-secondary small mb-0">
                        Every line of code, every pixel, and every strategy is held to the highest standards. We deliver work we are genuinely proud of.
                    </p>
                </div>
            </div>

            <!-- Value 3 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #ecfdf5; color: #059669; border-color: #a7f3d0;">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Integrity & Transparency</h5>
                    <p class="text-secondary small mb-0">
                        We operate with complete honesty — clear communication, fixed pricing, and no hidden surprises throughout the project lifecycle.
                    </p>
                </div>
            </div>

            <!-- Value 4 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #fdf2f8; color: #db2777; border-color: #fbcfe8;">
                        <i class="fa-solid fa-chart-line"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Results-Driven Growth</h5>
                    <p class="text-secondary small mb-0">
                        We measure success by the impact we create. Every decision we make is guided by delivering measurable outcomes for our clients.
                    </p>
                </div>
            </div>

            <!-- Value 5 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #e0f2fe; color: #0284c7; border-color: #bae6fd;">
                        <i class="fa-solid fa-user-check"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Client-First Approach</h5>
                    <p class="text-secondary small mb-0">
                        Your goals are our priority. We listen, understand, and tailor every solution to align with your unique business objectives.
                    </p>
                </div>
            </div>

            <!-- Value 6 -->
            <div class="col-md-4">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="service-icon-box mb-3" style="background: #f5f3ff; color: #7c3aed; border-color: #ddd6fe;">
                        <i class="fa-solid fa-infinity"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Long-Term Partnerships</h5>
                    <p class="text-secondary small mb-0">
                        We don't just complete projects — we build lasting relationships and support our clients through every stage of their growth.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 6. Why CHANDILA (The Difference & Client Quote) -->
<section class="py-5 bg-light border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">WHY CHANDILA</span>
                <h2 class="display-6 fw-bold text-dark mb-3">The CHANDILA Difference</h2>
                <p class="text-secondary mb-4">
                    With hundreds of digital agencies out there, here's what makes working with us a fundamentally different experience.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Dedicated project manager
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Fixed-price quotations (No hidden fees)
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Mobile-first, SEO-optimised
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Post-launch support &amp; maintenance
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> NDA &amp; IP protection guaranteed
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Track record: 120+ satisfied clients
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Agile methodology &amp; weekly updates
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="d-flex align-items-center gap-2 text-dark fw-semibold small">
                            <i class="fa-solid fa-circle-check text-success fs-6"></i> Scalable future-proof architecture
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Testimonial Quote Box -->
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg p-4 p-md-5 rounded-4 bg-white hover-elevate position-relative">
                    <i class="fa-solid fa-quote-left text-warning opacity-25 display-3 position-absolute top-0 end-0 m-4"></i>
                    
                    <div class="d-flex gap-1 text-warning mb-3">
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                        <i class="fa-solid fa-star"></i>
                    </div>

                    <blockquote class="blockquote fs-5 text-dark fw-semibold mb-4" style="line-height: 1.6;">
                        "CHANDILA didn't just build our platform — they became an extension of our team. Exceptional quality, clear communication, and results that exceeded all expectations."
                    </blockquote>

                    <div class="d-flex align-items-center gap-3 pt-3 border-top">
                        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white fw-bold" style="width: 48px; height: 48px;">
                            <i class="fa-solid fa-shield-heart"></i>
                        </div>
                        <div>
                            <div class="fw-bold text-dark">Trusted by 120+ Businesses</div>
                            <div class="text-secondary small">Across Sri Lanka, Australia, UK &amp; UAE</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 7. Industries We Serve (8 Industry Cards) -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill mb-2">INDUSTRIES WE SERVE</span>
            <h2 class="display-6 fw-bold text-dark mb-2">Deep Expertise Across Diverse Sectors</h2>
            <p class="text-secondary mx-auto" style="max-width: 650px;">
                We've delivered impactful digital platforms and custom automated systems across a wide range of industries.
            </p>
        </div>

        <div class="row g-3">
            <?php
            $industries = [
                ['name' => 'Education & E-Learning', 'icon' => 'fa-graduation-cap', 'desc' => 'LMS portals, student enrollment engines & online exams.'],
                ['name' => 'Healthcare & Wellness', 'icon' => 'fa-heart-pulse', 'desc' => 'Doctor scheduling, therapy reservations & patient records.'],
                ['name' => 'E-Commerce & Retail', 'icon' => 'fa-cart-shopping', 'desc' => 'Online stores, multi-currency catalogs & automated checkouts.'],
                ['name' => 'Finance & Fintech', 'icon' => 'fa-wallet', 'desc' => 'Loan tracking, pawn broker management & accounting systems.'],
                ['name' => 'Real Estate & Property', 'icon' => 'fa-building', 'desc' => 'Property listing engines, floor plan tours & lead generation.'],
                ['name' => 'Travel & Hospitality', 'icon' => 'fa-plane-departure', 'desc' => 'Tour package booking engines, itineraries & vehicle rentals.'],
                ['name' => 'Logistics & Transport', 'icon' => 'fa-truck-fast', 'desc' => 'Fleet monitoring, delivery routing & consignment tracking.'],
                ['name' => 'SaaS & Tech Startups', 'icon' => 'fa-cloud', 'desc' => 'Subscription portals, RESTful API backends & client dashboards.']
            ];
            foreach ($industries as $ind):
            ?>
                <div class="col-6 col-md-3">
                    <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-3 text-center bg-white hover-elevate">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-3 mx-auto mb-2" style="width: 48px; height: 48px; background: #f8fafc; color: #2563eb; font-size: 1.3rem;">
                            <i class="fa-solid <?php echo $ind['icon']; ?>"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?php echo $ind['name']; ?></h6>
                        <p class="text-secondary small mb-0" style="font-size: 0.78rem;"><?php echo $ind['desc']; ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- 8. Our Journey (From Start to Now - Interactive Milestone Timeline) -->
<section class="py-5 bg-light border-bottom border-secondary border-opacity-10 position-relative overflow-hidden">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">OUR JOURNEY</span>
            <h2 class="display-6 fw-bold text-dark mb-2">From Start to Now (2016 – 2026+)</h2>
            <p class="text-secondary mx-auto fs-5" style="max-width: 680px;">
                A decade of continuous innovation, relentless engineering, and sustainable global expansion.
            </p>
        </div>

        <!-- The Connected Milestone Timeline Roadmap -->
        <div class="milestone-journey">

            <!-- Milestone 1: 2016 (Left) -->
            <div class="milestone-item left">
                <div class="milestone-node">
                    <i class="fa-solid fa-flag text-warning"></i>
                </div>
                <div class="milestone-content">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2 flex-wrap">
                        <span class="badge bg-primary px-3 py-1 font-monospace">2016 • FOUNDING YEAR</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">The Beginning</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        CHANDILA was founded in Colombo with a bold vision — to make enterprise-grade digital solutions accessible to businesses of all sizes.
                    </p>
                    <div class="text-muted small" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i> Colombo, Sri Lanka • Core Engineering Team
                    </div>
                </div>
            </div>

            <!-- Milestone 2: 2018 (Right) -->
            <div class="milestone-item right">
                <div class="milestone-node">
                    <i class="fa-solid fa-chart-line text-info"></i>
                </div>
                <div class="milestone-content">
                    <div class="d-flex align-items-center justify-content-start gap-2 mb-2 flex-wrap">
                        <span class="badge bg-info text-dark px-3 py-1 font-monospace">2018 • GROWTH PHASE</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Scaling Operations</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Expanded our service portfolio and onboarded clients from education, healthcare, and e-commerce sectors across South Asia.
                    </p>
                    <div class="text-muted small" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-graduation-cap text-primary me-1"></i> Custom LMS &amp; Healthcare Portals Launched
                    </div>
                </div>
            </div>

            <!-- Milestone 3: 2020 (Left) -->
            <div class="milestone-item left">
                <div class="milestone-node">
                    <i class="fa-solid fa-globe text-success"></i>
                </div>
                <div class="milestone-content">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2 flex-wrap">
                        <span class="badge bg-success px-3 py-1 font-monospace">2020 • GLOBAL REACH</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Global Expansion</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Reached 15+ countries, launched strategic partnerships, and delivered 100+ projects in a single year despite global pandemic challenges.
                    </p>
                    <div class="text-muted small" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-earth-americas text-success me-1"></i> 15+ Countries • 100+ Delivered in 1 Year
                    </div>
                </div>
            </div>

            <!-- Milestone 4: 2022 (Right) -->
            <div class="milestone-item right">
                <div class="milestone-node">
                    <i class="fa-solid fa-trophy text-warning"></i>
                </div>
                <div class="milestone-content">
                    <div class="d-flex align-items-center justify-content-start gap-2 mb-2 flex-wrap">
                        <span class="badge bg-warning text-dark px-3 py-1 font-monospace">2022 • INDUSTRY ACCLAIM</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Innovation &amp; Awards</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Introduced AI-driven solutions, advanced automation, and earned recognition as a top digital solutions provider in Sri Lanka.
                    </p>
                    <div class="text-muted small" style="font-size: 0.8rem;">
                        <i class="fa-solid fa-award text-warning me-1"></i> Top Digital Solutions Award &amp; Cloud Automation
                    </div>
                </div>
            </div>

            <!-- Milestone 5: 2024 - 2026+ (Left / Pinnacle Milestone) -->
            <div class="milestone-item left">
                <div class="milestone-node" style="border-color: #d97706; background: #fffbeb;">
                    <i class="fa-solid fa-hotel text-warning"></i>
                </div>
                <div class="milestone-content border-warning border-opacity-50">
                    <div class="d-flex align-items-center justify-content-end gap-2 mb-2 flex-wrap">
                        <span class="badge bg-dark border border-warning text-warning px-3 py-1 font-monospace">2024 – 2026+ • PINNACLE MILESTONE</span>
                    </div>
                    <h4 class="fw-bold text-dark mb-2">Future Forward: Five-Star Hospitality &amp; Next-Gen Tech</h4>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Continuing to innovate with five-star luxury hospitality (<strong>Chandila Hotel</strong>), advanced AI integrations, and next-generation digital products with over <strong>500+ projects</strong> deployed worldwide.
                    </p>
                    <div class="rounded-3 overflow-hidden border border-warning border-opacity-50 shadow-sm mt-3 position-relative">
                        <img src="<?php echo BASE_URL; ?>/assets/images/chandila_hotel.jpg" alt="Five-Star Luxury Chandila Hotel" class="img-fluid w-100 object-fit-cover" style="max-height: 280px;">
                        <div class="position-absolute top-0 start-0 m-3">
                            <span class="badge bg-warning text-dark fw-bold font-monospace px-3 py-1 shadow">
                                ★ ★ ★ ★ ★ FIVE-STAR LUXURY HOSPITALITY
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- 9. Our Partners (Trusted Collaborations with Downloaded Badges) -->
<section class="py-5 bg-white border-bottom border-secondary border-opacity-10">
    <div class="container py-3">
        <div class="text-center mb-5">
            <span class="badge bg-primary bg-opacity-10 text-primary fw-bold px-3 py-1 rounded-pill mb-2">TRUSTED COLLABORATIONS</span>
            <h2 class="display-6 fw-bold text-dark mb-2">Our Strategic Partners</h2>
            <p class="text-secondary mx-auto" style="max-width: 650px;">
                We work alongside industry-leading technology partners to deliver comprehensive, end-to-end solutions for our clients.
            </p>
        </div>

        <div class="row g-4 justify-content-center">
            <!-- Partner 1: Lanka IT Solution -->
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="mb-3 text-center">
                        <img src="<?php echo BASE_URL; ?>/assets/images/partner_lanka_it.svg" alt="Lanka IT Solution" class="img-fluid" style="max-height: 80px;">
                    </div>
                    <h5 class="fw-bold text-dark mb-1 text-center">Lanka IT Solution</h5>
                    <div class="badge bg-info bg-opacity-10 text-info fw-bold mx-auto mb-3 d-table">Enterprise IT &amp; Infrastructure</div>
                    <p class="text-secondary small mb-0 text-center">
                        A leading IT services provider in Sri Lanka offering enterprise software, high-availability cloud infrastructure, and managed server operations.
                    </p>
                </div>
            </div>

            <!-- Partner 2: Victorxy Lanka -->
            <div class="col-md-6 col-lg-5">
                <div class="card h-100 border border-secondary border-opacity-25 rounded-4 p-4 shadow-sm bg-white hover-elevate">
                    <div class="mb-3 text-center">
                        <img src="<?php echo BASE_URL; ?>/assets/images/partner_victorxy.svg" alt="Victorxy Lanka" class="img-fluid" style="max-height: 80px;">
                    </div>
                    <h5 class="fw-bold text-dark mb-1 text-center">Victorxy Lanka</h5>
                    <div class="badge bg-warning bg-opacity-10 text-warning-emphasis fw-bold mx-auto mb-3 d-table">Digital Products &amp; Branding</div>
                    <p class="text-secondary small mb-0 text-center">
                        A dynamic technology company specialising in innovative digital products, corporate visual branding, and creative tech solutions.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 10. Call to Action Banner -->
<section class="py-5 bg-light">
    <div class="container py-4 text-center">
        <h2 class="text-dark fw-bold mb-3 display-6">Ready to Transform Your Business Digitally?</h2>
        <p class="text-secondary mb-4 mx-auto fs-5" style="max-width: 650px;">
            Let's discuss how CHANDILA can help you achieve your goals with a tailored digital strategy and guaranteed delivery.
        </p>
        <div class="d-flex justify-content-center flex-wrap gap-3">
            <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-paper-plane me-2"></i> Get in Touch
            </a>
            <a href="<?php echo BASE_URL; ?>/services.php" class="btn btn-outline-dark btn-lg">
                <i class="fa-solid fa-layer-group me-2"></i> Explore Services
            </a>
            <a href="https://wa.me/9476843340" target="_blank" class="btn btn-outline-success btn-lg">
                <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp: 076 843 3340
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
