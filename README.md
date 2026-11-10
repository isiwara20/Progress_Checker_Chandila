# Chandila Web Development – Project & Customer Management System

A complete, enterprise-grade, responsive web application for **Chandila Web Development**, a premier web development company in Sri Lanka. The system oversees and automates the entire customer project lifecycle—from initial project inquiry submission, automated WhatsApp notifications, and 3-stage milestone payments to agile developer assignment, progress tracking, website hosting, and delivery.

---

## 1. Technology Stack

- **Frontend:** HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3.3, Font Awesome 6.5.1
- **Backend:** Core PHP (PHP 8.0+ / 8.2), structured with reusable functions and modular architecture
- **Database:** MySQL / MariaDB (Normalized schema with foreign keys, indexes, and triggers)
- **Database Management:** phpMyAdmin compatible SQL dump
- **Data Visualizations:** Chart.js 4.4 for interactive executive analytics
- **Client Dynamic Requests:** Fetch API & Asynchronous AJAX handlers
- **Messaging Integration:** Configurable WhatsApp Cloud API Engine (Simulator, UltraMsg, and Generic Webhook)
- **Timezone:** Asia/Colombo (`+05:30`, Sri Lanka Time)
- **Design System:** Dark Navy (`#0B132B`, `#1C2541`), Electric Blue (`#00F0FF`, `#3A86FF`), Crisp White

---

## 2. Default System Credentials

| Role | Username | Email | Password | Access URL |
| :--- | :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` | `admin@chandilaweb.com` | `Admin@12345` | `/login.php` or `/admin/` |
| **Developer 01 (Senior Full Stack)** | `dev1` | `dev1@chandilaweb.com` | `Dev1@12345` | `/login.php` or `/developer/` |
| **Developer 02 (Frontend & UI/UX)** | `dev2` | `dev2@chandilaweb.com` | `Dev2@12345` | `/login.php` or `/developer/` |

> *Note: Passwords are encrypted using PHP `password_hash()` with the Bcrypt algorithm.*
> *Quick 1-click credential fill pills are available directly on the login page for instant testing.*

---

## 3. Quick Installation & Setup Guide (XAMPP / phpMyAdmin)

### Step 1: Copy to XAMPP htdocs
Ensure the project folder is placed inside your XAMPP web root:
```
d:\Chandila\XAMP\htdocs\Progress_Checker_Chandila\
```

### Step 2: Start Apache and MySQL
1. Open the **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import Database via phpMyAdmin
1. Open your browser and navigate to: `http://localhost/phpmyadmin`
2. Click on the **Import** tab at the top.
3. Click **Choose File** and select:
   ```
   d:\Chandila\XAMP\htdocs\Progress_Checker_Chandila\database\database.sql
   ```
4. Click **Import** at the bottom.
5. The database `chandila_pms` along with all 11 tables and realistic seed data (Admin, Developers, Customers, Projects, Milestones, Payments, Activities, WhatsApp logs) will be created automatically.

### Step 4: Open the Application
- **Public Website & Home:** `http://localhost/Progress_Checker_Chandila/index.php`
- **Request a Project:** `http://localhost/Progress_Checker_Chandila/request_project.php`
- **Track My Project:** `http://localhost/Progress_Checker_Chandila/track_project.php`
- **Staff Login (Admin & Dev):** `http://localhost/Progress_Checker_Chandila/login.php`

---

## 4. Key Architectural Features & Modules

### A. Customer Experience & Public Portal
1. **Home (`index.php`):**
   - High-impact dark navy & electric blue hero section with live stats.
   - Built-in "Quick Project Tracker" widget right on the hero banner.
   - 4-Stage Project Lifecycle visual breakdown.
   - Core services showcase and verified customer testimonials.
2. **About Us (`about.php`):**
   - Company mission, vision, Sri Lankan engineering team background, tech stack expertise.
3. **Our Services (`services.php`):**
   - Detailed breakdown across Business Websites, E-Commerce Stores, Custom Web Applications, Student Management Systems (SMS), Online Booking Engines, and Cloud Hosting.
4. **Portfolio (`portfolio.php`):**
   - Showcase of delivered web projects with tech stacks and live specifications.
5. **Contact Us (`contact.php`):**
   - Contact form, Colombo office details, direct WhatsApp links, and FAQ accordion.
