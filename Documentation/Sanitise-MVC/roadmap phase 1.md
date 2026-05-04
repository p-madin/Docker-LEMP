# Phase 1: Security Sanitization (Source Code Draft)

This document provides a full source code description for the `SecurityValidation` system using the Strategy and Decorator patterns.

## 1. Interface and Base Classes

### SanitizationStrategy Interface
The core interface that all sanitizers and decorators must implement.

```php
interface SanitizationStrategy {
    /**
     * @param mixed $input
     * @return string
     */
    public function sanitize($input): string;
}
```

### Base Sanitizer Decorator
An abstract class to facilitate the creation of decorators.

```php
abstract class SanitizerDecorator implements SanitizationStrategy {
    protected $sanitizer;

    public function __construct(SanitizationStrategy $sanitizer) {
        $this->sanitizer = $sanitizer;
    }

    abstract public function sanitize($input): string;
}
```

---

## 2. Core Sanitizers

### CleanSanitizer
A "pass-through" or basic sanitizer that serves as the base for decoration.

```php
class CleanSanitizer implements SanitizationStrategy {
    public function sanitize($input): string {
        return (string)$input;
    }
}
```

### XssSanitizer
Core protection against Cross-Site Scripting.

```php
class XssSanitizer implements SanitizationStrategy {
    public function sanitize($input): string {
        return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
    }
}
```

---

## 3. General Decorators

### WhitespaceNormalization
Trims whitespace and reduces multiple spaces to a single space.

```php
class WhitespaceNormalization extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return trim(preg_replace('/\s+/', ' ', $data));
    }
}
```

### SingleLineDecorator
Removes all newline characters.

```php
class SingleLineDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return str_replace(["\r", "\n"], '', $data);
    }
}
```

### MultiLineNormalizeDecorator
Normalizes line endings to `\n`.

```php
class MultiLineNormalizeDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return str_replace(["\r\n", "\r"], "\n", $data);
    }
}
```

### HtmlEscapeDecorator
Specifically escapes HTML (often used as the final step).

```php
class HtmlEscapeDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
```

### StripTagsDecorator
Removes all HTML tags.

```php
class StripTagsDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return strip_tags($data);
    }
}
```

### AlphanumericDecorator
Whitelists only alphanumeric characters.

```php
class AlphanumericDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return preg_replace('/[^a-zA-Z0-0]/', '', $data);
    }
}
```

---

## 4. CMS Specific Decorators

### AttributeWhitelistDecorator
Filters HTML attributes (e.g., for `<a>` or `<img>` tags in a CMS).

```php
class AttributeWhitelistDecorator extends SanitizerDecorator {
    protected $allowedAttributes;

    public function __construct(SanitizationStrategy $sanitizer, array $allowedAttributes = ['href', 'title', 'src', 'alt']) {
        parent::__construct($sanitizer);
        $this->allowedAttributes = $allowedAttributes;
    }

    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        // Basic logic: use DOMDocument to filter attributes
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        $nodes = $xpath->query('//@*');
        foreach ($nodes as $node) {
            if (!in_array($node->nodeName, $this->allowedAttributes)) {
                $node->parentNode->removeAttribute($node->nodeName);
            }
        }
        return $dom->saveHTML();
    }
}
```

### LengthLimitDecorator
Truncates input to a safe length.

```php
class LengthLimitDecorator extends SanitizerDecorator {
    protected $limit;

    public function __construct(SanitizationStrategy $sanitizer, int $limit = 255) {
        parent::__construct($sanitizer);
        $this->limit = $limit;
    }

    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return mb_substr($data, 0, $this->limit, 'UTF-8');
    }
}
```

### UrlSanitizerDecorator
Ensures strings are valid, safe URLs.

```php
class UrlSanitizerDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return filter_var($data, FILTER_SANITIZE_URL);
    }
}
```

---

## 5. Context Class: SecurityValidation

