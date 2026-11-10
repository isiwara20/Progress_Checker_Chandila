<?php
$pageTitle = 'Our Projects & Portfolio (500+ Delivered) – Chandila Web Development';
require_once __DIR__ . '/includes/header.php';

// Real customer projects delivered by Chandila Web Development
$realProjects = [
    [
        'title'       => 'Aqua Super',
        'url'         => 'https://aquasuper.lk',
        'category'    => 'ecommerce',
        'badge'       => 'E-Commerce & Retail',
        'icon'        => 'fa-cart-shopping',
        'color'       => 'primary',
        'description' => 'Online grocery and beverage shopping portal featuring instant product catalog, delivery scheduler, and local payment gateway integration.',
        'tech'        => ['PHP', 'MySQL', 'Bootstrap 5', 'PayHere Gateway', 'Cart System']
    ],
    [
        'title'       => 'Arigato',
        'url'         => 'https://arigato.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Fashion & Retail',
        'icon'        => 'fa-shirt',
        'color'       => 'warning',
        'description' => 'Modern lifestyle and retail e-commerce platform with dynamic inventory, size/color variant selector, and responsive checkout.',
        'tech'        => ['PHP', 'MySQL', 'UI/UX', 'Order Tracker', 'WhatsApp API']
    ],
    [
        'title'       => 'Attecert',
        'url'         => 'https://attecert.com',
        'category'    => 'corporate',
        'badge'       => 'Document Verification',
        'icon'        => 'fa-certificate',
        'color'       => 'info',
        'description' => 'Global certificate attestation and legal document verification platform with online application submission and tracking.',
        'tech'        => ['Core PHP', 'MySQL', 'Secure Uploads', 'Tracking Engine']
    ],
    [
        'title'       => 'Best ' . "N" . ' Less',
        'url'         => 'https://bestnless.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Mega Retail Store',
        'icon'        => 'fa-bag-shopping',
        'color'       => 'success',
        'description' => 'High-volume consumer goods and multi-category retail store with multi-tiered discounts and fast search filtering.',
        'tech'        => ['PHP', 'MySQL', 'AJAX Filter', 'Payment Gateway']
    ],
    [
        'title'       => 'Ceylon Therapist',
        'url'         => 'https://ceylontherapist.lk',
        'category'    => 'wellness',
        'badge'       => 'Healthcare & Therapy',
        'icon'        => 'fa-spa',
        'color'       => 'success',
        'description' => 'Comprehensive directory and appointment booking platform for certified therapists and wellness practitioners across Sri Lanka.',
        'tech'        => ['PHP', 'MySQL', 'Booking Engine', 'Calendar Integration']
    ],
    [
        'title'       => 'Chanu Gems',
        'url'         => 'https://chanugems.com',
        'category'    => 'ecommerce',
        'badge'       => 'Gems & Luxury Export',
        'icon'        => 'fa-gem',
        'color'       => 'warning',
        'description' => 'Luxury gemstone and certified natural sapphire export showcase with multi-currency pricing, high-res gallery, and international courier tracking.',
        'tech'        => ['PHP', 'MySQL', 'Multi-Currency', 'Stripe Gateway', 'DHL API']
    ],
    [
        'title'       => 'Damaz',
        'url'         => 'https://damaz.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Online Marketplace',
        'icon'        => 'fa-store',
        'color'       => 'primary',
        'description' => 'Full-featured online shopping marketplace with categories spanning electronics, home essentials, and fashion with instant delivery tracking.',
        'tech'        => ['PHP', 'MySQL', 'Cart Engine', 'PayHere', 'Order Dispatch']
    ],
    [
        'title'       => 'Easy Clicks',
        'url'         => 'https://easyclicks.lk',
        'category'    => 'services',
        'badge'       => 'Digital Services',
        'icon'        => 'fa-mouse-pointer',
        'color'       => 'info',
        'description' => 'One-click digital services, utility bookings, and consumer tech solutions platform with simple checkout and instant service vouchers.',
        'tech'        => ['HTML5', 'Bootstrap 5', 'PHP', 'API Integrations']
    ],
    [
        'title'       => 'E-Trending',
        'url'         => 'https://etrending.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Electronics & Gadgets',
        'icon'        => 'fa-headphones',
        'color'       => 'primary',
        'description' => 'Trending smart devices, mobile accessories, and electronic products store with real-time stock sync and automated invoice dispatch.',
        'tech'        => ['PHP', 'MySQL', 'Responsive UI', 'WhatsApp Notifications']
    ],
    [
        'title'       => 'Ezymart',
        'url'         => 'https://ezymart.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Grocery & Supermarket',
        'icon'        => 'fa-basket-shopping',
        'color'       => 'success',
        'description' => 'Neighborhood convenience and express grocery ordering platform with localized routing and mobile responsive interface.',
        'tech'        => ['PHP', 'MySQL', 'Express Checkout', 'SMS/WhatsApp Alert']
    ],
    [
        'title'       => 'Fast Maktec',
        'url'         => 'https://fastmaktec.com',
        'category'    => 'corporate',
        'badge'       => 'Industrial Machinery',
        'icon'        => 'fa-gears',
        'color'       => 'secondary',
        'description' => 'Engineering machinery, power tools, and industrial equipment digital catalog with quotation request workflows.',
        'tech'        => ['PHP', 'MySQL', 'Quote Generator', 'Product Specs']
    ],
    [
        'title'       => 'Himara Travels',
        'url'         => 'https://himaratravels.lk',
        'category'    => 'tourism',
        'badge'       => 'Tours & Vacation Booking',
        'icon'        => 'fa-plane-departure',
        'color'       => 'info',
        'description' => 'Bespoke Sri Lanka holiday itineraries, airport transfers, and hotel package reservation portal with multi-language support.',
        'tech'        => ['PHP', 'MySQL', 'Trip Planner', 'Direct WhatsApp Booking']
    ],
    [
        'title'       => 'IT Click',
        'url'         => 'https://itclick.lk',
        'category'    => 'services',
        'badge'       => 'IT Solutions & Hardware',
        'icon'        => 'fa-laptop-code',
        'color'       => 'primary',
        'description' => 'Enterprise computer hardware, security camera solutions, and IT infrastructure service portal with technical support ticket logging.',
        'tech'        => ['PHP', 'MySQL', 'Ticketing System', 'Catalog Engine']
    ],
    [
        'title'       => 'Jaffna Ads',
        'url'         => 'https://jaffnaads.lk',
        'category'    => 'services',
        'badge'       => 'Classifieds & Regional Ads',
        'icon'        => 'fa-bullhorn',
        'color'       => 'warning',
        'description' => 'Northern province classified advertising portal featuring verified property, automotive, and employment listings with image uploads.',
        'tech'        => ['PHP', 'MySQL', 'Ad Moderation Engine', 'Search Indexer']
    ],
    [
        'title'       => 'JCC',
        'url'         => 'https://jcc.lk',
        'category'    => 'corporate',
        'badge'       => 'Corporate & Institutional',
        'icon'        => 'fa-building-columns',
        'color'       => 'info',
        'description' => 'Prestigious institutional and corporate portal showcasing academy programs, administrative notices, and student admissions.',
        'tech'        => ['PHP', 'MySQL', 'Event Scheduler', 'Noticeboard System']
    ],
    [
        'title'       => 'J Tours',
        'url'         => 'https://jtours.lk',
        'category'    => 'tourism',
        'badge'       => 'Travel & Excursions',
        'icon'        => 'fa-route',
        'color'       => 'success',
        'description' => 'Custom travel experiences, private chauffeur tours, and wildlife safari bookings across Sri Lanka with online quote generator.',
        'tech'        => ['PHP', 'MySQL', 'Tour Packages', 'WhatsApp API']
    ],
    [
        'title'       => 'Lak Methodic',
        'url'         => 'https://lakmethodic.com',
        'category'    => 'corporate',
        'badge'       => 'Enterprise IT & Software',
        'icon'        => 'fa-network-wired',
        'color'       => 'primary',
        'description' => 'Enterprise digital architecture, methodic cloud solutions, and technology consultancy portfolio.',
        'tech'        => ['PHP', 'REST APIs', 'Cloud Architecture', 'Bootstrap 5']
    ],
    [
        'title'       => 'Lankan Lounge',
        'url'         => 'https://lankanlounge.com',
        'category'    => 'tourism',
        'badge'       => 'Hospitality & Dining',
        'icon'        => 'fa-utensils',
        'color'       => 'warning',
        'description' => 'Premium lounge, dining reservation, and culinary event showcase with digital interactive menu and table reservation engine.',
        'tech'        => ['PHP', 'MySQL', 'Menu Engine', 'Table Reservation']
    ],
    [
        'title'       => 'Lesi POS',
        'url'         => 'https://lesipos.lk',
        'category'    => 'pos',
        'badge'       => 'Cloud Point of Sale (POS)',
        'icon'        => 'fa-cash-register',
        'color'       => 'success',
        'description' => 'Modern cloud POS billing software for retail shops, supermarkets, and restaurants with barcode scanner support, inventory sync, and receipt printing.',
        'tech'        => ['Core PHP', 'MySQL', 'Thermal Printing', 'Barcode Scanner', 'Inventory']
    ],
    [
        'title'       => 'Lumak Store',
        'url'         => 'https://lumakstore.com',
        'category'    => 'ecommerce',
        'badge'       => 'Consumer Electronics',
        'icon'        => 'fa-tv',
        'color'       => 'primary',
        'description' => 'Consumer technology, smart home accessories, and audio gear e-store with payment gateway and dispatch tracker.',
        'tech'        => ['PHP', 'MySQL', 'Cart System', 'PayHere Gateway']
    ],
    [
        'title'       => 'Media One News Lanka',
        'url'         => 'https://mediaonenewslanka.lk',
        'category'    => 'services',
        'badge'       => 'Digital News & Media',
        'icon'        => 'fa-newspaper',
        'color'       => 'danger',
        'description' => 'High-traffic 24/7 breaking news and editorial journalism portal with category tagging, video embedding, and automated social sharing.',
        'tech'        => ['PHP', 'MySQL', 'CMS Architecture', 'Fast CDN Caching']
    ],
    [
        'title'       => 'Mewa Tours',
        'url'         => 'https://mewatours.lk',
        'category'    => 'tourism',
        'badge'       => 'Inbound Tour Operator',
        'icon'        => 'fa-compass',
        'color'       => 'info',
        'description' => 'Specialized holiday packages, cultural triangle expeditions, and southern beach holiday booking platform.',
        'tech'        => ['PHP', 'MySQL', 'Tour Planner', 'WhatsApp Booking']
    ],
    [
        'title'       => 'Pasiya Tech',
        'url'         => 'https://pasiyatech.com',
        'category'    => 'services',
        'badge'       => 'IT Engineering & CCTV',
        'icon'        => 'fa-microchip',
        'color'       => 'primary',
        'description' => 'Security surveillance, networking, and IT hardware installation services platform with warranty check lookup.',
        'tech'        => ['PHP', 'MySQL', 'Warranty Lookup', 'Service Catalog']
    ],
    [
        'title'       => 'POS by E-Trending',
        'url'         => 'https://pos.etrending.lk',
        'category'    => 'pos',
        'badge'       => 'Cloud Retail POS Engine',
        'icon'        => 'fa-barcode',
        'color'       => 'success',
        'description' => 'Enterprise point-of-sale management engine with real-time stock transfer between warehouse branches and cashier shift audits.',
        'tech'        => ['PHP', 'MySQL InnoDB', 'Multi-Branch Sync', 'Financial Audits']
    ],
    [
        'title'       => 'Prime Outfit',
        'url'         => 'https://primeoutfit.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Fashion & Apparel',
        'icon'        => 'fa-vest',
        'color'       => 'warning',
        'description' => 'Trendy fashion apparel, denim wear, and accessories online store with size guides and integrated courier delivery tracking.',
        'tech'        => ['PHP', 'MySQL', 'Variant Selector', 'Payment Gateway']
    ],
    [
        'title'       => 'Sri Lanka Therapist',
        'url'         => 'https://srilankatherapist.com',
        'category'    => 'wellness',
        'badge'       => 'Therapy & Healthcare',
        'icon'        => 'fa-user-doctor',
        'color'       => 'info',
        'description' => 'Nationwide portal connecting certified wellness therapists, clinical psychologists, and holistic healers with patients.',
        'tech'        => ['PHP', 'MySQL', 'Directory Listing', 'Booking Calendar']
    ],
    [
        'title'       => 'Super JCC',
        'url'         => 'https://super.jcc.lk',
        'category'    => 'ecommerce',
        'badge'       => 'Supermarket Portal',
        'icon'        => 'fa-cart-flatbed',
        'color'       => 'success',
        'description' => 'Large-scale supermarket chain ordering portal with category navigation, real-time discount vouchers, and door-to-door delivery.',
        'tech'        => ['PHP', 'MySQL', 'Voucher Engine', 'Stock Ledger']
    ],
    [
        'title'       => 'Swasthika Wedagedara',
        'url'         => 'https://swasthikawedagedara.com',
        'category'    => 'wellness',
        'badge'       => 'Ayurveda & Healing Center',
        'icon'        => 'fa-leaf',
        'color'       => 'success',
        'description' => 'Centuries-old authentic Sri Lankan Ayurveda medicine and wellness retreat portal featuring indigenous treatment plans and online herbal clinic booking.',
        'tech'        => ['PHP', 'MySQL', 'Ayurveda Treatments', 'Appointment Engine']
    ],
    [
        'title'       => 'Vandila Tours',
        'url'         => 'https://vandilatours.com',
        'category'    => 'tourism',
        'badge'       => 'Wildlife & Sightseeing',
        'icon'        => 'fa-camera',
        'color'       => 'warning',
        'description' => 'Eco-tourism, whale watching, and national park safari packages platform with instant booking calculation.',
        'tech'        => ['PHP', 'MySQL', 'Trip Customizer', 'WhatsApp Link']
    ],
    [
        'title'       => 'WW Pawns (Global)',
        'url'         => 'https://wwpawns.com',
        'category'    => 'corporate',
        'badge'       => 'Financial & Valuation',
        'icon'        => 'fa-coins',
        'color'       => 'warning',
        'description' => 'International gold valuation, luxury asset pawnbroking, and appraisal tracking portal with secure client consultation forms.',
        'tech'        => ['Core PHP', 'MySQL', 'Gold Rate Calculator', 'Security Hardening']
    ],
    [
        'title'       => 'WW Pawns (Sri Lanka)',
        'url'         => 'https://wwpawns.lk',
        'category'    => 'corporate',
        'badge'       => 'Licensed Financial Portal',
        'icon'        => 'fa-scale-balanced',
        'color'       => 'info',
        'description' => 'Sri Lankan gold loan, collateral assessment, and branch locator portal with loan installment interest calculators.',
        'tech'        => ['PHP', 'MySQL', 'Loan EMI Calculator', 'Branch Locator']
    ],
    [
        'title'       => 'Lasana',
        'url'         => 'https://lasana.com',
        'category'    => 'ecommerce',
        'badge'       => 'Luxury Flowers & Hampers',
        'icon'        => 'fa-gift',
        'color'       => 'danger',
        'description' => 'Premium floral arrangements, celebration gift hampers, and luxury event decor online store with same-day delivery scheduling and midnight surprise gifting.',
        'tech'        => ['PHP', 'MySQL', 'Delivery Date Picker', 'Stripe & PayHere', 'Cart System']
    ]
];
?>