6. **Request a Project Form (`request_project.php`):**
   - Complete 13-field technical requirement form.
   - Validates inputs, records customer details, saves project specifications, and accepts secure file uploads up to 10MB.
   - Atomically generates unique Job ID (e.g., `CHW-2026-0001`).
   - Dispatches automated **WhatsApp Event 1** confirmation containing the Job ID.
7. **Customer Project Tracking (`track_project.php`):**
   - Public tracking requiring **Job ID + Registered Phone / WhatsApp** for dual-factor verification.
   - Displays 12-Stage Visual Stepper Timeline with active/completed stages.
   - Progress bar (0% – 100%).
   - 3-Stage Payment Breakdown (Advance, 50% Milestone, Final Payment, Balance Due).
   - Expected completion date, live hosting URL (once deployed), and public developer updates feed.
   - **Privacy Protection:** Internal developer notes, administrative margins, and other customers' data are strictly withheld.

---

### B. 12-Stage Project Lifecycle Workflow
The project progresses transparently across 12 milestone stages:
1. `Request Submitted` – Received online; Job ID issued.
2. `Request Reviewed` – Technical scope analyzed; pricing calculated.
3. `Project Confirmed` – Terms approved for development queue.
4. `Advance Payment Pending` – Awaiting advance deposit verification.
5. `Advance Payment Confirmed` – Advance verified; dev environment initialized.
6. `Development Started` – Architecture & database modeling started.
7. `Development in Progress` – Core sprints & frontend/backend integration.
8. `Client Review / Revisions` – Staging review with customer.
9. `Final Payment Pending` – Development complete; awaiting final balance.
10. `Hosting & Deployment` – Live DNS, SSL, database & server configuration.
11. `Project Completed` – QA testing and performance checks approved.
12. `Project Delivered` – Final delivery with ownership credentials.

---

### C. 3-Stage Payment Management Workflow
- **Stage 1 – Advance Payment:** Agreed deposit paid before development officially kicks off. The project status cannot be confirmed without verified advance payment.
- **Stage 2 – Second Payment (50% Milestone):** Milestone payment due before scheduled completion date. Includes 1-click WhatsApp milestone reminders.
- **Stage 3 – Final Payment and Hosting:** Settle outstanding balance prior to live deployment. Development completion and website delivery are treated as separate verified stages.
- **Payment Verification:** Bank slips and transaction references must be manually approved or rejected by the admin. Payments are never marked received automatically.

---

### D. Admin Control Center (`/admin/`)
1. **Executive Dashboard (`index.php`):**
   - 9 Real-time business KPI cards (Total Projects, New Requests, Awaiting Advance, Active, Completed, Pending Payments, Total Revenue, Total Outstanding Balance, Active Developers).
   - 4 Interactive Chart.js widgets (Projects by Status Doughnut, Monthly Revenue Bar, Developer Workload Horizontal Bar, Delivery Ratio).
   - Recent requests table and system audit activity feed.
2. **New Project Requests (`requests.php`):**
   - Review incoming inquiries, set pricing and milestone schedules, assign developers, or reject requests with audit logging.
3. **All Projects (`projects.php`):**
   - Master filterable directory by status, type, developer, or text query.
4. **Single Project Command Center (`project_view.php`):**
   - Full 360-degree control of scope, downloads, 12-stage status updater, payment history, hosting deployment, and reminders.
5. **Manage Customers (`customers.php`):**
   - Customer directory with project count, contact numbers, and total lifetime spend.
6. **Manage Developers (`developers.php`):**
   - Add new developer accounts with hashed passwords and track workload.
7. **Assign Projects (`assign.php`):**
   - Visual capacity board to balance projects across the team.
8. **Payment Management (`payments.php`):**
   - 3-stage payments center with 1-click verification/rejection and receipt viewing.
9. **Project Progress Radar (`progress.php`):**
   - High-level bird's-eye view of all ongoing sprints with progress bars and deadline countdowns.
10. **WhatsApp Messaging Center (`whatsapp.php`):**
    - Audit log of all automated event notifications.
    - **1-Click Manual Resend** for failed dispatches via AJAX.
    - Ad-hoc custom WhatsApp message sender.
11. **Reports & Financials (`reports.php`):**
    - Revenue by service line, developer delivery velocity, cleared invoices, and print-ready summary.
