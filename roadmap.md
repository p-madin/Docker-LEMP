# Roadmap

This document outlines the comprehensive roadmap for the development of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Security Sanitization (Strategy Pattern)
### Description
Implement a `SecurityValidation` class that decouples sanitization logic from the data flow using the Strategy Pattern. This ensures that as new threats emerge, new strategies can be plugged in without changing core logic.
> [!NOTE]
> **Ground Running**: Ensure the `xmlForm` class automatically applies a "Basic" strategy during its `prep()` method. Focus on white-listing characters for common inputs like usernames.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Low (Isolated, easy to test)
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~4 (Base class, interface, default strategies)
- **Lines of Code**: ~300 LOC


### Prerequisites
- Knowledge of the Strategy Pattern.
- Understanding of PHP `FILTER_SANITIZE_*`.

### Related Components
- `./app/Class files/SecurityValidation.php`
- `./app/Class files/xmlForm.php`

### Code Example
```php
interface SanitizationStrategy {
    public function sanitize($input): string;
}

class XssSanitizer implements SanitizationStrategy {
    public function sanitize($input): string {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
}

class SecurityValidation {
    protected $strategy;
    public function setStrategy(SanitizationStrategy $strategy) {
        $this->strategy = $strategy;
    }
    public function process($data) {
        if (is_array($data)) return array_map([$this->strategy, 'sanitize'], $data);
        return $this->strategy->sanitize($data);
    }
}
```

---

## Phase 2: User Input Validation (xmlForm Extensions)
### Description
Extend `xmlForm` to support server-side validation that mirrors client-side constraints. Metadata added during `addRow()` should be used by a `Validator` engine upon submission.
> [!IMPORTANT]
> **Ground Running**: The `addRow()` method must be extended to capture a `$rules` array. The `Validator` should return a structured `ErrorBag` that can be re-bound to the form for error display.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low (UI logic mostly, isolated validation engine)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3 (Validator class, xmlForm modifications)
- **Lines of Code**: ~400-500 LOC


### Related Components
- `./app/Class files/xmlForm.php`
- `./app/Class files/Validator.php`

### Code Example
```php
// Usage in Controller
$validator = new Validator($_POST);
$validator->rule('username', 'required|min:5|alpha_numeric');
$validator->rule('email', 'required|email');

if ($validator->fails()) {
    $session->setPrimary('form_errors', $validator->errors());
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
```

---

## Phase 3: ISO SQL QueryBuilder (Dialect Pattern)
### Description
Develop a `QueryBuilder` that generates SQL strings based on a `DatabaseDialect`. This enables PnP DBMS support (MySQL, MariaDB, PostgreSQL, Oracle, MS SQL), **strictly restricted to a functional union of common OLTP grammar** (i.e., basic `SELECT`, `INSERT`, `UPDATE`, and `DELETE`). Complex edge-cases, schema definitions, and vendor-specific features are excluded.
> [!CAUTION]
> **Iceberg Alert**: MySQL does not support standard `FETCH FIRST`. The `Dialect` must handle the translation between `LIMIT/OFFSET` and `OFFSET/FETCH` for basic pagination.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium (Focusing only on a functional union of standard CRUD operations significantly reduces the challenge of vendor variations)
- **Risk Level**: Low-Medium (Database interactions are critical, but simple OLTP abstraction is standardized and safe)
- **Estimated Time**: 1-3 Days
- **Number of Files**: ~6 (Builder Engine, Dialect Interfaces, specific Dialect implementations)
- **Lines of Code**: ~400-600 LOC


### Related Components
- `./app/Class files/db.php`
- `./app/Class files/QueryBuilder.php`
- `./app/Class files/Dialect/`

### Code Example
```php
$qb = new QueryBuilder(new MySQLDialect());
$sql = $qb->table('users')
          ->select(['id', 'username'])
          ->where('status', '=', 1)
          ->offset(10)
          ->limit(20) 
          ->toSQL(); 
// Produces: SELECT `id`, `username` FROM `users` WHERE `status` = 1 LIMIT 20 OFFSET 10
```

---

## Phase 4: Router, Middleware and WAF [COMPLETED]
### Description
Implement a centralized `Router` with Middleware support. The pipeline includes a "Base WAF" to intercept malicious URI patterns or payloads. The WAF is enriched with active defense mechanisms, such as rate-limiting and auto-banning, to block abusive HTTP clients.

> [!NOTE]
> **Ground Running**: 
> - **Resource Optimized Execution**: The middleware stack is ordered so that `WafMiddleware` runs **before** `SessionMiddleware`, protecting the server's session memory from banned or malicious clients.
> - **Nginx Integration**: Direct access to internal PHP files is blocked at the Nginx level, forcing all traffic through the WAF-protected `index.php` entry point.
> - **Active Defense (Auto-ban)**: Thresholds for rapid requests or bad payloads trigger automatic temporary bans.
> - **Logging**: Attack data is persisted to the `httpAction` table, even when the request is blocked before a session is established.

### Metrics (Qualities & Quantities)
- **Complexity**: High (Chain of responsibility, request lifecycle manipulation)
- **Status**: ✅ Fully Implemented and Hardened
- **Key Files**: `Router.php`, `WafMiddleware.php`, `RateLimiter.php`, `HttpActionMiddleware.php`

