<?php
/**
 * Chandila Web Development – Customer Care Footer Component
 */
?>
        </main>
        <!-- Body Content Wrapper Ends -->

        <!-- Customer Care Footer Note -->
        <footer class="py-3 px-4 border-top border-secondary border-opacity-25 text-secondary small d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                &copy; <?php echo date('Y'); ?> <strong>Chandila Web Development</strong> • Customer Care Operations Desk
            </div>
            <div>
                <span class="badge bg-dark border border-secondary text-info">Timezone: Asia/Colombo</span>
            </div>
        </footer>
    </div>
</div>

<!-- Logout Confirmation Modal Component -->
<?php require_once __DIR__ . '/logout_modal.php'; ?>

<!-- Bootstrap 5.3.3 Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Chart.js 4.4 CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<!-- Global Base URL for AJAX scripts -->
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<!-- Toast Notification Scripts -->
<script src="<?php echo BASE_URL; ?>/assets/js/toast.js"></script>
<!-- Custom Scripts -->
<script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>

