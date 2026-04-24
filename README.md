# Docker-LEMP Extranet Application

This repository provides a high-security, robust LEMP stack (Linux, Nginx, MySQL, PHP) boilerplate, featuring a custom extranet with advanced form handling and data protection.

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

### Accessing the Application
- **Home/Login**: [https://localhost/](https://localhost/)
- **Dashboard**: [https://localhost/dashboard](https://localhost/dashboard)
- **Account Management**: [https://localhost/account_management](https://localhost/account_management)

## Technical Architecture
- **Pattern**: Front Controller with Middleware Pipeline (Onion Architecture)
- **Language**: PHP 8.x
- **Infrastructure**: Dockerized Nginx (Hardened) & MySQL
- **UI Logic**: Vanilla Javascript & Semantic HTML
- **Principles**: Adheres to SOLID design principles and standard design patterns.

