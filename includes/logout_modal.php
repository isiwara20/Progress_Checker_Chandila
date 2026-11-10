<?php
/**
 * Chandila Web Development – Reusable Logout Confirmation Modal
 */
?>
<!-- Logout Confirmation Modal -->
<div class="modal fade" id="chandilaLogoutModal" tabindex="-1" aria-labelledby="chandilaLogoutModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; border: 1px solid #e2e8f0; background: #ffffff;">
            <div class="modal-body p-4 text-center">
                <!-- Warning / Sign-Out Icon Badge -->
                <div class="mb-3 d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 60px; height: 60px; background-color: #fee2e2; color: #dc2626;">
                    <i class="fa-solid fa-arrow-right-from-bracket fa-2x"></i>
                </div>
                
                <h5 class="fw-bold text-dark mb-2" id="chandilaLogoutModalLabel">Confirm Sign Out</h5>
                
                <p class="text-secondary small mb-4">
                    Are you sure you want to log out of your session? You will need to re-enter your credentials to sign in again.
                </p>

                <div class="d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-light border px-4 fw-semibold text-secondary" data-bs-dismiss="modal">
                        <i class="fa-solid fa-xmark me-1"></i> Cancel
                    </button>
                    <a href="<?php echo BASE_URL; ?>/logout.php" class="btn btn-danger px-4 fw-semibold shadow-sm" id="confirmLogoutBtn">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Yes, Log Out
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

