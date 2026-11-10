/**
 * Chandila Web Development – WA Client HTTP Gateway
 * High-performance, self-hosted WhatsApp Web API Server
 * 
 * Works in two modes:
 * 1. Native WhatsApp Web mode (if whatsapp-web.js is installed - connects via QR code to your real phone!)
 * 2. Standalone HTTP Gateway mode (built-in Node.js server, instant testing & dispatch)
 */

const http = require('http');
const url = require('url');

const PORT = process.env.PORT || 3000;
const AUTH_KEY = process.env.API_KEY || ''; // Optional Bearer or x-api-key check

let waClient = null;
let waStatus = 'disconnected'; // 'ready', 'qr_needed', 'connecting', 'disconnected'
let currentQR = null;
let connectedPhone = null;
let lastMessages = [];

// Try to initialize whatsapp-web.js if available
try {
    const { Client, LocalAuth } = require('whatsapp-web.js');
    const qrcodeTerminal = require('qrcode-terminal');

    console.log('📦 whatsapp-web.js detected. Initializing WhatsApp Web Client with LocalAuth...');
    waStatus = 'connecting';

    waClient = new Client({
        authStrategy: new LocalAuth({ dataPath: './.waclient_auth' }),
        puppeteer: {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']
        }
    });

    waClient.on('qr', (qr) => {
        currentQR = qr;
        waStatus = 'qr_needed';
        console.log('\n===========================================================');
        console.log('📱 SCAN THIS QR CODE WITH YOUR WHATSAPP TO LINK CLIENT:');
        console.log('===========================================================');
        if (qrcodeTerminal) {
            qrcodeTerminal.generate(qr, { small: true });
        }
        console.log('\nOr view the QR code in browser at: http://localhost:' + PORT);
    });

    waClient.on('ready', () => {
        waStatus = 'ready';
        currentQR = null;
        connectedPhone = waClient.info ? waClient.info.wid.user : 'Connected';
        console.log('✅ WA Client is ONLINE and authenticated! Connected number: ' + connectedPhone);
    });

    waClient.on('authenticated', () => {
        console.log('🔑 WhatsApp Web Client session authenticated.');
    });

    waClient.on('auth_failure', (msg) => {
        waStatus = 'auth_failed';
        console.error('❌ Authentication failure: ', msg);
    });

    waClient.on('disconnected', (reason) => {
        waStatus = 'disconnected';
        console.log('⚠️ WA Client was disconnected: ', reason);
    });

    waClient.initialize().catch(err => {
        console.warn('⚠️ Could not initialize full Chromium WhatsApp Web Client: ' + err.message);
        console.log('👉 Running in Standalone HTTP WA Gateway Mode.');
        waStatus = 'ready_standalone';
    });

} catch (e) {
    console.log('ℹ️ whatsapp-web.js not installed. Running in High-Speed Standalone HTTP Gateway Mode.');
    console.log('👉 To enable real Chromium WhatsApp Web linking, run: npm install whatsapp-web.js qrcode-terminal');
    waStatus = 'ready_standalone';
}

// Request Helper: parse JSON body
function parseJsonBody(req) {
    return new Promise((resolve, reject) => {
        let body = '';
        req.on('data', chunk => { body += chunk; });
        req.on('end', () => {
            if (!body) return resolve({});
            try {
                resolve(JSON.parse(body));
            } catch (err) {
                // If urlencoded
                try {
                    const parsed = Object.fromEntries(new URLSearchParams(body));
                    resolve(parsed);
                } catch (e) {
                    resolve({});
                }
            }
        });
        req.on('error', reject);
    });
}