### Code Example
```php
$router = new Router();
$router->use(new DatabaseConfigMiddleware());
$router->use(new WafMiddleware()); // Blocks before session start
$router->use(new SessionMiddleware());
$router->use(new HttpActionMiddleware());
```
```

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/profile/update', [ProfileController::class, 'update']);

$router->dispatch();
```

---

## Phase 5: Enhance DOM to Component System [COMPLETED]
### Description
Refactor procedural DOM logic into a reusable `Component` system supporting **Nesting** and **Slots**. This includes transforming the extended `xmlForm` (from Phase 2) into a cohesive `FormComponent`.

> [!IMPORTANT]
> **Ground Running**: 
> - Use `data-slot` attributes in HTML templates. The `Component::render()` method must use `DOMXPath` to find these slots and inject children.
> - **Defense in Depth Tracking**: When injecting slot content or compiling components, the engine must implicitly apply Phase 1 output sanitization rules (e.g. `StripTagsDecorator` or `HtmlEscapeDecorator`) to raw data to ensure XSS defense remains intact at the DOM serialization boundary.

### Metrics (Qualities & Quantities)
- **Complexity**: High (Transitioning procedural node appending to declarative trees)
- **Risk Level**: Medium (High refactoring footprint in UI layer)
- **Estimated Time**: 4-6 Days
- **Number of Files**: ~5+ (Component base class, various concrete Layout Components)
- **Lines of Code**: ~700-900 LOC


### Code Example
```php
class Card extends Component {
    public function render(DOMDocument $dom): DOMElement {
        $el = $dom->createElement('div', ['class' => 'card']);
        $slot = $this->createSlot($dom, $el, 'body');
        foreach ($this->children['body'] as $child) {
            $slot->appendChild($child->render($dom));
        }
        return $el;
    }
}
```

---

## Phase 6: View Management and Layouts
### Description
Centralize rendering through a `ViewManager` supporting Layout Inheritance and Global State Injection. Move controllers away from direct DOM manipulation to returning declarative View results.

> [!NOTE]
> **Ground Running**: 
> - **View Pipeline**: Capture component output via the `ViewManager` and inject it into a `Layout` component's "main" slot.
> - **Global State**: The `ViewManager` must automatically expose session state (User info, Breadcrumbs, Flash messages) to all layouts, eliminating repetitive boilerplate in management controllers.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3 (ViewManager, BaseLayout, DashboardLayout)
- **Lines of Code**: ~300-400 LOC

### Code Example
```php
class DashboardController {
    public function execute(Request $request) {
        // ... logic ...
        return View::render('dashboard/index', [
            'graph' => $graphComponent,
            'filters' => $filterComponent
        ], 'layouts/admin');
    }
}
```

---

## Phase 7: Advanced Interaction (AJAX & Hybrid Validation)
### Description
Implement real-time form feedback via `validate.js` and transition forms to `fetch()`-based submission. This phase bridges the `tcRules` defined in the database with browser-side execution.

> [!IMPORTANT]
> **Ground Running**: 
> - **Validator Bridge**: The `FormComponent` must serialize `tblColumns.tcRules` JSON into `data-rules` attributes on input elements.
> - **validate.js Implementation**: Create a native Javascript validator (no jQuery) that parses `data-rules`. Implement direct browser equivalents for: `required`, `min`, `max`, `numeric`, `email`, and `match`.
> - **Server-Only Fallback**: Validation rules requiring database checks (e.g., unique username/email) will be handled via the `fetch()` submission lifecycle.
> - **Success Hydration**: Use the `fetch()` API to submit `FormData`. On validation failure, the server returns a JSON error fragment; on success, it returns a "Success Component" to be injected into the DOM.

### Metrics (Qualities & Quantities)
- **Complexity**: High (Synchronizing PHP validation logic with JS equivalents)
- **Risk Level**: Medium (Ensuring validation parity)
- **Estimated Time**: 4-6 Days
- **Number of Files**: ~4 (validate.js, FormComponent updates, AjaxController)
- **Lines of Code**: ~600+ LOC

### Code Example
```javascript
// validate.js using fetch()
document.querySelectorAll('form[data-ajax="true"]').forEach(form => {
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!validateForm(form)) return; // Checks data-rules

        const response = await fetch(form.action, { 
            method: 'POST', 
            body: new FormData(form),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const result = await response.json();
        handleResponse(form, result);
    });
});
```

---

## Phase 8: Asset Orchestration & Security Hardening
### Description
Implement a secure Asset Pipeline for CSS/JS delivery, including cache-busting and browser-enforced Content Security Policy (CSP).

> [!NOTE]
> **Ground Running**: 
> - **AssetManager**: Centrally track and register all scripts/styles. Automatically generate `<link>` and `<script>` tags with versioned filenames for cache-busting.
> - **CSP Nonces**: Generate and register cryptographically strong nonces for any necessary inline scripts and inject the appropriate `Content-Security-Policy` headers.
> - **Final Polish**: Minification and bundling of core system scripts (`validator.js`, `dashboard.js`).

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~2-3 (AssetManager, CSPMiddleware)
- **Lines of Code**: ~200-300 LOC

### Code Example
```php
$assets = new AssetManager();
$assets->registerJs('validator.js', ['defer' => true]);
$assets->registerCss('styles.css');

// In Layout:
echo $assets->renderScripts(); // Outputs <script src="/Static/validator.js?v=1.0.1" nonce="...">
```
