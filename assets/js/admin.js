/**
 * Chandila Web Development – Admin & Developer Dashboard Scripts
 * Chart.js Analytics, AJAX Status Updaters & Calculations
 */

document.addEventListener('DOMContentLoaded', function () {
    // 0. Maintain Sidebar Scroll Position Seamlessly Across Page Transitions
    const adminSidebar = document.querySelector('.admin-sidebar');
    if (adminSidebar) {
        const storageKey = 'chandila_admin_sidebar_scroll';
        const savedScroll = sessionStorage.getItem(storageKey);
        if (savedScroll !== null && savedScroll !== '' && !isNaN(savedScroll)) {
            adminSidebar.scrollTop = parseInt(savedScroll, 10);
        } else {
            const activeLink = adminSidebar.querySelector('.admin-menu-link.active');
            if (activeLink) {
                activeLink.scrollIntoView({ block: 'center', behavior: 'instant' });
            }
        }

        // Save position as user scrolls the sidebar (debounced to prevent layout reset overwrites)
        let scrollTimer = null;
        adminSidebar.addEventListener('scroll', function () {
            clearTimeout(scrollTimer);
            scrollTimer = setTimeout(function() {
                sessionStorage.setItem(storageKey, adminSidebar.scrollTop);
            }, 40);
        }, { passive: true });

        // Also capture exact scroll position before following any navigation link
        adminSidebar.querySelectorAll('.admin-menu-link').forEach(link => {
            link.addEventListener('click', function () {
                sessionStorage.setItem(storageKey, adminSidebar.scrollTop);
            });
        });
    }

    // 1. Mobile Sidebar Toggle
    const sidebarToggle = document.getElementById('sidebarToggleBtn');
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function (e) {
            if (window.innerWidth < 992 && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)) {
                sidebar.classList.remove('show');
            }
        });
    }

    // 2. Initialize Chart.js Analytics if canvas elements exist
    initDashboardCharts();

    // 3. Dynamic Payment Calculation in Project Management
    initPaymentCalculators();

    // 4. WhatsApp Manual Resend via AJAX
    initWhatsAppResend();

    // 5. Milestone Checklist AJAX Toggle
    initMilestoneChecklist();

    // 6. Universal Logout Confirmation Modal Interceptor
    initLogoutConfirmation();
});

/**
 * Initialize Admin Dashboard Charts
 */
