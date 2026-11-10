/**
 * Chandila Web Development – Modern Bottom-Right Corner Toast Notification System
 * High-performance, floating popups with auto-dismiss timers and hover pause
 */

(function () {
    // Dismiss toast with slide-out animation
    window.dismissChandilaToast = function (toastId) {
        const el = typeof toastId === 'string' ? document.getElementById(toastId) : toastId;
        if (!el) return;
        el.classList.add('toast-dismissing');
        setTimeout(() => {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 300);
    };

    // Auto-dismiss helper with pause on hover
    function setupToastAutoDismiss(toastEl, duration = 5000) {
        if (!toastEl || toastEl.hasAttribute('data-timer-attached')) return;
        toastEl.setAttribute('data-timer-attached', 'true');

        let remaining = duration;
        let timerStart = Date.now();
        let timeoutId = null;

        const startTimer = () => {
            timerStart = Date.now();
            timeoutId = setTimeout(() => {
                window.dismissChandilaToast(toastEl);
            }, remaining);
        };

        const pauseTimer = () => {
            if (timeoutId) {
                clearTimeout(timeoutId);
                remaining -= (Date.now() - timerStart);
            }
        };

        toastEl.addEventListener('mouseenter', pauseTimer);
        toastEl.addEventListener('mouseleave', () => {
            if (remaining > 0) {
                startTimer();
            }
        });

        startTimer();
    }

    // Programmatic toast creator
    window.showChandilaToast = function (type = 'info', message = '', title = '', duration = 5000) {
        let container = document.getElementById('chandilaToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'chandilaToastContainer';
            container.className = 'chandila-toast-container';
            container.setAttribute('aria-live', 'polite');
            document.body.appendChild(container);
        }

        const config = {
            success: {
                title: title || 'Action Completed',
                icon: 'fa-solid fa-circle-check',
                border: '#10b981',
                color: '#059669',
                bg: '#ecfdf5'
            },
            error: {
                title: title || 'Attention Required',
                icon: 'fa-solid fa-circle-exclamation',
                border: '#ef4444',
                color: '#dc2626',
                bg: '#fef2f2'
            },
            warning: {
                title: title || 'System Notice',
                icon: 'fa-solid fa-triangle-exclamation',
                border: '#f59e0b',
                color: '#d97706',
                bg: '#fffbeb'
            },
            info: {
                title: title || 'Update',
                icon: 'fa-solid fa-circle-info',
                border: '#2563eb',
                color: '#2563eb',
                bg: '#eff6ff'
            }
        }[type] || {
            title: title || 'Notification',
            icon: 'fa-solid fa-bell',
            border: '#2563eb',
            color: '#2563eb',
            bg: '#eff6ff'
        };

        const toastId = 'toast_' + Math.random().toString(36).substring(2, 9);
        const card = document.createElement('div');
        card.className = `chandila-toast-card toast-type-${type}`;
        card.id = toastId;
        card.setAttribute('role', 'alert');
        card.style.borderLeftColor = config.border;

        card.innerHTML = `
            <div class="chandila-toast-body">
                <div class="chandila-toast-icon" style="background: ${config.bg}; color: ${config.color};">
                    <i class="${config.icon}"></i>
                </div>
                <div class="chandila-toast-content">
                    <div class="chandila-toast-header">
                        <span class="chandila-toast-title">${config.title}</span>
                        <span class="chandila-toast-time">Just now</span>
                    </div>
                    <div class="chandila-toast-message">${message}</div>
                </div>
                <button type="button" class="chandila-toast-close" onclick="dismissChandilaToast('${toastId}')" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="chandila-toast-progress-bar" style="background-color: ${config.border}; animation-duration: ${duration}ms;"></div>
        `;

        container.appendChild(card);
        setupToastAutoDismiss(card, duration);
        return toastId;
    };

    // Auto-init for toasts pre-rendered by PHP
    function initPreRenderedToasts() {
        document.querySelectorAll('.chandila-toast-card').forEach(toast => {
            setupToastAutoDismiss(toast, 5000);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initPreRenderedToasts);
    } else {
        initPreRenderedToasts();
    }
})();

