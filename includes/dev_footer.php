<?php
/**
 * Chandila Web Development – Developer Portal Footer
 */
?>
        </main>

        <footer class="py-3 px-4 border-top border-secondary border-opacity-25 text-secondary small d-flex justify-content-between align-items-center">
            <div>
                &copy; <?php echo date('Y'); ?> <strong>Chandila Web Development</strong> • Developer Portal
            </div>
            <div>
                <span class="badge bg-dark border border-secondary text-info">Timezone: Asia/Colombo</span>
            </div>
        </footer>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    window.BASE_URL = '<?php echo BASE_URL; ?>';
</script>
<script src="<?php echo BASE_URL; ?>/assets/js/admin.js"></script>
</body>
</html>

