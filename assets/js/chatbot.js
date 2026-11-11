/**
 * Chandila Web Development – Virtual Assistant & Project Tracking Chatbot Controller
 * Handles conversation state, API interaction, real-time project card rendering, and UI animations.
 */

(function () {
    'use strict';

    // State Constants & Storage Keys
    const STORAGE_KEY_HISTORY = 'chandila_chat_history';
    const STORAGE_KEY_STATE   = 'chandila_chat_is_open';
    const STORAGE_KEY_SOUND   = 'chandila_chat_sound_enabled';

    // Storage Safe Helpers (Never throw in private/incognito mode)
    function safeGetSession(key) {
        try {
            return sessionStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }
    function safeSetSession(key, val) {
        try {
            sessionStorage.setItem(key, val);
        } catch (e) {}
    }
    function safeGetLocal(key) {
        try {
            return localStorage.getItem(key);
        } catch (e) {
            return null;
        }
    }
    function safeSetLocal(key, val) {
        try {
            localStorage.setItem(key, val);
        } catch (e) {}
    }

    // Global Base URL Resolver
    const BASE_URL = (function () {
        if (typeof window.CHANDILA_BASE_URL !== 'undefined') {
            return window.CHANDILA_BASE_URL.replace(/\/+$/, '');
        }
        const script = document.querySelector('script[src*="chatbot.js"]');
        if (script) {
            const src = script.getAttribute('src');
            const idx = src.indexOf('/assets/js/chatbot.js');
            if (idx !== -1) {
                return src.substring(0, idx);
            }
        }
        return '';
    })();

    // DOM Elements
    let windowEl, launcherEl, launcherBtnEl, pillEl, bodyEl, chipsEl, formEl, inputEl, sendBtnEl;
    let closeBtnEl, minimizeBtnEl, clearBtnEl, soundBtnEl;

    let soundEnabled = safeGetLocal(STORAGE_KEY_SOUND) !== '0';
    let isWaitingForResponse = false;
    let messageHistory = [];
    let lastToggleTimestamp = 0;

    // Synthesized gentle chime using Web Audio API (No external sound files required)
    function playChime(type = 'receive') {
        if (!soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            const ctx = new AudioCtx();
            const now = ctx.currentTime;

            if (type === 'receive') {
                const osc1 = ctx.createOscillator();
                const osc2 = ctx.createOscillator();
                const gain = ctx.createGain();

                osc1.type = 'sine';
                osc2.type = 'sine';
                osc1.frequency.setValueAtTime(659.25, now);       // E5
                osc2.frequency.setValueAtTime(830.61, now + 0.08); // G#5

                gain.gain.setValueAtTime(0.08, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.35);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(ctx.destination);

                osc1.start(now);
                osc1.stop(now + 0.12);
                osc2.start(now + 0.08);
                osc2.stop(now + 0.35);
            } else if (type === 'send') {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(440, now);
                osc.frequency.exponentialRampToValueAtTime(220, now + 0.08);
                gain.gain.setValueAtTime(0.06, now);
                gain.gain.exponentialRampToValueAtTime(0.001, now + 0.08);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start(now);
                osc.stop(now + 0.08);
            }
        } catch (e) {}
    }

    // Markdown Parser
    function parseMarkdown(md) {
        if (!md) return '';
        let html = md
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');

        html = html.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+|tel:[^\s\)]+|mailto:[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" class="chat-link">$1 <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:0.7em;"></i></a>');
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/(^|[^*])\*([^*]+)\*/g, '$1<em>$2</em>');
        html = html.replace(/`([^`]+)`/g, '<code class="chat-inline-code">$1</code>');
        html = html.replace(/^[•\-\*]\s+(.*)$/gm, '<li class="chat-list-item">$1</li>');
        html = html.replace(/(<li class="chat-list-item">.*<\/li>)/s, '<ul class="chat-list">$1</ul>');
        html = html.replace(/\n\n/g, '<div class="chat-paragraph-gap"></div>');
        html = html.replace(/\n/g, '<br>');

        return html;
    }

    // Toggle Chat Window with Debounce Guard
    function toggleChat() {
        const now = Date.now();
        if (now - lastToggleTimestamp < 300) {
            return;
        }
        lastToggleTimestamp = now;

        if (!windowEl) windowEl = document.getElementById('chandilaChatbotWindow');
        if (!windowEl) return;

        if (windowEl.classList.contains('is-open')) {
            closeChat();
        } else {
            openChat(true);
        }
    }

    function updateLauncherIcon(isOpen) {
        if (!launcherBtnEl) launcherBtnEl = document.getElementById('chandilaChatbotBtn');
        if (!launcherBtnEl) return;

        if (isOpen) {
            launcherBtnEl.innerHTML = '<i class="fa-solid fa-xmark"></i>';
            launcherBtnEl.setAttribute('aria-label', 'Close Chat');
            launcherBtnEl.setAttribute('title', 'Close Chat');
        } else {
            launcherBtnEl.innerHTML = '<i class="fa-solid fa-comments"></i><span class="btn-badge-dot"></span>';
            launcherBtnEl.setAttribute('aria-label', 'Open Chatbot & Order Tracker');
            launcherBtnEl.setAttribute('title', 'Chandila Virtual Assistant & Live Project Tracker');
        }
    }

    function openChat(focusInput = true) {
        if (!windowEl) windowEl = document.getElementById('chandilaChatbotWindow');
        if (!windowEl) return;

        windowEl.style.display = 'flex';
        // Force reflow
        void windowEl.offsetWidth;
        windowEl.classList.add('is-open');

        updateLauncherIcon(true);
        safeSetSession(STORAGE_KEY_STATE, '1');

        if (!pillEl) pillEl = document.getElementById('chandilaChatbotPill');
        if (pillEl) {
            pillEl.style.opacity = '0';
            pillEl.style.pointerEvents = 'none';
        }

        scrollToBottom();

        if (focusInput) {
            if (!inputEl) inputEl = document.getElementById('chandilaChatInput');
            if (inputEl) {
                setTimeout(() => {
                    try { inputEl.focus(); } catch (e) {}
                }, 250);
            }
        }
    }

    function closeChat() {
        if (!windowEl) windowEl = document.getElementById('chandilaChatbotWindow');
        if (!windowEl) return;

        windowEl.classList.remove('is-open');
        updateLauncherIcon(false);
        safeSetSession(STORAGE_KEY_STATE, '0');

        setTimeout(() => {
            if (windowEl && !windowEl.classList.contains('is-open')) {
                windowEl.style.display = 'none';
            }
        }, 280);

        if (!pillEl) pillEl = document.getElementById('chandilaChatbotPill');
        if (pillEl) {
            pillEl.style.opacity = '1';
            pillEl.style.pointerEvents = 'auto';
        }
    }

    function toggleSound() {
        soundEnabled = !soundEnabled;
        safeSetLocal(STORAGE_KEY_SOUND, soundEnabled ? '1' : '0');
        updateSoundButtonUi();
        if (soundEnabled) playChime('receive');
    }

    function updateSoundButtonUi() {
        if (!soundBtnEl) soundBtnEl = document.getElementById('chandilaChatSoundBtn');
        if (!soundBtnEl) return;

        if (soundEnabled) {
            soundBtnEl.innerHTML = '<i class="fa-solid fa-volume-high"></i>';
            soundBtnEl.title = 'Mute sound notification';
        } else {
            soundBtnEl.innerHTML = '<i class="fa-solid fa-volume-xmark"></i>';
            soundBtnEl.title = 'Unmute sound notification';
        }
    }

    function clearChatHistory() {
        if (confirm('Would you like to clear your current conversation and start fresh?')) {
            try { sessionStorage.removeItem(STORAGE_KEY_HISTORY); } catch (e) {}
            messageHistory = [];
            if (bodyEl) bodyEl.innerHTML = '';
            if (chipsEl) chipsEl.innerHTML = '';
            fetchWelcomeMessage();
        }
    }

    // Render message into chat body
    function appendMessage(sender, text, projectCard = null, save = true) {
        if (!bodyEl) bodyEl = document.getElementById('chandilaChatBody');
        if (!bodyEl) return;

        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const row = document.createElement('div');
        row.className = `chat-msg-row ${sender}`;

        let cardHtml = '';
        if (projectCard) {
            cardHtml = renderProjectCardHtml(projectCard);
        }

        row.innerHTML = `
            <div class="chat-msg-content">
                <div class="chat-bubble">
                    ${parseMarkdown(text)}
                    ${cardHtml}
                </div>
                <div class="chat-time">${timeStr}</div>
            </div>
        `;

        bodyEl.appendChild(row);
        scrollToBottom();

        if (save) {
            messageHistory.push({
                sender,
                text,
                projectCard,
                timeStr
            });
            saveHistory();
        }
    }

    // Render Project Status Card
    function renderProjectCardHtml(card) {
        const pct = Math.min(100, Math.max(0, parseInt(card.progress_percentage || 0, 10)));
        const stageNum = card.stage_number || 1;
        const totalMilestones = card.milestones_total || 5;
        const doneMilestones = card.milestones_done || 0;

        let financialHtml = '';
        if (card.is_verified) {
            financialHtml = `
                <div class="project-card-financial">
                    <div class="financial-item">
                        <div class="f-label">Total Cost</div>
                        <div class="f-val">${card.total_cost || 'N/A'}</div>
                    </div>
                    <div class="financial-item">
                        <div class="f-label">Paid to Date</div>
                        <div class="f-val text-success">${card.total_paid || 'N/A'}</div>
                    </div>
                    <div class="financial-item due">
                        <div class="f-label">Balance Due</div>
                        <div class="f-val">${card.balance_due || 'N/A'}</div>
                    </div>
                </div>
            `;
        }

        let actionsHtml = `
            <a href="${card.tracker_url}" target="_blank" class="btn-card-action primary">
                <i class="fa-solid fa-gauge"></i> Full Tracker
            </a>
            <a href="${card.invoice_url}" target="_blank" class="btn-card-action secondary">
                <i class="fa-solid fa-file-invoice"></i> Official Invoice
            </a>
            <a href="https://wa.me/9476843340?text=Hello%20Chandila%20Web,%20I%20am%20inquiring%20about%20my%20project%20${encodeURIComponent(card.job_id)}" target="_blank" class="btn-card-action whatsapp">
                <i class="fa-brands fa-whatsapp"></i> WhatsApp Lead
            </a>
        `;

        return `
            <div class="chat-project-card">
                <div class="project-card-header">
                    <span class="project-card-job-badge"><i class="fa-solid fa-barcode me-1"></i>${card.job_id}</span>
                    <span class="project-card-stage-badge"><i class="fa-solid fa-circle-check text-success me-1"></i>Stage ${stageNum}/13</span>
                </div>
                <h6 class="project-card-title">${card.project_title}</h6>

                <div class="project-card-progress">
                    <div class="progress-labels">
                        <span>${card.status}</span>
                        <span>${pct}%</span>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: ${pct}%;"></div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1" style="font-size:0.72rem; color:#64748b;">
                        <span><i class="fa-solid fa-list-check me-1"></i>${doneMilestones} of ${totalMilestones} Milestones</span>
                        <span><i class="fa-solid fa-calendar-day me-1"></i>Target: ${card.expected_completion}</span>
                    </div>
                </div>

                <div class="project-card-meta">
                    <div class="meta-item">
                        <span class="meta-label">Project Type</span>
                        <span class="meta-value">${card.project_type || 'Web Application'}</span>
                    </div>
                    <div class="meta-item">
                        <span class="meta-label">Assigned Lead</span>
                        <span class="meta-value">${card.developer_name || 'Senior Architect'}</span>
                    </div>
                </div>

                ${financialHtml}

                ${card.latest_update ? `
                    <div class="project-card-note">
                        <i class="fa-solid fa-clock-rotate-left me-1 text-warning"></i>
                        <strong>Latest Update:</strong> ${card.latest_update}
                    </div>
                ` : ''}

                <div class="project-card-actions">
                    ${actionsHtml}
                </div>
            </div>
        `;
    }

    // Render Quick Action Chips
    function renderChips(chips) {
        if (!chipsEl) chipsEl = document.getElementById('chandilaChatChips');
        if (!chipsEl) return;
        chipsEl.innerHTML = '';
        if (!chips || chips.length === 0) return;

        chips.forEach(chip => {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'chat-chip';
            btn.innerHTML = chip.label;
            btn.onclick = function (e) {
                e.preventDefault();
                handleChipClick(chip);
            };
            chipsEl.appendChild(btn);
        });
    }

    // Handle Chip Click
    function handleChipClick(chip) {
        if (isWaitingForResponse) return;

        if (chip.action === 'open_link' && chip.url) {
            window.open(chip.url, '_blank');
            return;
        }

        if (chip.action === 'whatsapp') {
            window.open('https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20am%20chatting%20from%20the%20website%20and%20would%20like%20assistance.', '_blank');
            return;
        }

        if (chip.action === 'whatsapp_job' && chip.job_id) {
            window.open(`https://wa.me/9476843340?text=Hello%20Chandila%20Web%20Development,%20I%20am%20inquiring%20about%20my%20Job%20ID%20${encodeURIComponent(chip.job_id)}`, '_blank');
            return;
        }

        if (chip.action === 'call') {
            window.location.href = 'tel:0768433340';
            return;
        }

        if (chip.action === 'focus_input') {
            if (!inputEl) inputEl = document.getElementById('chandilaChatInput');
            if (inputEl) {
                inputEl.placeholder = 'Enter registered WhatsApp / phone number...';
                inputEl.focus();
            }
            return;
        }

        if (chip.action === 'send_text' && chip.text) {
            sendMessageToServer(chip.text);
            return;
        }

        sendQuickActionToServer(chip.action, chip.label);
    }

    // Typing Indicator
    let typingRowEl = null;
    function showTypingIndicator() {
        if (!bodyEl) bodyEl = document.getElementById('chandilaChatBody');
        if (!bodyEl || typingRowEl) return;

        typingRowEl = document.createElement('div');
        typingRowEl.className = 'chat-msg-row bot';
        typingRowEl.innerHTML = `
            <div class="chat-msg-content">
                <div class="chatbot-typing">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        `;
        bodyEl.appendChild(typingRowEl);
        scrollToBottom();
    }

    function hideTypingIndicator() {
        if (typingRowEl && typingRowEl.parentNode) {
            typingRowEl.parentNode.removeChild(typingRowEl);
        }
        typingRowEl = null;
    }

    function scrollToBottom() {
        if (!bodyEl) bodyEl = document.getElementById('chandilaChatBody');
        if (bodyEl) {
            setTimeout(() => {
                bodyEl.scrollTop = bodyEl.scrollHeight;
            }, 60);
        }
    }

    function handleUserMessage() {
        if (isWaitingForResponse) return;
        if (!inputEl) inputEl = document.getElementById('chandilaChatInput');
        if (!inputEl) return;

        const text = inputEl.value.trim();
        if (!text) return;

        inputEl.value = '';
        inputEl.placeholder = 'Type a message or Job ID (e.g. CHW-2026-0001)...';
        sendMessageToServer(text);
    }

    function sendMessageToServer(text) {
        appendMessage('user', text, null, true);
        playChime('send');

        isWaitingForResponse = true;
        showTypingIndicator();
        toggleInputState(false);

        fetch(`${BASE_URL}/api/chatbot.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'message',
                message: text
            })
        })
        .then(res => res.json())
        .then(data => {
            hideTypingIndicator();
            isWaitingForResponse = false;
            toggleInputState(true);

            if (data.reply) {
                appendMessage('bot', data.reply, data.project_card || null, true);
                playChime('receive');
            }
            if (data.quick_chips) {
                renderChips(data.quick_chips);
            }
        })
        .catch(() => {
            hideTypingIndicator();
            isWaitingForResponse = false;
            toggleInputState(true);

            appendMessage('bot', "⚠️ I'm having trouble connecting to our server right now. You can chat with our team directly on WhatsApp at [076 843 3340](https://wa.me/9476843340).", null, true);
            renderChips([
                { label: '💬 Open WhatsApp Support', action: 'whatsapp' }
            ]);
        });
    }

    function sendQuickActionToServer(command, label) {
        appendMessage('user', label, null, true);
        playChime('send');

        isWaitingForResponse = true;
        showTypingIndicator();
        toggleInputState(false);

        fetch(`${BASE_URL}/api/chatbot.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                action: 'quick_action',
                command: command
            })
        })
        .then(res => res.json())
        .then(data => {
            hideTypingIndicator();
            isWaitingForResponse = false;
            toggleInputState(true);

            if (data.reply) {
                appendMessage('bot', data.reply, data.project_card || null, true);
                playChime('receive');
            }
            if (data.quick_chips) {
                renderChips(data.quick_chips);
            }
        })
        .catch(() => {
            hideTypingIndicator();
            isWaitingForResponse = false;
            toggleInputState(true);
            appendMessage('bot', "Could not complete request. Please reach us on WhatsApp at [076 843 3340](https://wa.me/9476843340).", null, true);
        });
    }

    function fetchWelcomeMessage() {
        showTypingIndicator();
        fetch(`${BASE_URL}/api/chatbot.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ action: 'init' })
        })
        .then(res => res.json())
        .then(data => {
            hideTypingIndicator();
            if (data.reply) {
                appendMessage('bot', data.reply, null, true);
            }
            if (data.quick_chips) {
                renderChips(data.quick_chips);
            }
        })
        .catch(() => {
            hideTypingIndicator();
            appendMessage('bot', "Hello! 👋 Welcome to **Chandila Web Development**.\n\nHow can I help you today? You can ask about our services, pricing, or enter your **Job ID** (e.g. `CHW-2026-0001`) to track your project progress.", null, true);
            renderChips([
                { label: '🔍 Track My Project', action: 'track_prompt' },
                { label: '💼 Services', action: 'services' },
                { label: '💰 Milestone Escrow', action: 'pricing' },
                { label: '💬 WhatsApp Support', action: 'whatsapp' }
            ]);
        });
    }

    function toggleInputState(enabled) {
        if (!inputEl) inputEl = document.getElementById('chandilaChatInput');
        if (!sendBtnEl) sendBtnEl = document.getElementById('chandilaChatSendBtn');
        if (inputEl) inputEl.disabled = !enabled;
        if (sendBtnEl) sendBtnEl.disabled = !enabled;
        if (enabled && inputEl) inputEl.focus();
    }

    function saveHistory() {
        try {
            const trimmed = messageHistory.slice(-30);
            safeSetSession(STORAGE_KEY_HISTORY, JSON.stringify(trimmed));
        } catch (e) {}
    }

    function loadPersistedHistory() {
        try {
            const raw = safeGetSession(STORAGE_KEY_HISTORY);
            if (raw) {
                const list = JSON.parse(raw);
                if (Array.isArray(list) && list.length > 0) {
                    messageHistory = list;
                    if (bodyEl) bodyEl.innerHTML = '';
                    list.forEach(msg => {
                        appendMessage(msg.sender, msg.text, msg.projectCard || null, false);
                    });
                }
            }
        } catch (e) {
            messageHistory = [];
        }
    }

    // Expose Global Functions
    window.toggleChandilaChatbot = toggleChat;
    window.openChandilaChatbot   = function () { openChat(true); };
    window.closeChandilaChatbot  = closeChat;

    // Initialize UI and Bind Events
    function init() {
        launcherEl    = document.getElementById('chandilaChatbotLauncher');
        launcherBtnEl = document.getElementById('chandilaChatbotBtn');
        pillEl        = document.getElementById('chandilaChatbotPill');
        windowEl      = document.getElementById('chandilaChatbotWindow');
        bodyEl        = document.getElementById('chandilaChatBody');
        chipsEl       = document.getElementById('chandilaChatChips');
        formEl        = document.getElementById('chandilaChatForm');
        inputEl       = document.getElementById('chandilaChatInput');
        sendBtnEl     = document.getElementById('chandilaChatSendBtn');
        closeBtnEl    = document.getElementById('chandilaChatCloseBtn');
        minimizeBtnEl = document.getElementById('chandilaChatMinimizeBtn');
        clearBtnEl    = document.getElementById('chandilaChatClearBtn');
        soundBtnEl    = document.getElementById('chandilaChatSoundBtn');

        if (!launcherEl || !windowEl) return;

        updateSoundButtonUi();
        loadPersistedHistory();

        // Restore open state
        const savedState = safeGetSession(STORAGE_KEY_STATE);
        if (savedState === '1') {
            openChat(false);
        } else {
            windowEl.style.display = 'none';
        }

        // Authoritative Single Click Handlers
        if (launcherBtnEl) {
            launcherBtnEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleChat();
            };
        }
        if (pillEl) {
            pillEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                openChat(true);
            };
        }
        if (closeBtnEl) {
            closeBtnEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeChat();
            };
        }
        if (minimizeBtnEl) {
            minimizeBtnEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                closeChat();
            };
        }
        if (clearBtnEl) {
            clearBtnEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                clearChatHistory();
            };
        }
        if (soundBtnEl) {
            soundBtnEl.onclick = function (e) {
                e.preventDefault();
                e.stopPropagation();
                toggleSound();
            };
        }

        if (formEl) {
            formEl.onsubmit = function (e) {
                e.preventDefault();
                handleUserMessage();
            };
        }

        // Welcome message on first load
        if (messageHistory.length === 0) {
            fetchWelcomeMessage();
        }
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
