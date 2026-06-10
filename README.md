# Docker-LEMP Extranet Application

This repository provides a high-security, event-sourced LEMP stack (Linux, Nginx, MySQL, PHP) SaaS platform, featuring automated multi-tenant provisioning, a custom CMS block editor, and advanced data protection.

## Key Features

### 🛡️ Security & Active Defense
- **Front Controller WAF**: All traffic is routed through a centralized `Router` with a global Web Application Firewall (WAF) middleware.
- **Resource Protection**: The WAF blocks banned IPs and malicious payloads *before* session initialization, protecting server memory and database resources.
- **Auto-Banning**: Integrated `RateLimiter` automatically identifies and temporarily bans IPs exhibiting abusive behavior (brute-force, cookie rotation, malicious payloads).
- **Nginx Hardening**: Direct access to internal PHP scripts is blocked at the web server level. Only `index.php` and `exception.php` are permitted entry points.
- **Layered Sanitization**: Uses Strategy and Decorator patterns to apply multiple sanitization layers (XSS, Whitespace, HTML Escape, Tag Stripping).
- **CSRF Protection**: Comprehensive Cross-Site Request Forgery protection across all state-changing routes.

### ✅ Schema-Driven Input Validation
- **Centralized Definitions**: Forms are defined via PHP schemas, defining labels, types, and constraints.
- **Validator Engine**: A custom backend validation engine enforces site-wide rules and provides consistent error reporting.
- **Dynamic UI**: `xmlForm` automatically generates HTML5-compliant inputs and error messages from the centralized schema.

### 🎨 Advanced UI & UX
- **Clean URLs**: Modern, extension-less routing (e.g., `/dashboard` instead of `/dashboard.php`).
- **Real-Time Validation**: Custom `validator.js` provides instant feedback as you type.
- **Modern Navigation**: Implementation of the PRG (Post-Redirect-Get) pattern for seamless form submissions.

### 🏢 SaaS & Multi-Tenancy
- **Automated Provisioning**: Programmatically spin up isolated Docker containers, databases, and Nginx configurations for independent tenants.
- **Strict Isolation**: Each "Child Service" (tenant) runs in a completely segregated environment, preventing data leakage and "noisy neighbor" impacts.
- **Host-to-Tenant Mapping**: Securely delegates administrative access by injecting host users directly into isolated tenant instances without requiring plain-text credentials.

### 📜 Immutable Event Sourcing
- **Auditability**: All state-mutating actions (Creates, Updates, Deletes) are recorded chronologically in an immutable `event_store` rather than directly overwriting database rows.
- **Asynchronous Processing**: Background workers handle the heavy lifting of materializing events into standard relational tables using `FOR UPDATE SKIP LOCKED` concurrency controls.
- **Undo/Redo (Memento)**: Point-and-click reversal of destructive actions through automatically generated compensating events.
- **Platform Recovery**: Built-in point-in-time recovery, allowing administrators to seamlessly rebuild the database state up to a specific timestamp.

### 🧱 Page Builder & CMS
- **Visual Block Editor**: A vanilla Javascript interface supporting drag-and-drop mechanics, nested containers, and in-place content editing.
- **Deep Nesting**: The robust relational data model supports infinitely nested layouts, serializing complex element hierarchies securely through the Event Store.

### 📊 Data Visualization
- **Dynamic Graphs**: Built-in SVG-based data graphing component (`dataGraph.php`) for visualizing application metrics.

## Getting Started

### Prerequisites
- Docker & Docker Compose

### Startup

1. Clone the repository and navigate to the directory.
2. Choose your database backend and run the environment:

**For MySQL (Default):**
```bash
docker compose up --build
```

**For MariaDB:**
```bash
docker compose -f compose.mariadb.yaml up --build
```

**For PostgreSQL:**
```bash
docker compose -f compose.postgreSQL.yaml up --build
```

**Using .env.dev::**
```bash
docker compose --env-file .env.dev up --build
```

**On Live::**
```bash
docker compose --env-file .env.live up --build
```

### Accessing the Application
- **Home/Login**: [https://localhost:8443/](https://localhost:8443/)
- **Dashboard**: [https://localhost:8443/dashboard](https://localhost:8443/dashboard)
- **Account Management**: [https://localhost:8443/account_management](https://localhost:8443/account_management)

## Technical Architecture
- **Pattern**: Front Controller with Middleware Pipeline (Onion Architecture) & Event Sourcing
- **Language**: PHP 8.x
- **Infrastructure**: Dockerized Nginx (Hardened) & MySQL
- **UI Logic**: Vanilla Javascript & Semantic HTML
- **Principles**: Adheres to SOLID design principles and standard design patterns.

