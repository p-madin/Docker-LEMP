<?php

class SecurityTest extends TestSuiteBase {
    public function __construct() {
        parent::__construct("Security Sanitization Test Suite");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Security Sanitization Test Suite...\n";
        $pass = true;

        // Autoloader for Security classes (relative to this file)
        spl_autoload_register(function ($class) {
            $prefix = 'App\\Security\\';
            $base_dir = __DIR__ . '/../../Class files/Security/';
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) return;
            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
            if (file_exists($file)) require_once $file;
        });

        // Test Helper
        $runTest = function($name, $callback) use (&$pass) {
            try {
                $callback();
                $GLOBALS['returnable'] .= "[PASS] $name\n";
            } catch (Exception $e) {
                $GLOBALS['returnable'] .= "[FAIL] $name: " . $e->getMessage() . "\n";
                $pass = false;
            }
        };

        // --- Test Cases ---

        $runTest("CleanSanitizer", function() {
            $s = new \App\Security\CleanSanitizer();
            if ($s->sanitize("  hello  ") !== "  hello  ") throw new Exception("Failed");
        });

        $runTest("XssSanitizer", function() {
            $s = new \App\Security\XssSanitizer();
            if ($s->sanitize("<script>alert(1)</script>") !== "&lt;script&gt;alert(1)&lt;/script&gt;") throw new Exception("Failed");
        });

        $runTest("WhitespaceNormalization", function() {
            $s = new \App\Security\WhitespaceNormalization(new \App\Security\CleanSanitizer());
            if ($s->sanitize("  hello   world  ") !== "hello world") throw new Exception("Got: '" . $s->sanitize("  hello   world  ") . "'");
        });

        $runTest("Decoration Chain (StripTags + Length)", function() {
            $s = new \App\Security\LengthLimitDecorator(new \App\Security\StripTagsDecorator(new \App\Security\CleanSanitizer()), 5);
            if ($s->sanitize("<b>hello</b> world") !== "hello") throw new Exception("Got: " . $s->sanitize("<b>hello</b> world"));
        });

        $runTest("SecurityValidation with Array", function() {
            $v = new \App\Security\SecurityValidation();
            $v->setStrategy(new \App\Security\AlphanumericDecorator(new \App\Security\CleanSanitizer()));
            $data = ['name' => 'John @ Doe 123', 'email' => 'john.doe@example.com'];
            $result = $v->process($data);
            if ($result['name'] !== "JohnDoe123") throw new Exception("Failed name: " . $result['name']);
            if ($result['email'] !== "johndoeexamplecom") throw new Exception("Failed email: " . $result['email']);
        });

        $runTest("AttributeWhitelistDecorator", function() {
            $s = new \App\Security\AttributeWhitelistDecorator(new \App\Security\CleanSanitizer(), ['href']);
            $input = '<a href="http://google.com" onclick="alert(1)" title="Go">Link</a>';
            $output = $s->sanitize($input);
            if (strpos($output, 'onclick') !== false) throw new Exception("onclick should be removed");
            if (strpos($output, 'title') !== false) throw new Exception("title should be removed");
            if (strpos($output, 'href') === false) throw new Exception("href should be kept");
        });

        $runTest("IntegerSanitizerDecorator", function() {
            $s = new \App\Security\IntegerSanitizerDecorator(new \App\Security\CleanSanitizer());
            if ($s->sanitize("Age: 25 years") !== "25") throw new Exception("Failed numeric: " . $s->sanitize("Age: 25 years"));
            if ($s->sanitize("-42") !== "-42") throw new Exception("Failed negative: " . $s->sanitize("-42"));
            if ($s->sanitize("not a number") !== "0") throw new Exception("Failed empty: " . $s->sanitize("not a number"));
        });

        $runTest("RootRelativePathDecorator", function() {
            $s = new \App\Security\RootRelativePathDecorator(new \App\Security\CleanSanitizer());
            if ($s->sanitize("dashboard.php") !== "/") throw new Exception("Failed relative: " . $s->sanitize("dashboard.php"));
            if ($s->sanitize("/dashboard.php") !== "/dashboard.php") throw new Exception("Failed root-relative: " . $s->sanitize("/dashboard.php"));
            if ($s->sanitize("//evil.com") !== "/") throw new Exception("Failed protocol-relative: " . $s->sanitize("//evil.com"));
        });

        $runTest("Output Sanitization (xmlDom style)", function() {
            $s = new \App\Security\StripTagsDecorator(new \App\Security\WhitespaceNormalization(new \App\Security\CleanSanitizer()));
            if ($s->sanitize("  <b>Hello</b>   World  ") !== "Hello World") throw new Exception("Failed: " . $s->sanitize("  <b>Hello</b>   World  "));
        });

        return $pass;
    }
}

$test_suite[] = new SecurityTest();
