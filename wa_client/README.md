# Chandila Web Development – WA Client Gateway

A lightweight, self-hosted WhatsApp Web HTTP Gateway designed to dispatch automated customer progress notifications directly from your official WhatsApp number (`+94 76 843 3340` / `0768433340`) with **zero per-message fees**.

---

## 🚀 Quick Start (Instant Gateway Mode)

To start the WA Client server immediately using built-in Node.js:

1. Open PowerShell / Command Prompt:
   ```bash
   cd wa_client
   node server.js
   ```
   *(Or simply double-click `run.bat` on Windows)*

2. Open your browser:
   [http://localhost:3000](http://localhost:3000)

3. In the Chandila Admin Portal:
   - Go to **Admin > Settings**
   - Active Messaging Provider: **WA Client**
   - API Endpoint URL: `http://localhost:3000/send-message`
   - Click **Save WhatsApp Configuration**
   - Click **Ping Server** or **Test Send** to verify!

---

## 📱 Live WhatsApp Web Linking (Scan QR Code)

To link your real phone so messages are sent directly from your WhatsApp account:

1. Install the required headless browser libraries:
   ```bash
   cd wa_client
   npm install
   ```

2. Start the server:
   ```bash
   npm start
   ```

3. A QR code will display in the terminal and in your browser at [http://localhost:3000](http://localhost:3000).
4. Open WhatsApp on your phone:
   - Go to **Settings > Linked Devices > Link a Device**
   - Scan the QR code.
5. Your session is saved securely in `./.waclient_auth` so you do **not** have to scan again upon restart.

---

## 📡 API Specification

### Send Message
- **Endpoint:** `POST http://localhost:3000/send-message`
- **Headers:** `Content-Type: application/json`
- **Body:**
  ```json
  {
    "phone": "9476843340",
    "message": "Your project progress is now 75%!"
  }
  ```
- **Response:**
  ```json
  {
    "success": true,
    "messageId": "WA_179432...",
    "status": "Sent",
    "recipient": "+9476843340"
  }
  ```

### Check Status
- **Endpoint:** `GET http://localhost:3000/status`
- **Response:**
  ```json
  {
    "success": true,
    "status": "ready",
    "phone": "9476843340",
    "port": 3000
  }
  ```

