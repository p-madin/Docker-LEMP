<?php

class ComprehensiveSecurityValidationTest extends TestSuiteBase {
    private $cookieFile = "/tmp/integration_test_cookie.txt";
    private $homeUrl = "https://localhost/index.php";
    private $loginAction = "https://localhost/login-action.php";
    private $registerAction = "https://localhost/register-action.php";

    public function __construct() {
        parent::__construct("Comprehensive Security & Validation Integration Suite");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Comprehensive Security & Validation Integration Tests...\n";
        $pass = true;

        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }

        // Test Helper for HTTP Tests
        $runHttpTest = function($name, $callback) use (&$pass) {
            try {
                $callback();
                $GLOBALS['returnable'] .= "[PASS] $name\n";
            } catch (Exception $e) {
                $GLOBALS['returnable'] .= "[FAIL] $name: " . $e->getMessage() . "\n";
                $pass = false;
            }
        };

        // 1. Test Required Fields & Error isolation
        $runHttpTest("Form Isolation (Login errors should not appear on Register)", function() {
            // First, trigger a login error
            $token = $this->getCsrfToken();
            $ch = $this->prepare_curl($this->loginAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => '', // Should fail
                'password' => '', // Should fail
                'csrf_token' => $token
            ]));
            curl_exec($ch);
            curl_close($ch);

            // Fetch home page to check errors
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res = curl_exec($ch);
            curl_close($ch);

            if (strpos($res, 'Login form') === false) throw new Exception("Home page failed to load");
            
            // Check for login errors in the HTML
            // (Assuming errors are rendered within a 'validation-error' class as per xmlForm.php)
            if (strpos($res, 'The username field is required.') === false) throw new Exception("Login error not found");
            
