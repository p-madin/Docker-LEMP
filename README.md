# Docker-LEMP Extranet Application

This repository provides a high-security, robust LEMP stack (Linux, Nginx, MySQL, PHP) boilerplate, featuring a custom extranet with advanced form handling and data protection.

## Key Features

### 🛡️ Security & Sanitization Framework
- **Layered Defense**: Uses Strategy and Decorator patterns to apply multiple sanitization layers (XSS, Whitespace, HTML Escape, Tag Stripping).
- **Auto-Sanitized UI**: The `xmlDom` rendering engine automatically sanitizes all content (defense-in-depth).
- **CSRF Protection**: Comprehensive Cross-Site Request Forgery protection across all secure entry points.

### ✅ Schema-Driven Input Validation
- **Centralized Definitions**: Forms are defined via PHP arrays in `forms.php`, defining labels, types, and constraints (required, email, length, etc.).
- **Validator Engine**: A custom backend validation engine enforces site-wide rules and provides consistent error reporting.
- **Dynamic UI**: `xmlForm` automatically generates HTML5-compliant inputs and error messages from the centralized schema.

### 🎨 Advanced UI & UX
- **Real-Time Validation**: Custom `validator.js` provides instant feedback as you type, using green/red visual highlights for satisfied/error states.
- **Modern Navigation**: Implementation of the PRG (Post-Redirect-Get) pattern with federated JSON/HTML support for a seamless "Ajax-like" feel while maintaining full browser compatibility.
- **Form Safety**: Architectural guards prevent invalid HTML nesting (e.g., in the `Hyperlink` widget).
- **Data Persistence**: "Flash" session data preserves form input after validation failures while protecting sensitive fields like passwords.

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
- **Home/Login**: [https://localhost/index.php](https://localhost/index.php)
- **Dashboard**: [https://localhost/dashboard.php](https://localhost/dashboard.php)
- **Account Management**: [https://localhost/account_management.php](https://localhost/account_management.php)

## Technical Architecture
- **Language**: PHP 8.x
- **Infrastructure**: Dockerized Nginx & MySQL
- **UI Logic**: Vanilla Javascript & Semantic HTML
- **Principles**: Adheres to SOLID design principles and standard design patterns.
