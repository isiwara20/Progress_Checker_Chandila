/**
 * Chandila Web Development – Public Website JavaScript
 */

// 0. Site Animated Preloader (Initial Page Load)
(function () {
    function hidePreloader() {
        const preloader = document.getElementById('sitePreloader');
        if (preloader && !preloader.classList.contains('fade-out')) {
            preloader.classList.add('fade-out');
            setTimeout(() => {
                preloader.style.display = 'none';
            }, 550);
        }
    }

    // Dismiss preloader smoothly after page loads
    window.addEventListener('load', function () {
        setTimeout(hidePreloader, 400);
    });

    // Fallback safety timeout (ensures preloader never hangs if CDN is slow)
    setTimeout(hidePreloader, 1500);
})();

document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Bootstrap Tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Copy Job ID helper
    const copyBtns = document.querySelectorAll('.btn-copy-job-id');
    copyBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const jobId = this.getAttribute('data-job-id') || this.innerText.trim();
            navigator.clipboard.writeText(jobId).then(() => {
                const originalHtml = this.innerHTML;
                this.innerHTML = '<i class="fa-solid fa-check text-success"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = originalHtml;
                }, 2000);
            }).catch(err => {
                console.error('Copy failed: ', err);
            });
        });
    });

    // 3. File upload size & type check in Request Form
    const fileInput = document.getElementById('project_attachment');
    if (fileInput) {
        fileInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                const maxSize = 10 * 1024 * 1024; // 10MB
                const allowedExtensions = ['pdf', 'doc', 'docx', 'png', 'jpg', 'jpeg', 'zip', 'txt'];
                const fileExt = file.name.split('.').pop().toLowerCase();

                if (file.size > maxSize) {
                    alert('File is too large! Maximum allowed size is 10MB.');
                    this.value = '';
                    return;
                }

                if (!allowedExtensions.includes(fileExt)) {
                    alert('Invalid file format. Please upload PDF, Word DOC, PNG, JPG, or ZIP.');
                    this.value = '';
                    return;
                }

                // Show selected file indicator
                const fileHelper = document.getElementById('file_selected_helper');
                if (fileHelper) {
                    fileHelper.innerText = `Selected: ${file.name} (${(file.size / (1024 * 1024)).toFixed(2)} MB)`;
                    fileHelper.classList.remove('d-none');
                }
            }
        });
    }

    // 4. Quick Track Form Validation
    const quickTrackForm = document.getElementById('quickTrackForm');
    if (quickTrackForm) {
        quickTrackForm.addEventListener('submit', function (e) {
            const jobIdInput = document.getElementById('quick_job_id');
            if (jobIdInput && !jobIdInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a valid Job ID (e.g., CHW-2026-0001)');
                jobIdInput.focus();
            }
        });
    }
});

