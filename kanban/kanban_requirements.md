# KANBAN TASK MANAGEMENT SYSTEM — SECURITY-HARDENED IMPLEMENTATION PLAN
**Version:** 2.2
**Last Updated:** 2025-06-28

## 1. High-Level Vision & Core Principles

*   **Audience:** Enterprise-grade data center, technical, and creative workflow teams requiring a robust, self-hosted solution with a premium on security.
*   **Core Vision:** To create a visually stunning, highly performant, and deeply customizable Kanban system that feels both powerful for managers and intuitive for end-users. It should be a central hub for productivity and collaboration, built on a foundation of zero-trust security.
*   **Key Principles:**
    *   **Secure by Design:** Security is the primary feature. The system must be architected to proactively defend against modern threats and protect user data at all layers. Adherence to the **OWASP Top 10** is mandatory.
    *   **Modular & Extensible:** A clean plugin architecture is paramount.
    *   **Real-Time First:** All user interactions must be reflected instantly for all connected clients via a secure WebSocket connection.
    *   **Mobile & PWA Native:** The experience on mobile must be seamless, fast, and fully featured, including offline capabilities.
    *   **Aesthetically Polished:** Fluid animations, customizable themes, and a clean, modern UI are critical for user adoption.
    *   **Accessible by Design:** Adherence to WCAG 2.1 AA standards is required.

## 2. Technology Stack & Architecture

*   **Web Server:** Apache 2.4+ (`mod_rewrite`, `mod_ssl` support required)
*   **Database:** MariaDB 10.6+ or MySQL 8.0+
*   **Backend:** PHP 8.2+ (Modular REST API)
*   **Real-Time Engine:** A dedicated, secure WebSocket server (`wss://`) requiring token authentication for all connections.
*   **Frontend:**
    *   **Framework:** Vue 3 or React 18 for a dynamic, component-based Single Page Application (SPA).
    *   **State Management:** Pinia (for Vue) or Redux Toolkit (for React).
    *   **Styling:** Tailwind CSS for a utility-first, highly customizable design system.
    *   **Data Visualization:** Chart.js or a more modern alternative like Apache ECharts.
*   **Progressive Web App (PWA):** Manifest and a robust service worker for caching, offline access, and background sync.
*   **Setup/Deployment:** Automated Bash script (`setup.sh`) and consideration for future Docker containerization with hardened base images.

## 3. Directory & File Structure
```
/kanban/
├── api/
│   ├── v1/
│   │   ├── auth/
│   │   ├── users/
│   │   ├── boards/
│   │   ├── tasks/
│   │   ├── comments/
│   │   ├── notifications/
│   │   ├── automations/
│   │   ├── reports/
│   │   └── _helpers/
├── assets/
├── config/
│   ├── database.php
│   └── app.php
├── db/
│   ├── schema.sql
│   ├── seed.sql
│   └── migrations/
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── store/
│   │   ├── assets/
│   │   └── main.js
├── public/
│   ├── index.html
│   ├── manifest.json
│   ├── service-worker.js
│   └── .htaccess
├── scripts/
│   ├── setup.sh
│   ├── backup.sh
│   └── cron/
├── storage/
│   ├── uploads/
│   ├── logs/
│   └── cache/
├── .env.example
├── README.md
└── LICENSE
```

## 4. Detailed Feature Requirements
(No changes from v2.0 - features remain the same)

---

## 5. Security & Compliance Requirements

This section outlines the mandatory security controls that must be implemented throughout the system.

### 5.1. Authentication & Session Management
*   **Password Security:** Use `bcrypt` for all stored user passwords.
*   **Multi-Factor Authentication:** Support for Time-based One-Time Passwords (TOTP) as a 2FA method is required.
*   **SSO Integration:** Support for SAML and OAuth2 for Google/Microsoft/GitHub for enterprise adoption.
*   **Advanced Token Strategy:**
    *   **Short-Lived Access Tokens:** JWT access tokens must have a short expiry (e.g., 15 minutes).
    *   **Secure Refresh Tokens:** Implement a refresh token system using `HttpOnly`, `Secure` cookies to maintain user sessions.
    *   **Session Revocation:** Admins must have a UI to immediately revoke any user's sessions and access tokens.

