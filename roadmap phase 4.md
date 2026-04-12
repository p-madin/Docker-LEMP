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
*   **Pages**: `Dashboard`, `AccountManagement`, `ErrorLog`, `NavbarManagement`, `FormManagement`.
*   **Actions**: `Login`, `Logout`, `Register`, `UpdateAccount`, `EditNavbar`, `EditForm`, `EditColumn`.

## Status: COMPLETED
Implementation is fully verified. The expanded test suite (Test 10 & 11) confirms that all management CRUD operations are functional within the new Front Controller architecture. Phase 4 is hardened and stable.
