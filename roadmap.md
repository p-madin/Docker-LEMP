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

## Phase 4: Router, Middleware and WAF
### Description
Implement a centralized `Router` with Middleware support. The pipeline must include a "Base WAF" to intercept malicious URI patterns or payloads. The WAF will be enriched with active defense mechanisms, such as rate-limiting and auto-banning, to block abusive HTTP clients (similar to `mod_evasive`).

> [!NOTE]
> **Ground Running**: 
> - **Inspection**: The WAF should utilize the `SecurityValidation` strategies from Phase 1 to inspect `$_GET` and `$_POST` globally.
> - **Active Defense (Auto-ban)**: Implement thresholds for rapid requests (e.g., brute-force attempts) or known bad payloads. Clients exceeding thresholds should be automatically temporarily banned (e.g., returning 429 Too Many Requests or 403 Forbidden).
> - **Logging**: Use the `QueryBuilder` from Phase 3 to persist WAF events, banned IP logs, and anomalous behavior to the database.

### Metrics (Qualities & Quantities)
- **Complexity**: High (Chain of responsibility, request lifecycle manipulation, stateful tracking for rate limiting)
- **Risk Level**: High (Can break routing or block valid traffic with false-positive WAF rules)
- **Estimated Time**: 3-5 Days
- **Number of Files**: ~5-7 (Router, Middleware interface, WAF, Auth middleware, RateLimiter)
- **Lines of Code**: ~800 LOC


### Code Example
```php
$router = new Router();
$router->use(new WafMiddleware());
$router->use(new AuthMiddleware());

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/profile/update', [ProfileController::class, 'update']);

$router->dispatch();
```

---

## Phase 5: Enhance DOM to Component System
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

## Phase 6: View Manager and Layouts
### Description
Centralize rendering through a `ViewManager` supporting Layout Inheritance and Global State Injection.

> [!NOTE]
> **Ground Running**: 
> - Use output buffering to capture the view content, then inject it into a `Layout` component's "main" slot.
> - **State Integration**: The `ViewManager` must automatically fetch Flash Cache data (Validation Errors and Old Input Data established in Phase 2) and expose it globally to Layouts. This reduces boilerplate and ensures UI components always have access to `$errors` state.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~2-3 (ViewManager, Base Layout components)
- **Lines of Code**: ~300 LOC


### Code Example
```php
class ViewManager {
    public function render($viewPath, $data = [], $layout = 'main') {
        extract($data);
        ob_start();
        include "views/{$viewPath}.php";
        $content = ob_get_clean();
        
        $layoutComp = new Layout($layout);
        $layoutComp->setSlot('content', $content);
        echo $layoutComp->toHtml();
    }
}
```

---

## Phase 7: View Data Binding
### Description
Bind PHP data to DOM nodes using standard PHP `DOMDocument` and `XPath` logic, seamlessly repopulating form state using prior validation run data.

> [!IMPORTANT]
> **Ground Running**: 
> - Avoid non-native methods like `querySelector`. Use `DOMXPath::query()` to find elements by ID or Class for data injection.
> - **Form Repopulation**: Connect the Phase 2 data retention logic (flash cache) to the new DOM binding system. The DataBinder should use XPath to locate `input`, `select`, and `textarea` elements matching names in the flash cache to automatically inject their `value` properties.
> - **Secure Data Binding Pipeline**: Explicitly require that dynamic data bound to DOM nodes uses Phase 1 `SecurityValidation` strategies based on context (e.g. `HtmlEscapeDecorator` for Text node binding).

### Metrics (Qualities & Quantities)
- **Complexity**: Very High (Parsing and manipulating large DOM trees dynamically)
- **Risk Level**: High (Performance impact on scale, potential memory leaks in PHP's un-freed DOM nodes)
- **Estimated Time**: 5-7 Days
- **Number of Files**: ~2-4 (DataBinder engine, parsing utilities)
- **Lines of Code**: ~800+ LOC


### Code Example
```php
$xpath = new DOMXPath($dom);
$node = $xpath->query("//span[@id='user-name']")->item(0);
if ($node) {
    $node->nodeValue = $user->getName();
}
```

---

## Phase 8: Asset Pipeline
### Description
Manage CSS/JS inclusion with bundling, cache-busting, and Content Security Policy (CSP) management.

> [!NOTE]
> **Ground Running**: 
> - The `AssetManager` should track all registered scripts/styles during the request and render the final `<link>` and `<script>` tags in the Layout's `<head>` or `<footer>`.
> - **CSP Integration**: Securely generate and register CSP nonces for inline scripts/styles (if any are necessary) and inject the appropriate `Content-Security-Policy` headers. This acts as a browser-enforced shield, complementing the Phase 4 WAF.
> - **Validation Delivery**: Manage the dynamic delivery of the `validator.js` client-side validation logic developed as an extension of Phase 2.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~2 (AssetManager, Cache helper)
- **Lines of Code**: ~200-300 LOC


### Code Example
```php
$assets = new AssetManager();
$assets->registerJs('app.js', ['defer' => true]);
$assets->registerCss('theme.css');

// In Layout template:
echo $assets->renderStyles();
echo $assets->renderScripts();
```
