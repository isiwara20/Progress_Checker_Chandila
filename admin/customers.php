<?php
$adminTitle = 'Manage Customers';
require_once __DIR__ . '/../includes/admin_header.php';

// Fetch Customers along with their project counts and total revenue
$customers = $pdo->query("
    SELECT c.*, 
           COUNT(p.id) AS total_projects,
           COALESCE(SUM(p.total_cost), 0) AS total_spent,
           COALESCE(SUM(p.total_paid), 0) AS total_paid
    FROM customers c
    LEFT JOIN projects p ON c.id = p.customer_id
    GROUP BY c.id
    ORDER BY c.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-dark fw-bold mb-1"><i class="fa-solid fa-users text-primary me-2"></i> Customer Directory</h3>
        <p class="text-secondary small mb-0">Total Customers: <strong><?php echo count($customers); ?></strong> • View contact details and project history</p>
    </div>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-chandila">
            <thead>
                <tr>
                    <th>Customer Name</th>
                    <th>Company / Business</th>
                    <th>Email Address</th>
                    <th>WhatsApp & Phone</th>
                    <th>Total Projects</th>
                    <th>Total Value</th>
                    <th>Total Paid</th>
                    <th>Registered</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-secondary py-5">No customers registered yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $cust): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($cust['full_name']); ?></div>
                            </td>
                            <td>
                                <?php echo htmlspecialchars($cust['company_name'] ?: '—'); ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo htmlspecialchars($cust['email']); ?>" class="text-primary">
                                    <?php echo htmlspecialchars($cust['email']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-dark"><?php echo htmlspecialchars($cust['whatsapp_number'] ?: $cust['phone']); ?></span>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $cust['whatsapp_number'] ?: $cust['phone']); ?>" target="_blank" class="text-success" title="Chat on WhatsApp">
                                        <i class="fa-brands fa-whatsapp"></i>
                                    </a>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-20"><?php echo (int)$cust['total_projects']; ?> Projects</span>
                            </td>
                            <td class="fw-semibold text-dark">
                                <?php echo formatCurrency($cust['total_spent']); ?>
                            </td>
                            <td class="fw-semibold text-success">
                                <?php echo formatCurrency($cust['total_paid']); ?>
                            </td>
                            <td class="small text-secondary">
                                <?php echo formatDateOnly($cust['created_at']); ?>
                            </td>
                            <td class="text-end">
                                <a href="<?php echo BASE_URL; ?>/admin/projects.php?q=<?php echo urlencode($cust['full_name']); ?>" class="btn btn-sm btn-outline-primary" title="View Customer's Projects">
                                    <i class="fa-solid fa-folder-open me-1"></i> Projects
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/admin_footer.php'; ?>