function initDashboardCharts() {
    // 1. Projects by Status Chart
    const statusChartCanvas = document.getElementById('chartProjectsByStatus');
    if (statusChartCanvas && window.dashboardChartData) {
        const statusData = window.dashboardChartData.statusData || {};
        new Chart(statusChartCanvas, {
            type: 'doughnut',
            data: {
                labels: Object.keys(statusData),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: [
                        '#94a3b8', '#0284c7', '#2563eb', '#f59e0b',
                        '#10b981', '#6366f1', '#06b6d4', '#fbbf24',
                        '#ef4444', '#8b5cf6', '#059669', '#d97706'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#475569', boxWidth: 12, font: { size: 11, weight: '500' } }
                    }
                },
                cutout: '65%'
            }
        });
    }

    // 2. Monthly Revenue Chart
    const revenueChartCanvas = document.getElementById('chartMonthlyRevenue');
    if (revenueChartCanvas && window.dashboardChartData) {
        const revData = window.dashboardChartData.revenueData || { labels: [], amounts: [] };
        new Chart(revenueChartCanvas, {
            type: 'bar',
            data: {
                labels: revData.labels,
                datasets: [{
                    label: 'Revenue (LKR)',
                    data: revData.amounts,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    borderColor: '#1d4ed8',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#64748b' }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    y: { 
                        ticks: { 
                            color: '#64748b',
                            callback: function(value) { return 'Rs. ' + Number(value).toLocaleString(); }
                        }, 
                        grid: { color: 'rgba(0,0,0,0.05)' } 
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) { return ' Revenue: Rs. ' + Number(context.raw).toLocaleString(); }
                        }
                    }
                }
            }
        });
    }

    // 3. Developer Workload Chart
    const devWorkloadCanvas = document.getElementById('chartDevWorkload');
    if (devWorkloadCanvas && window.dashboardChartData) {
        const devData = window.dashboardChartData.devWorkload || { developers: [], counts: [] };
        new Chart(devWorkloadCanvas, {
            type: 'bar',
            data: {
                labels: devData.developers,
                datasets: [{
                    label: 'Active Projects',
                    data: devData.counts,
                    backgroundColor: 'rgba(217, 119, 6, 0.85)',
                    borderColor: '#b45309',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { ticks: { color: '#64748b', stepSize: 1 }, grid: { color: 'rgba(0,0,0,0.05)' } },
                    y: { ticks: { color: '#475569', font: { weight: '600' } }, grid: { display: false } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

    // 4. Completed vs Ongoing Chart
    const completionChartCanvas = document.getElementById('chartCompletedVsOngoing');
    if (completionChartCanvas && window.dashboardChartData) {
        const compData = window.dashboardChartData.completedVsOngoing || [0, 0];
        new Chart(completionChartCanvas, {
            type: 'pie',
            data: {
                labels: ['Completed & Delivered', 'Ongoing / In-Progress'],
                datasets: [{
                    data: compData,
                    backgroundColor: ['#10b981', '#2563eb'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#475569', boxWidth: 12, font: { size: 11, weight: '500' } }
                    }
                }
            }
        });
    }
}

/**
 * Dynamic Payment Balance Calculator
 */
function initPaymentCalculators() {
    const totalCostInput = document.getElementById('calc_total_cost');
    const advanceInput = document.getElementById('calc_advance_amount');
    const milestone2Input = document.getElementById('calc_milestone2_amount');
    const finalAmountInput = document.getElementById('calc_final_amount');
    const autoCalcBtn = document.getElementById('btn_auto_calc_schedule');

    function calculateMilestones() {
        if (!totalCostInput) return;
        const total = parseFloat(totalCostInput.value) || 0;
        
        if (autoCalcBtn && total > 0) {
            // Default 3-stage business arrangement:
            // Advance: 30% or custom, Second Payment: 50% of total, Final: Remaining
            const advance = parseFloat(advanceInput ? advanceInput.value : 0) || Math.round(total * 0.30);
            const milestone2 = Math.round(total * 0.50);
            const finalAmt = Math.max(0, total - advance - milestone2);

            if (advanceInput && !advanceInput.value) advanceInput.value = advance;
            if (milestone2Input) milestone2Input.value = milestone2;
            if (finalAmountInput) finalAmountInput.value = finalAmt;
        } else if (finalAmountInput) {
            const adv = parseFloat(advanceInput ? advanceInput.value : 0) || 0;
            const m2 = parseFloat(milestone2Input ? milestone2Input.value : 0) || 0;
            finalAmountInput.value = Math.max(0, total - adv - m2);
        }
    }

    if (totalCostInput) {
        totalCostInput.addEventListener('input', calculateMilestones);
    }
    if (advanceInput) {
        advanceInput.addEventListener('input', calculateMilestones);
    }
    if (milestone2Input) {
        milestone2Input.addEventListener('input', calculateMilestones);
    }
    if (autoCalcBtn) {
        autoCalcBtn.addEventListener('click', function(e) {
            e.preventDefault();
            calculateMilestones();
        });
    }
}

/**
 * WhatsApp Manual Resend via AJAX
 */
function initWhatsAppResend() {
    document.querySelectorAll('.btn-resend-whatsapp').forEach(btn => {
        btn.addEventListener('click', function () {
            const notifId = this.getAttribute('data-notif-id');
            if (!notifId) return;

            if (!confirm('Are you sure you want to resend this WhatsApp notification?')) {
                return;
            }

            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Resending...';
            this.disabled = true;

            const formData = new FormData();
            formData.append('action', 'resend');
            formData.append('notification_id', notifId);

            fetch((window.BASE_URL || '') + '/api/whatsapp.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    this.innerHTML = '<i class="fa-solid fa-check text-success"></i> Sent!';
                    if (window.showChandilaToast) {
                        showChandilaToast('success', 'WhatsApp notification dispatched successfully to customer!', 'Notification Sent');
                    }
                    const row = document.getElementById('notif_row_' + notifId);
                    if (row) {
                        const statusBadge = row.querySelector('.notif-status-badge');
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-success notif-status-badge';
                            statusBadge.innerText = 'Sent';
                        }
                    }
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 3000);
                } else {
                    if (window.showChandilaToast) {
                        showChandilaToast('error', data.message || 'Failed to resend WhatsApp notification', 'Dispatch Error');
                    } else {
                        alert('Failed to resend: ' + (data.message || 'Unknown error'));
                    }
                    this.innerHTML = originalText;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                if (window.showChandilaToast) {
                    showChandilaToast('error', 'Network or server error. Please try again.', 'Connection Error');
                } else {
                    alert('Connection error. Please try again.');
                }
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    });
}

/**
 * Milestone Checklist Interactive Toggle
 */
function initMilestoneChecklist() {
    document.querySelectorAll('.milestone-check-input').forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const milestoneId = this.getAttribute('data-milestone-id');
            const isCompleted = this.checked ? 1 : 0;

            const formData = new FormData();
            formData.append('action', 'toggle_milestone');
            formData.append('milestone_id', milestoneId);
            formData.append('is_completed', isCompleted);

            fetch((window.BASE_URL || '') + '/api/project_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    if (window.showChandilaToast) {
                        showChandilaToast('error', data.message || 'Failed to update milestone', 'Update Error');
                    } else {
                        alert(data.message || 'Failed to update milestone');
                    }
                    this.checked = !this.checked;
                } else {
                    if (window.showChandilaToast) {
                        showChandilaToast('success', isCompleted ? 'Milestone marked as completed.' : 'Milestone reopened.', 'Sprint Progress');
                    }
                    const label = document.getElementById('milestone_label_' + milestoneId);
                    if (label) {
                        if (isCompleted) {
                            label.classList.add('text-decoration-line-through', 'text-muted');
                        } else {
                            label.classList.remove('text-decoration-line-through', 'text-muted');
                        }
                    }
                }
            })
            .catch(err => {
                console.error(err);
                if (window.showChandilaToast) {
                    showChandilaToast('error', 'Connection error updating milestone.', 'Network Error');
                }
                this.checked = !this.checked;
            });
        });
    });
}

/**
 * Universal Logout Confirmation Modal Handler
 */
function initLogoutConfirmation() {
    document.addEventListener('click', function (e) {
        const logoutTrigger = e.target.closest('a[href*="logout.php"], .admin-logout-trigger, [data-confirm-logout]');
        if (!logoutTrigger) return;

        // Skip if this is the confirmation button inside the modal itself
        if (logoutTrigger.id === 'confirmLogoutBtn') return;

        e.preventDefault();
        const targetUrl = logoutTrigger.getAttribute('href') || ((window.BASE_URL || '') + '/logout.php');

        const modalEl = document.getElementById('chandilaLogoutModal');
        if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const confirmBtn = document.getElementById('confirmLogoutBtn');
            if (confirmBtn) {
                confirmBtn.setAttribute('href', targetUrl);
            }
            const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
            modalInstance.show();
        } else {
            // Graceful native browser dialog fallback
            if (window.confirm('Are you sure you want to log out of your session?')) {
                window.location.href = targetUrl;
            }
        }
    });
}


