# Phase 4: Front Controller & Middleware Security Architecture

## Goal
Transition the legacy procedural PHP application to a modern **Front Controller** architecture. Centralize all request routing, implement a global **Middleware Pipeline** for security enforcement (WAF, CSRF, Extranet), and modernize the URL structure to support clean, extension-less paths.

## Architecture

### 1. Unified Entry Point (`index.php`)
The application no longer relies on physical `.php` files for every page. Instead, Nginx is configured to route all non-file requests to a single `index.php`.

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 2. Database-Driven Router
The `Router` class now dynamically initializes its registry from `tblNavBar`. It maps paths to specialized `ControllerInterface` implementations and determines if a route requires authentication via the `nbProtected` flag.

**Key Refactor**: Consolidated `$controllerRegistry` and `$protectedPaths` into a single, unified `$registry` source of truth.

### 3. Middleware Pipeline (Onion Architecture)
Security is enforced globally via a stack of middlewares that wrap the final controller execution:
1. **WafMiddleware**: Sanitizes input and blocks malicious patterns.
2. **ExtranetMiddleware**: Enforces authentication based on the Router's protection registry.
3. **CsrfMiddleware**: Validates tokens for state-changing requests.

```php
$router->use(new WafMiddleware());
$router->use(new ExtranetMiddleware($router, $sessionController));
$router->use(new CsrfMiddleware($sessionController));
```

## 4. URL Modernization
Legacy `.php` extensions have been removed from the application's routing registry and internal navigation (e.g., `/account_management.php` -> `/account_management`). This simplifies Nginx configuration and aligns the codebase with modern web standards.

## 5. Controller Migration
The following legacy modules were successfully refactored into the `ControllerInterface` pattern:
*   **Pages**: `Dashboard`, `AccountManagement`, `ErrorLog`, `NavbarManagement`, `FormManagement`, `BannedIpManagement`.
*   **Actions**: `Login`, `Logout`, `Register`, `UpdateAccount`, `EditNavbar`, `EditForm`, `EditColumn`, `UnbanIpAction`.

## Phase 4.1: Post-Implementation Hardening
Following the successful deployment of the Front Controller, the architecture was further hardened to improve resource resilience and security:

*   **Resource-Optimized Pipeline**: The middleware stack was reordered to run `WafMiddleware` (IP/Ban check) *before* `SessionMiddleware`. This ensures that banned or malicious clients are rejected before the server spends resources initializing a session.
*   **Administrative IP Management**: A new management interface allows administrators to view and remove IP bans. The "Unban" action automatically clears the client's `httpAction` history, resetting rate limits and allowing immediate re-access.
*   **Entry-Point Enforcement (Nginx Lockdown)**: Nginx was reconfigured to block all direct access to `.php` files except for `index.php` and `exception.php`. This forces 100% of the application's PHP execution through the WAF and Router.

*   **Decoupled Logging**: `WafMiddleware` was updated to perform independent logging for blocked attacks, ensuring audit trails are maintained even when the request is terminated early.

## Status: COMPLETED & HARDENED
Implementation is fully verified. The architecture now provides high performance for legitimate users while being extremely resilient against automated probes and resource-exhaustion attacks. Phase 4 is fully stable.