            // Verify Register form (next to it) does NOT have errors
            // We'll search for 'Register form' heading and then look for errors after it
            $registerPos = strpos($res, 'Register form');
            $partAfterRegister = substr($res, $registerPos);
            if (strpos($partAfterRegister, 'validation-error') !== false) {
                 // But wait, it might have errors if we accidentally triggered them. 
                 // Here we expect NONE because we only hit login-action.
                 throw new Exception("Registration form polluted with login errors");
            }
        });

        // 2. Test Data Retention
        $runHttpTest("Data Retention (Non-sensitive data preserved after failure)", function() {
            $token = $this->getCsrfToken();
            $ch = $this->prepare_curl($this->registerAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => 'persist_user',
                'password' => 'short', // Trigger server-side validation error (min:6)
                'confirm_password' => 'short',
                'name' => 'Persist Name',
                'age' => '30',
                'email' => 'invalid-email', // Trigger email error
                'csrf_token' => $token
            ]));
            curl_exec($ch);
            curl_close($ch);

            // Fetch home page
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res = curl_exec($ch);
            curl_close($ch);

            if (strpos($res, 'value="persist_user"') === false) throw new Exception("Username not retained");
            if (strpos($res, 'value="Persist Name"') === false) throw new Exception("Name not retained");
            if (strpos($res, 'value="30"') === false) throw new Exception("Age not retained");
            if (strpos($res, 'type="password" value="') !== false) {
                // Check if password value is NOT present (security requirement)
                // Search for any password input with a non-empty value
                if (preg_match('/type="password"[^>]*value="[^"]+"/', $res)) {
                    throw new Exception("Security Breach: Password value retained in DOM");
                }
            }
        });

        // 3. Test Sanitization (XSS)
        $runHttpTest("XSS Sanitization (Inputs escaped when rendered back)", function() {
            $token = $this->getCsrfToken();
            $xssPayload = "<script>alert('xss')</script>";
            
            $ch = $this->prepare_curl($this->registerAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => 'xss_tester',
                'name' => $xssPayload,
                'email' => 'not-an-email', // Trigger validation failure to force re-render
                'csrf_token' => $token
            ]));
            curl_exec($ch);
            curl_close($ch);

            // Fetch home page
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res = curl_exec($ch);
            curl_close($ch);

            // Verify specifically the 'name' input field in the registration form
            // We'll use a regex to find the value specifically for id="register_name_UI"
            if (!preg_match('/id="register_name_UI"[^>]*value="([^"]*)"/', $res, $matches)) {
                throw new Exception("Could not find register_name_UI input field in response");
            }
            
            $renderedValue = $matches[1];

            // 1. Check for RAW script tags (Security failure)
            if (strpos($renderedValue, '<script>') !== false) {
                throw new Exception("XSS Script tag found RAW in input value! Security failure.");
            }

            // 2. Check that tags were STRIPPED (per Phase 3.1 requirement)
            // They should not be there even escaped.
            if (strpos($renderedValue, '&lt;script>') !== false || strpos($renderedValue, '&lt;script&gt;') !== false) {
                 throw new Exception("XSS Script tag was NOT stripped (found escaped instead) in input value: " . htmlspecialchars($renderedValue));
            }

            // 3. Check that the content 'alert' is still there
            if (strpos($renderedValue, 'alert') === false) {
                 throw new Exception("XSS Payload content lost. Input value: " . htmlspecialchars($renderedValue));
            }
        });

        // 4. Boundary & Edge Cases
        $runHttpTest("Numeric & Min/Max Boundaries", function() {
            $token = $this->getCsrfToken();
            
            // Test 1: Non-numeric Age
            $ch = $this->prepare_curl($this->registerAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => 'boundary_user',
                'age' => 'twenty', // Should fail
                'csrf_token' => $token
            ]));
            curl_exec($ch);
            curl_close($ch);
            
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res = curl_exec($ch);
            curl_close($ch);
            if (strpos($res, 'The age must be a number.') === false) throw new Exception("Numeric validation failed for 'twenty'. Got output: " . substr($res, 0, 1000));

            // Test 2: Min length on redirect
            $token = $this->getCsrfToken();
            $ch = $this->prepare_curl($this->registerAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
                'username' => 'ab', // min:3
                'csrf_token' => $token
            ]));
            curl_exec($ch);
            curl_close($ch);
            
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res = curl_exec($ch);
            curl_close($ch);
            if (strpos($res, 'The username must be at least 3 characters.') === false) throw new Exception("Min length validation failed");
        });

        // 5. Flash Behavior Verification
        $runHttpTest("Flash Destruction (Errors disappear after refresh)", function() {
            // First, trigger an error (as in previous tests, but we'll do a fresh one)
            $token = $this->getCsrfToken();
            $ch = $this->prepare_curl($this->loginAction, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['username'=>'', 'csrf_token'=>$token]));
            curl_exec($ch);
            curl_close($ch);

            // First impression: should have error
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res1 = curl_exec($ch);
            curl_close($ch);
            if (strpos($res1, 'validation-error') === false) throw new Exception("Error didn't appear on first impression");

            // Second impression: should be GONE
            $ch = $this->prepare_curl($this->homeUrl, $this->cookieFile);
            $res2 = curl_exec($ch);
            curl_close($ch);
            if (strpos($res2, 'validation-error') !== false) throw new Exception("Error persisted after second impression (Flash failed)");
        });

        if (file_exists($this->cookieFile)) unlink($this->cookieFile);
        return $pass;
    }

    private function getCsrfToken() {
        $ch = $this->prepare_curl($homeUrl = $this->homeUrl, $this->cookieFile);
        $res = curl_exec($ch);
        curl_close($ch);

        $doc = \Dom\HTMLDocument::createFromString($res, LIBXML_NOERROR);
        $input = $doc->querySelector('input[name="csrf_token"]');
        if (!$input) throw new Exception("Could not find CSRF token");
        return $input->getAttribute('value');
    }
}

$test_suite[] = new ComprehensiveSecurityValidationTest();