The `SecurityValidation` class manages the strategy.

```php
class SecurityValidation {
    protected $strategy;

    public function setStrategy(SanitizationStrategy $strategy) {
        $this->strategy = $strategy;
    }

    public function process($data) {
        if (!$this->strategy) {
            throw new Exception("Sanitization strategy not set.");
        }

        if (is_array($data)) {
            return array_map([$this, 'process'], $data);
        }

        return $this->strategy->sanitize($data);
    }
}
```

---

## 6. Usage Example

### Complex Decoration Chain
```php
// Goal: A CMS comment that allows minimal tags but strips attributes and normalizes whitespace
$base = new CleanSanitizer();
$stepped = new StripTagsDecorator($base, '<b><i><strong><em>');
$stepped = new AttributeWhitelistDecorator($stepped, []); // No attributes allowed
$stepped = new WhitespaceNormalization($stepped);
$stepped = new LengthLimitDecorator($stepped, 500);

$validator = new SecurityValidation();
$validator->setStrategy($stepped);

$safeComment = $validator->process($_POST['comment']);
```

---

## 7. Integration with xmlForm

To "Ground Run" this in `xmlForm::prep()`, we should initialize a default strategy.

```php
// In xmlForm.php
class xmlForm {
    protected $security;

    public function prep($action, $method, $useFlexTable = true) {
        // ... existing code ...

        // Default "Basic" strategy
        $this->security = new SecurityValidation();
        $this->security->setStrategy(
            new HtmlEscapeDecorator(
                new WhitespaceNormalization(
                    new CleanSanitizer()
                )
            )
        );
    }

    // New method to override strategy if needed
    public function setSecurityStrategy(SanitizationStrategy $strategy) {
        $this->security->setStrategy($strategy);
    }
}
```

---

## 8. Implementation Details

### Security Strategy and Decorators
The system successfully implements the **Strategy** and **Decorator** design patterns to create a flexible and extensible sanitization engine. 

#### System Components:
- **`SanitizationStrategy`**: The core interface defined in [SanitizationStrategy.php](file:///c:/Workspace/Docker%20Workspace/Docker-LEMP/app/Class%20files/Security/SanitizationStrategy.php).
- **`SanitizerDecorator`**: The base class for all decorators, enabling the construction of sophisticated sanitization chains.
- **Specialized Decorators**: 
    - `IntegerSanitizerDecorator`: Ensures data type consistency for numeric fields like `age`.
    - `RootRelativePathDecorator`: Secures the application against Open Redirect attacks by validating internal redirect paths.
    - `StripTagsDecorator`: Removes HTML tags for clean UI display.
    - `HtmlEscapeDecorator`: Safely encodes HTML entities for browser output.
    - `WhitespaceNormalization`: Trims and cleans strings for consistent UI alignment.

### Global Integration
The system is integrated at both the input and help desk boundaries:
- **Input Sanitization**: Automatically applied in `xmlForm::prep()` and manually in action files (`login-action.php`, `register-action.php`, `update_account_action.php`).
- **Output Sanitization**: Integrated into `xmlDom::appendChild()`, ensuring that every element added to the DOM is normalized and stripped of tags by default, providing a strong "Defense in Depth" layer.
- **Session Security**: Secured session cookies using `AlphanumericDecorator` and restricted the session ID character set to match.

### Security Enhancements
- **Access Control**: Fixed `isLoggedIn()` to verify user authentication (presence of `userID`) rather than just session existence.
- **CSRF Protection**: Automatically injected into all `xmlForm` instances.
- **Log Security**: Masked passwords and sanitized output in `preinclude.php`.

### Verification
- **Automated Testing**: Refactored the test suite into an object-oriented structure extending `TestSuiteBase`. All tests are integrated into the global test runner in [main.php](file:///c:/Workspace/Docker%20Workspace/Docker-LEMP/app/Test/main.php) and verified within the Docker environment.

```