### 5.2. API & Application Security
*   **Rate Limiting:** Implement strict rate limiting on all API endpoints, especially authentication, to prevent brute-force and denial-of-service attacks.
*   **Input Validation & Sanitization:** All user-supplied data, without exception, must be rigorously validated and sanitized on the server-side to prevent SQL Injection (SQLi), Cross-Site Scripting (XSS), and other injection flaws.
*   **Mandatory HTTP Security Headers:** All server responses must include:
    *   `Content-Security-Policy (CSP)`: A strict policy to control which resources can be loaded, preventing XSS.
    *   `HTTP Strict-Transport-Security (HSTS)`: To enforce HTTPS connections.
    *   `X-Frame-Options: DENY`: To prevent clickjacking.
    *   `X-Content-Type-Options: nosniff`: To prevent MIME-type sniffing.
*   **Role-Based Access Control (RBAC):** Granular permissions for Admin, Manager, and User roles. API endpoints must enforce these permissions on every request.

### 5.3. Data & File Security
*   **Encryption at Rest:** Sensitive data in the database (e.g., API keys for integrations, user PII) and all user-uploaded files in the `/storage/uploads/` directory must be encrypted.
*   **Secure File Uploads:**
    *   Uploads must be stored outside the web root (`public/`).
    *   Implement strict file type and size validation on the server side.
    *   Scan all uploads for malware before saving.
*   **Encrypted Backups:** The `scripts/backup.sh` script must produce GPG-encrypted backup archives.

### 5.4. Auditing & Dependency Management
*   **Immutable Audit Trails:** A comprehensive, unchangeable log of all significant actions (logins, task creations, permission changes, etc.) must be maintained.
*   **Automated Dependency Scanning:** The project must be configured to use **GitHub Dependabot** or an equivalent tool to continuously scan for and report vulnerabilities in third-party code libraries.
*   **Secret Management:** While `.env` is suitable for development, the system must be architected to support integration with a dedicated secrets vault (e.g., HashiCorp Vault) for production deployments.

---

## 6. Setup, Deployment & Code Quality Requirements

This section incorporates lessons learned from the initial debugging and hardening process to prevent common environmental and build-related issues.

*   **One-Script Setup:** The `scripts/setup.sh` script must fully automate the installation of all dependencies, database setup, configuration, and permissions on a fresh Ubuntu 24.04 server.
*   **Default Accounts:** The `db/seed.sql` file must create the following default accounts:
    *   `admin@kanban.local / adminpass`
    *   `manager@kanban.local / managerpass`
    *   `user@kanban.local / userpass`
*   **Automated HTTPS Enforcement:** The setup script must generate a self-signed SSL certificate and configure the Apache virtual host to enforce HTTPS by redirecting all HTTP traffic. The `HSTS` header must be enabled.
*   **Correct File Permissions:** The setup script must recursively set file and directory ownership to the web server user (`www-data` on Debian/Ubuntu) for the entire project root. All subsequent dependency and build commands (Composer, NPM) must be executed as the `www-data` user to prevent runtime permission errors.
*   **Robust Backend Pathing:** All backend file includes (e.g., for `config/`, `vendor/autoload.php`) must use absolute paths derived from the file's location (e.g., using `__DIR__`) to ensure reliability regardless of execution context.
*   **Integrated Build Process:** The frontend build tool (Vite, Webpack, etc.) must be configured to place all final, compiled assets (JS, CSS, images, etc.) and the main `index.html` file directly into the `/public` directory. The `index.html` source file must not contain hardcoded links to assets; these must be injected dynamically by the build tool.
*   **Correct Apache Configuration:** The setup script must generate an Apache virtual host that correctly routes requests:
    *   The `DocumentRoot` must point to the `/public` directory.
    *   An `Alias` must be created to map the `/api` URL path to the `/api` directory.
    *   `FallbackResource /index.html` must be used within the `DocumentRoot` directory configuration to enable client-side routing for the Single Page Application.
*   **Final Deliverable:** A single ZIP archive containing the complete, organized codebase as per the directory structure, ready for development, auditing, and one-script deployment.