12. **Settings (`settings.php`):**
    - Configurable WhatsApp API (Simulator / UltraMsg / Generic Webhook), company contact details, bank deposit information, and admin password change.

---

### E. Developer Workspace (`/developer/`)
- **Strict Role-Based Access Control:** Developers can **ONLY** view and manage projects assigned to their accounts.
- **Sprint Management (`project_view.php`):**
  - View technical scope, customer contact info, deadline, and reference files.
  - Interactive milestone task checklist (AJAX enabled).
  - Update progress slider (0% – 100%).
  - Add public progress descriptions and private internal developer notes.
  - Option to trigger WhatsApp progress updates to the customer.
- **Enforced Security Guardrails:** Developers **cannot** change project pricing, approve payments, assign projects, access financials, or delete data.

---

### F. WhatsApp Notification Integration
The system triggers 9 automated event messages:
1. `Project Request Submitted` – Confirmation with Job ID and tracking link.
2. `Project Approved` – Review completed notification.
3. `Advance Payment Confirmed` – Official project confirmation.
4. `Project Assigned` – Development team assigned.
5. `Project Progress Updated` – Milestone & percentage updates.
6. `Second Payment Reminder` – 50% milestone reminder.
7. `Development Completed` – Development work finished.
8. `Final Payment Reminder` – Balance settlement before hosting.
9. `Hosting Completed` – Live deployment announcement with production URL.

---

## 5. Security & Production Standards

- **SQL Injection Prevention:** 100% prepared statements using PHP Data Objects (PDO).
- **CSRF Protection:** Cryptographically secure CSRF tokens on all POST and AJAX actions.
- **XSS Prevention:** Output escaping via `htmlspecialchars()`.
- **Session Security:** `session_regenerate_id(true)` upon authentication, HTTP-only session cookies.
- **Brute Force Protection:** Session-based rate limiting (locks after 5 failed attempts for 15 minutes).
- **File Upload Hardening:** Extension whitelist (`pdf, doc, docx, png, jpg, jpeg, zip`), 10MB file size ceiling, randomized storage filenames, and `.htaccess` script execution prevention in `/uploads/`.
- **Client Privacy:** Customer portal requires dual-factor Job ID + Registered Phone verification.

---

## 6. Directory Structure

```
Progress_Checker_Chandila/
├── config/
│   ├── config.php              # Global settings, constants, timezone
│   ├── database.php            # PDO singleton with graceful auto-installer
│   └── whatsapp_config.php     # WhatsApp API service & templates
├── includes/
│   ├── auth.php                # Authentication, CSRF, rate-limiting & RBAC
│   ├── functions.php           # Unique Job ID generator, audit logger, file handler
│   ├── header.php              # Public navigation header
│   ├── footer.php              # Public footer & floating WhatsApp button
│   ├── admin_header.php        # Admin navbar & stats
│   ├── admin_sidebar.php       # Admin 12-item sidebar
│   ├── admin_footer.php        # Admin scripts & Chart.js init
│   ├── dev_header.php          # Developer navbar
│   ├── dev_sidebar.php         # Developer sidebar
│   └── dev_footer.php          # Developer footer
├── assets/
│   ├── css/
│   │   ├── style.css           # Public styling (navy & electric blue)
│   │   └── admin.css           # Admin & developer dashboard styling
│   └── js/
│       ├── main.js             # Public interactions & form validation
│       └── admin.js            # Chart.js charts & AJAX status/resend
├── uploads/
│   └── .htaccess               # Prevents script execution
├── database/
│   └── database.sql            # Full MySQL schema & seed data
├── api/
│   ├── whatsapp.php            # WhatsApp AJAX resend & test dispatch
│   ├── project_actions.php     # Milestone checklist AJAX toggle
│   └── track_ajax.php          # Public project lookup AJAX endpoint
├── admin/                      # 12-module Admin Control Center
├── developer/                  # Developer Workspace
├── index.php                   # Public Homepage
├── about.php                   # About Us
├── services.php                # Our Services
├── portfolio.php               # Portfolio Showcase
├── contact.php                 # Contact Us & FAQ
├── request_project.php         # 13-Field Project Request Form
├── track_project.php           # Public Project Tracker
├── customer_login.php          # Customer Quick Login
├── login.php                   # Unified Staff Login
├── logout.php                  # Secure Logout
└── README.md                   # System Documentation
```

---

*Engineered with precision for **Chandila Web Development**.*

