# Roadmap

This document outlines the comprehensive roadmap for the development of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Security Sanitization (Strategy Pattern)
### Description
Implement a `SecurityValidation` class that decouples sanitization logic from the data flow using the Strategy Pattern. This ensures that as new threats emerge, new strategies can be plugged in without changing core logic.
> [!NOTE]
> **Ground Running**: Ensure the `xmlForm` class automatically applies a "Basic" strategy during its `prep()` method. Focus on white-listing characters for common inputs like usernames.

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
Develop a `QueryBuilder` that generates SQL strings based on a `DatabaseDialect`. This enables PnP DBMS support (MySQL, MariaDB, PostgreSQL, Oracle, MS SQL).
> [!CAUTION]
> **Iceberg Alert**: MySQL does not support standard `FETCH FIRST`. The `Dialect` must handle the translation between `LIMIT/OFFSET` and `OFFSET/FETCH`.

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
Implement a centralized `Router` with Middleware support. The pipeline must include a "Base WAF" to intercept malicious URI patterns or payloads.
> [!NOTE]
> **Ground Running**: The WAF should utilize the `SecurityValidation` strategies from Phase 1 to inspect `$_GET` and `$_POST` globally.

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
Refactor procedural DOM logic into a reusable `Component` system supporting **Nesting** and **Slots**.
> [!IMPORTANT]
> **Ground Running**: Use `data-slot` attributes in HTML templates. The `Component::render()` method must use `DOMXPath` to find these slots and inject children.

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
Centralize rendering through a `ViewManager` supporting Layout Inheritance.
> [!NOTE]
> **Ground Running**: Use output buffering to capture the view content, then inject it into a `Layout` component's "main" slot.

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
Bind PHP data to DOM nodes using standard PHP `DOMDocument` and `XPath` logic.
> [!IMPORTANT]
> **Ground Running**: Avoid non-native methods like `querySelector`. Use `DOMXPath::query()` to find elements by ID or Class for data injection.

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
Manage CSS/JS inclusion with bundling and cache-busting.
> [!NOTE]
> **Ground Running**: The `AssetManager` should track all registered scripts/styles during the request and render the final `<link>` and `<script>` tags in the Layout's `<head>` or `<footer>`.

### Code Example
```php
$assets = new AssetManager();
$assets->registerJs('app.js', ['defer' => true]);
$assets->registerCss('theme.css');

// In Layout template:
echo $assets->renderStyles();
echo $assets->renderScripts();
```