<div class="py-5" style="background: radial-gradient(circle at 80% 80%, rgba(245, 158, 11, 0.05) 0%, transparent 60%), #f8fafc;">
    <div class="container py-4">

        <!-- Page Header -->
        <div class="text-center max-w-700 mx-auto mb-4">
            <span class="badge bg-warning bg-opacity-20 text-warning-emphasis border border-warning px-3 py-2 mb-2 fw-bold anim-fade-down">
                <i class="fa-solid fa-trophy me-1"></i> PROVEN TRACK RECORD
            </span>
            <h1 class="text-dark fw-bold display-5 anim-fade-up anim-delay-1">Our Success Portfolio</h1>
            <p class="text-secondary fs-5 anim-fade-up anim-delay-2">
                Over <strong>500+ successful projects delivered</strong> by <strong>Chandila Web Development</strong>. Explore our live client implementations across Sri Lanka and worldwide.
            </p>
        </div>

        <!-- 500+ Milestone Highlight Strip -->
        <div class="chandila-card p-4 mb-5 border-warning bg-white anim-fade-up anim-delay-3" style="border-width: 1.5px;">
            <div class="row align-items-center g-3 text-center text-md-start">
                <div class="col-md-auto text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 68px; height: 68px; background: #fef3c7; border: 2px solid #f59e0b;">
                        <i class="fa-solid fa-award fa-2x text-warning"></i>
                    </div>
                </div>
                <div class="col-md">
                    <h4 class="text-dark fw-bold mb-1">500+ Projects Completed & Running Live</h4>
                    <p class="text-secondary small mb-0">
                        From high-traffic e-commerce marketplaces and cloud POS systems to luxury tourism portals, healthcare booking platforms, and institutional management systems, our clients rely on our scalable architecture and transparent project management.
                    </p>
                </div>
                <div class="col-md-auto text-center">
                    <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-chandila-primary btn-lg shadow-sm">
                        <i class="fa-solid fa-paper-plane me-2"></i> Start Your Project
                    </a>
                </div>
            </div>
        </div>

        <!-- Category Filter Buttons -->
        <div class="d-flex flex-wrap justify-content-center gap-2 mb-5" id="portfolioFilter">
            <button class="btn btn-sm btn-outline-dark filter-btn active" data-filter="all">
                <i class="fa-solid fa-layer-group me-1"></i> All Projects (<?php echo count($realProjects); ?>+)
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="ecommerce">
                <i class="fa-solid fa-cart-shopping me-1"></i> E-Commerce & Retail
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="tourism">
                <i class="fa-solid fa-plane-departure me-1"></i> Travel & Tourism
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="wellness">
                <i class="fa-solid fa-spa me-1"></i> Wellness & Healthcare
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="pos">
                <i class="fa-solid fa-cash-register me-1"></i> Cloud POS & Retail
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="corporate">
                <i class="fa-solid fa-building me-1"></i> Corporate & Enterprise
            </button>
            <button class="btn btn-sm btn-outline-dark filter-btn" data-filter="services">
                <i class="fa-solid fa-cubes me-1"></i> Digital Services & Media
            </button>
            <a href="<?php echo BASE_URL; ?>/posts.php" class="btn btn-sm btn-warning fw-bold text-dark">
                <i class="fa-solid fa-photo-film me-1"></i> 1080x1080 Showcases
            </a>
        </div>

        <!-- Portfolio Cards Grid -->
        <div class="row g-4" id="portfolioGrid">
            <?php foreach ($realProjects as $p): ?>
                <div class="col-md-6 col-lg-4 portfolio-item" data-category="<?php echo htmlspecialchars($p['category']); ?>">
                    <div class="portfolio-card">
                        <!-- Card Header -->
                        <div class="portfolio-card-header">
                            <span class="badge bg-<?php echo $p['color']; ?> bg-opacity-10 text-<?php echo $p['color']; ?> border border-<?php echo $p['color']; ?> border-opacity-25 px-2 py-1">
                                <i class="fa-solid <?php echo htmlspecialchars($p['icon']); ?> me-1"></i> <?php echo htmlspecialchars($p['badge']); ?>
                            </span>
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 small">
                                <i class="fa-solid fa-circle-check me-1"></i> Live
                            </span>
                        </div>

                        <!-- Card Body -->
                        <div class="portfolio-card-body">
                            <div>
                                <h4 class="text-dark fw-bold mb-2"><?php echo htmlspecialchars($p['title']); ?></h4>
                                <p class="text-secondary small mb-3">
                                    <?php echo htmlspecialchars($p['description']); ?>
                                </p>
                            </div>

                            <div>
                                <!-- Tech Tags -->
                                <div class="d-flex flex-wrap gap-1 mb-3 pt-3 border-top border-secondary border-opacity-10">
                                    <?php foreach ($p['tech'] as $tech): ?>
                                        <span class="badge bg-light text-dark border border-secondary border-opacity-25" style="font-size: 0.72rem; font-weight: 500;">
                                            <?php echo htmlspecialchars($tech); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Live Website Link Button -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="<?php echo htmlspecialchars($p['url']); ?>" target="_blank" rel="noopener noreferrer" class="portfolio-url-badge text-truncate" style="max-width: 190px;" title="Visit <?php echo htmlspecialchars($p['url']); ?>">
                                        <i class="fa-solid fa-arrow-up-right-from-square"></i>
                                        <span><?php echo preg_replace('#^https?://#', '', htmlspecialchars($p['url'])); ?></span>
                                    </a>

                                    <a href="<?php echo BASE_URL; ?>/request_project.php?ref=<?php echo urlencode($p['title']); ?>" class="btn btn-sm btn-outline-primary" title="Build a similar project">
                                        <i class="fa-solid fa-plus me-1"></i> Build Similar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom Call to Action -->
        <div class="mt-5 p-4 p-md-5 rounded-4 text-center bg-white border border-secondary border-opacity-25 shadow-sm">
            <h3 class="text-dark fw-bold mb-2">Want Your Project Featured Among Our 500+ Success Stories?</h3>
            <p class="text-secondary mb-4 mx-auto" style="max-width: 650px;">
                Whether you need a cutting-edge e-commerce store, a bespoke cloud POS application, or a corporate digital portal, we engineer systems with guaranteed deadlines and real-time Job ID milestone tracking.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="<?php echo BASE_URL; ?>/request_project.php" class="btn btn-gold btn-lg">
                    <i class="fa-solid fa-paper-plane me-2"></i> Submit Requirement & Receive Job ID
                </a>
                <a href="https://wa.me/9476843340" target="_blank" class="btn btn-outline-success btn-lg">
                    <i class="fa-brands fa-whatsapp me-2"></i> WhatsApp: 076 843 3340
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Interactive Portfolio Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterButtons = document.querySelectorAll('#portfolioFilter .filter-btn');
    const items = document.querySelectorAll('#portfolioGrid .portfolio-item');

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            filterButtons.forEach(b => b.classList.remove('active', 'btn-dark'));
            filterButtons.forEach(b => b.classList.add('btn-outline-dark'));

            this.classList.add('active', 'btn-dark');
            this.classList.remove('btn-outline-dark');

            const filterValue = this.getAttribute('data-filter');

            items.forEach(item => {
                const category = item.getAttribute('data-category');
                if (filterValue === 'all' || category === filterValue) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