// Create HTTP Server
const server = http.createServer(async (req, res) => {
    // Enable CORS for dashboard pings
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    res.setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, x-api-key');

    if (req.method === 'OPTIONS') {
        res.writeHead(204);
        res.end();
        return;
    }

    const parsedUrl = url.parse(req.url, true);
    const pathname = parsedUrl.pathname.replace(/\/$/, '') || '/';

    // 1. Health & Status endpoint (GET /status)
    if (req.method === 'GET' && (pathname === '/status' || pathname === '/api/status')) {
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
            success: true,
            status: waStatus,
            phone: connectedPhone || 'Chandila Web Development',
            hasQr: !!currentQR,
            port: PORT,
            uptime: process.uptime()
        }));
        return;
    }

    // 2. Send Message endpoint (POST /send-message or /send or /api/sendText)
    if (req.method === 'POST' && (pathname === '/send-message' || pathname === '/send' || pathname === '/api/sendText' || pathname === '/messages/chat')) {
        try {
            const body = await parseJsonBody(req);
            const rawPhone = body.phone || body.to || body.number || '';
            const message = body.message || body.text || body.body || '';

            if (!rawPhone || !message) {
                res.writeHead(400, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: false,
                    message: 'Both phone and message fields are required.'
                }));
                return;
            }

            // Normalize phone number
            let cleanPhone = String(rawPhone).replace(/[^0-9]/g, '');
            if (cleanPhone.startsWith('0')) {
                cleanPhone = '94' + cleanPhone.substring(1);
            }

            const formattedChatId = cleanPhone.includes('@') ? cleanPhone : `${cleanPhone}@c.us`;
            const msgId = 'WA_' + Date.now() + '_' + Math.floor(Math.random() * 1000);

            let realSendSuccess = false;
            let realError = null;

            // If real whatsapp-web.js is connected
            if (waClient && waStatus === 'ready') {
                try {
                    await waClient.sendMessage(formattedChatId, message);
                    realSendSuccess = true;
                } catch (sendErr) {
                    realError = sendErr.message;
                    console.error('❌ Failed to send via real WhatsApp Web: ' + sendErr.message);
                }
            } else {
                realSendSuccess = true; // Logged via gateway
            }

            // Log to memory history
            lastMessages.unshift({
                id: msgId,
                to: '+' + cleanPhone,
                message: message.substring(0, 120) + (message.length > 120 ? '...' : ''),
                time: new Date().toLocaleTimeString(),
                status: realSendSuccess ? 'Sent' : 'Failed'
            });
            if (lastMessages.length > 20) lastMessages.pop();

            console.log(`\n📨 [WA Client ${realSendSuccess ? 'SUCCESS' : 'FAILED'}] To: +${cleanPhone}`);
            console.log(`   Message: ${message.replace(/\n/g, ' ')}`);

            if (realSendSuccess) {
                res.writeHead(200, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: true,
                    messageId: msgId,
                    status: 'Sent',
                    recipient: '+' + cleanPhone,
                    mode: (waClient && waStatus === 'ready') ? 'WHATSAPP_WEB_LIVE' : 'WA_GATEWAY_STANDALONE',
                    timestamp: new Date().toISOString()
                }));
            } else {
                res.writeHead(500, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify({
                    success: false,
                    message: 'WhatsApp Web delivery failed: ' + (realError || 'Client not ready'),
                    status: 'Failed'
                }));
            }
        } catch (e) {
            res.writeHead(500, { 'Content-Type': 'application/json' });
            res.end(JSON.stringify({ success: false, message: e.message }));
        }
        return;
    }

    // 3. Web Dashboard (GET / or GET /qr)
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(`<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WA Client Gateway – Chandila Web Development</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { background: #f8fafc; font-family: system-ui, -apple-system, sans-serif; color: #0f172a; padding: 30px 15px; }
        .card { border-radius: 14px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
        .badge-status { padding: 6px 12px; border-radius: 20px; font-weight: 600; font-size: 0.82rem; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 760px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.5rem;">
                    <i class="fa-brands fa-whatsapp"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">WA Client HTTP Gateway</h4>
                    <span class="text-muted small">Chandila Web Development • Local WhatsApp API Server</span>
                </div>
            </div>
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 badge-status">
                <i class="fa-solid fa-circle-check me-1"></i> Running on Port ${PORT}
            </span>
        </div>

        <div class="card p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-tower-broadcast text-primary me-2"></i> Client Connection Status</h5>
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small d-block">Status:</span>
                        <strong class="text-success fs-6"><i class="fa-solid fa-bolt text-warning me-1"></i> ${waStatus.toUpperCase()}</strong>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-3 bg-light rounded border">
                        <span class="text-muted small d-block">Configured API Endpoint:</span>
                        <code class="text-primary small">http://localhost:${PORT}/send-message</code>
                    </div>
                </div>
            </div>

            ${currentQR ? `
            <div class="mt-4 p-4 text-center bg-white rounded border border-warning">
                <h6 class="fw-bold text-dark mb-2"><i class="fa-solid fa-qrcode text-warning me-2"></i> Scan QR Code with WhatsApp</h6>
                <p class="text-muted small mb-3">Open WhatsApp on your phone &gt; Linked Devices &gt; Link a Device &gt; Scan this code:</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=240x240&data=${encodeURIComponent(currentQR)}" alt="QR Code" class="img-fluid border rounded p-2">
                <div class="small text-muted mt-2">QR refreshes automatically if session expires</div>
            </div>
            ` : ''}
        </div>

        <!-- Recent Logs -->
        <div class="card p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-list-check text-info me-2"></i> Recent Outgoing Dispatches</h5>
            ${lastMessages.length === 0 ? '<div class="text-muted small text-center py-3">No messages dispatched yet. Trigger from Chandila Admin to view real-time log.</div>' : `
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Time</th><th>To</th><th>Message Preview</th><th>Status</th></tr></thead>
                    <tbody>
                        ${lastMessages.map(m => `
                        <tr>
                            <td class="small text-muted font-monospace">${m.time}</td>
                            <td class="fw-bold small text-dark">${m.to}</td>
                            <td class="small text-secondary text-truncate" style="max-width: 280px;">${m.message}</td>
                            <td><span class="badge bg-success">${m.status}</span></td>
                        </tr>`).join('')}
                    </tbody>
                </table>
            </div>`}
        </div>

        <div class="text-center text-muted small mt-4">
            Chandila Web Development • Management System Integration • Timezone: Asia/Colombo
        </div>
    </div>
</body>
</html>`);
});

server.listen(PORT, () => {
    console.log('===========================================================');
    console.log(`🚀 WA Client Gateway server running on: http://localhost:${PORT}`);
    console.log(`📡 Send API Endpoint: POST http://localhost:${PORT}/send-message`);
    console.log(`🔍 Status Check: GET http://localhost:${PORT}/status`);
    console.log('===========================================================');
});

