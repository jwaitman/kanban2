# KANBAN TASK MANAGEMENT SYSTEM — SECURITY-HARDENED IMPLEMENTATION PLAN
**Version:** 2.3
**Last Updated:** 2025-06-28

## 1. High-Level Vision & Core Principles

*   **Audience:** Enterprise-grade data center, technical, and creative workflow teams requiring a robust, self-hosted solution with a premium on security.
*   **Core Vision:** To create a visually stunning, highly performant, and deeply customizable Kanban system that feels both powerful for managers and intuitive for end-users. It should be a central hub for productivity and collaboration, built on a foundation of zero-trust security.
*   **Key Principles:**
    *   **Secure by Design:** Security is the primary feature. The system is architected to proactively defend against modern threats and protect user data at all layers. Adherence to the **OWASP Top 10** is mandatory.
    *   **Modular & Extensible:** A clean, RESTful API architecture allows for easy integration and extension.
    *   **Performant & Responsive:** All user interactions are designed to be fast and responsive, providing a seamless user experience.
    *   **Mobile-First & PWA Ready:** The experience on mobile is seamless, fast, and fully featured, with a functional PWA structure in place.
    *   **Aesthetically Polished:** A clean, modern UI built with Tailwind CSS is critical for user adoption.
    *   **Accessible by Design:** Adherence to WCAG 2.1 AA standards is a core goal.

## 2. Technology Stack & Architecture

*   **Web Server:** Apache 2.4+ or Nginx 1.20+ (`mod_rewrite` or equivalent support required).
*   **Database:** MariaDB 10.6+ or MySQL 8.0+.
*   **Backend:** PHP 8.2+ (Modular REST API).
    *   **Dependency Management:** Composer is required.
    *   **Authentication:** `firebase/php-jwt` for JWT handling.
*   **Frontend:**
    *   **Framework:** Vue 3 for a dynamic, component-based Single Page Application (SPA).
    *   **State Management:** Pinia for centralized, type-safe state management.
    *   **HTTP Client:** Axios, integrated with the Pinia store for authenticated API requests.
    *   **Styling:** Tailwind CSS for a utility-first, highly customizable design system.
    *   **Build Tool:** Vite.
*   **Progressive Web App (PWA):** Manifest and a service worker are in place for caching and offline capabilities.
*   **Setup/Deployment:** Automated Bash script (`setup.sh`) for initial setup.

## 3. Directory & File Structure (Refined)
```
/kanban/
├── api/
│   └── v1/
│       ├── auth/index.php
│       ├── users/index.php
│       ├── boards/index.php
│       ├── columns/index.php
│       ├── tasks/index.php
│       ├── comments/index.php
│       └── _helpers/
│           ├── auth.php
│           ├── database.php
│           ├── response.php
│           └── audit.php
├── config/
│   ├── database.php
│   └── app.php
├── db/
│   ├── schema.sql
│   └── seed.sql
├── frontend/
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── store/index.js  (Pinia Store)
│   │   ├── assets/
│   │   ├── router.js
│   │   └── main.js
│   ├── public/
│   ├── index.html
│   ├── package.json
│   └── vite.config.js
├── public/ (Web Root)
│   ├── index.html
│   └── assets/ (Compiled frontend assets)
├── scripts/
│   └── setup.sh
├── storage/
│   ├── logs/
│   └── cache/
├── vendor/ (Composer dependencies)
├── composer.json
├── kanban_requirements.md
└── README.md
```

## 4. Security & Compliance Requirements (Implemented & Enforced)

This section outlines the mandatory security controls that have been implemented.

### 4.1. Authentication & Session Management
*   **Password Security:** Use **Argon2id** (via `password_hash` with `PASSWORD_DEFAULT` in PHP 8.2+) for all stored user passwords, exceeding the original `bcrypt` requirement.
*   **Advanced Token Strategy:**
    *   **Short-Lived Access Tokens:** JWT access tokens have a configurable, short expiry (default: 1 hour).
    *   **Secure Refresh Tokens:** Implemented a refresh token system using `HttpOnly`, `Secure` (in production), and `SameSite=Strict` cookies to securely maintain user sessions and retrieve new access tokens.
*   **Secure Logout:** The logout endpoint invalidates the client-side session, and the secure cookie mechanism prevents token reuse.

### 4.2. API & Application Security
*   **SQL Injection Prevention:** All database queries are executed using **prepared statements** (`bind_param`), eliminating the risk of SQLi.
*   **Role-Based Access Control (RBAC):** Granular permissions for `admin`, `manager`, and `user` roles are enforced on every relevant API endpoint. Users cannot access or modify resources they do not have explicit permission for.
*   **Centralized Authentication Check:** A `require_auth()` helper function is used across all protected API endpoints to ensure no endpoint is left unsecured.
*   **Robust Pathing & Includes:** All backend file includes use absolute paths derived from `__DIR__` to prevent path traversal issues and ensure reliability regardless of execution context.
*   **Error Handling:** API endpoints provide clear, but not overly descriptive, error messages to prevent leaking sensitive system information.

### 4.3. Data & File Security
*   **Database Schema:** The schema is normalized and includes foreign key constraints with `ON DELETE CASCADE` to ensure data integrity.
*   **Sensitive Data Handling:** The `last_login_at` timestamp is recorded for user activity monitoring.

### 4.4. Auditing & Dependency Management
*   **Immutable Audit Trails:** A comprehensive `audit_log` table records all significant security and data modification events, including:
    *   Successful logins (`login_success`).
    *   Failed login attempts, including the attempted username (`login_failure`).
    *   (Extendable to) resource creation, updates, and deletions.
*   **Dependency Management:** Project dependencies are explicitly managed via `composer.json` (PHP) and `package.json` (JS), enabling vulnerability scanning with tools like Dependabot.

## 5. Setup, Deployment & Code Quality Requirements (Updated)

This section incorporates lessons learned from the initial debugging and hardening process.

*   **Mandatory Prerequisite: Composer:** The `README.md` and setup instructions must clearly state that **Composer is a required dependency** for the backend to function. The `setup.sh` script should include a check for its existence.
*   **One-Command Dependency Install:** Running `composer install` in the project root is the single, authoritative step to install all PHP dependencies and generate the critical `vendor/autoload.php` file. This step is non-negotiable and resolves class/dependency-not-found errors.
*   **Default Accounts:** The `db/seed.sql` file must create the following default accounts with secure, hashed passwords:
    *   `admin@kanban.local / adminpass`
    *   `manager@kanban.local / managerpass`
    *   `user@kanban.local / userpass`
*   **Integrated Build Process:** The Vue/Vite frontend is configured to be a pure Single Page Application. The `npm run build` command compiles all assets and places them into a `/dist` directory, which should then be moved to the server's web root (e.g., `/var/www/html` or the project's `/public` folder).
*   **Web Server Configuration:** The web server must be configured to:
    1.  Set the `DocumentRoot` to the `/public` directory.
    2.  Use `mod_rewrite` (or equivalent) to direct all non-file, non-directory requests to `/index.html` to enable Vue Router's client-side routing.
    3.  Proxy or rewrite requests for `/api/v1/` to the `api/v1/` directory to ensure the backend API is reachable.
*   **Final Deliverable:** A single ZIP archive containing the complete, organized codebase as per the directory structure, ready for development, auditing, and deployment, with clear instructions in the `README.md`.
