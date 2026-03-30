<?php

require_once(__DIR__ . '/../TestBase.php');

class xmlDomTest extends TestSuiteBase {
    protected $cookieFile;
    protected $baseUrl = 'https://localhost/';
    protected $lastResponse = '';
    protected $lastUrl = '';

    public function __construct() {
        parent::__construct("Contract-Based Browser Automation Test");
        $this->cookieFile = tempnam(sys_get_temp_dir(), 'test_cookie_');
    }

    public function __destruct() {
        if (file_exists($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function test() {
        global $db;
        $GLOBALS['returnable'] .= "Running Contract-Based Browser Automation Test...\n";
        
        $xmlPath = __DIR__ . '/Test Contract/test-suite.xml';
        if (!file_exists($xmlPath)) {
            $GLOBALS['returnable'] .= "[FAIL] test-suite.xml not found at $xmlPath\n";
            return false;
        }

        $xml = simplexml_load_file($xmlPath);
        $testPassed = true;

        foreach ($xml->testGroup as $group) {
            foreach ($group->test as $test) {
                unlink($this->cookieFile);
                $this->cookieFile = sys_get_temp_dir() . '/test_cookie_' . uniqid() . '.txt';
                $GLOBALS['returnable'] .= " - Test: " . $test->name . "\n";
                foreach ($test->step as $step) {
                    $action = (string)$step->action;
                    $GLOBALS['returnable'] .= "   - Action: $action\n";

                    $res = false;
                    switch ($action) {
                        case 'NavigateTo':
                            $res = $this->handleNavigate((string)$step->url, $step->expected);
                            break;
                        case 'FillForm':
                            $res = $this->handleFillForm($step);
                            break;
                        case 'AssertText':
                            $res = $this->handleAssertText($step);
                            break;
                        case 'Click':
                            $res = $this->handleClick($step);
                            break;
                        default:
                            $GLOBALS['returnable'] .= "     [WARN] Unknown action: $action\n";
                            $res = true; // Skip unknown
                            break;
                    }
                    
                    if ($res) {
                        $rawSnippet = strip_tags($this->lastResponse);
                        $snippet = trim(substr($rawSnippet, 0, 100));
                        // Remove non-printable characters and handle whitespace
                        $snippet = preg_replace('/[\x00-\x1F\x7F-\xFF]/', ' ', $snippet);
                        $snippet = preg_replace('/\s+/', ' ', $snippet);
                        $GLOBALS['returnable'] .= "     [OK] Context: {$this->lastUrl} | Snippet: ...$snippet...\n";
                    } else {
                        $testPassed = false;
                        break 2;
                    }
                }
            }
        }

        $db->exec("DELETE FROM appUsers WHERE username = 'LifecycleUser'");
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'pgsql') {
            $db->exec("ALTER TABLE appUsers ALTER COLUMN auPK RESTART WITH 3");
        } else {
            $db->exec("ALTER TABLE appUsers AUTO_INCREMENT = 3");
        }

        return $testPassed;
    }

    protected function handleNavigate($url, $expected = null) {
        if (strpos($url, 'http') !== 0) {
            $url = $this->baseUrl . ltrim($url, '/');
        }
        
        $ch = $this->prepare_curl($url, $this->cookieFile);
        
        $follow = true;
        if ($expected && isset($expected->statusCode) && (int)$expected->statusCode === 302) {
            $follow = false;
        }
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($ch, CURLOPT_HEADER, true); // Need header if we want to check 302/Location
        $fullResponse = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastResponse = substr($fullResponse, $headerSize);
        $info = curl_getinfo($ch);
        $this->lastUrl = $info['url']; // Update lastUrl with the final URL after redirects if followed
        curl_close($ch);

        if ($expected && isset($expected->statusCode)) {
            $actualCode = $info['http_code'];
            if ($actualCode !== (int)$expected->statusCode) {
                $GLOBALS['returnable'] .= "     [FAIL] Navigate to $url expected status {$expected->statusCode} but got $actualCode\n";
                return false;
            }
        } else {
            // Default check if no specific status code is expected
            if ($info['http_code'] !== 200) {
                $GLOBALS['returnable'] .= "     [FAIL] Navigate to $url expected status 200 but got " . $info['http_code'] . "\n";
                return false;
            }
        }

        if ($expected && $expected->pageTitle) {
            if (strpos($this->lastResponse, (string)$expected->pageTitle) === false) {
                $GLOBALS['returnable'] .= "     [FAIL] Expected page title '" . $expected->pageTitle . "' not found.\n";
                return false;
            }
        }

        return true;
    }

    protected function handleFillForm($step) {
        $selector = (string)$step->selector; 
        $id = ltrim($selector, '#');
        $csrfMode = (isset($step->csrfMode)) ? (string)$step->csrfMode : 'auto';
        
        // Use lastResponse if available, otherwise fetch baseUrl
        $response = $this->lastResponse ?: '';
        if (empty($response)) {
            $ch = $this->prepare_curl($this->baseUrl, $this->cookieFile);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $fullResponse = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($fullResponse, 0, $headerSize);
            $response = substr($fullResponse, $headerSize);
            $this->lastResponse = $response;
            $this->lastUrl = $this->baseUrl;
            curl_close($ch);
        }

        $dom = DOM\HTMLDocument::createFromString('<!DOCTYPE html>'.$response, Dom\HTML_NO_DEFAULT_NS);
        $xpath = new DOM\XPath($dom);

        $form = $xpath->query("//form[@id='$id']")->item(0);
        if (!$form) {
            $GLOBALS['returnable'] .= "     [FAIL] Form with id '$id' not found in current context (URL: {$this->lastUrl}).\n";
            $GLOBALS['returnable'] .= "     [DEBUG] Response Length: " . strlen($response) . "\n";
            $GLOBALS['returnable'] .= "     [DEBUG] Body Snippet: " . substr($response, 0, 9000) . "\n";
            
            // Output any libxml errors
            foreach (libxml_get_errors() as $error) {
                $GLOBALS['returnable'] .= "     [LIBXML ERROR] " . trim($error->message) . "\n";
            }
            libxml_clear_errors();
            
            file_put_contents('/tmp/last_failure.html', $response);
            $GLOBALS['returnable'] .= "     [DEBUG] Full response dumped to /tmp/last_failure.html\n";
            
            return false;
        }

        $actionUrl = $form->getAttribute('action');
        if (strpos($actionUrl, 'http') !== 0) {
            $actionUrl = $this->baseUrl . ltrim($actionUrl, '/');
        }

        $postData = [];
        // Extract ALL existing fields from the form
        if ($csrfMode !== 'invalid') {
            // Find all inputs, selects, textareas
            $inputs = $xpath->query(".//input|.//select|.//textarea", $form);
            foreach ($inputs as $input) {
                $name = $input->getAttribute('name');
                if (empty($name)) continue;

                $type = strtolower($input->getAttribute('type'));
                
                if ($type === 'checkbox' || $type === 'radio') {
                    if ($input->hasAttribute('checked')) {
                        $postData[$name] = $input->getAttribute('value') ?: '1';
                    }
                } else {
                    $postData[$name] = $input->getAttribute('value');
                    // For textarea, we'd ideally get nodeValue, but for this app value is often set or and it's mostly inputs
                    if ($input->nodeName === 'textarea') {
                        $postData[$name] = $input->nodeValue;
                    }
                }
            }
        }

        if ($csrfMode === 'invalid') {
            $postData['csrf_token'] = 'invalid_token_value';
        }

        // Fill overrides from XML
        foreach ($step->parameters->parameter as $param) {
            $name = (string)$param->name;
            $value = (string)$param->value;
            
            if ($value === 'checked') {
                $postData[$name] = '1'; 
            } elseif ($value === 'unchecked') {
                unset($postData[$name]);
            } else {
                $postData[$name] = $value;
            }
        }

        // 2. Submit Form
        $ch = $this->prepare_curl($actionUrl, $this->cookieFile);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $fullResponse = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastResponse = substr($fullResponse, $headerSize);
        $info = curl_getinfo($ch);
        $this->lastUrl = $info['url']; // Use effective URL
        curl_close($ch);

        $expectedStatus = (isset($step->expected->statusCode)) ? (int)$step->expected->statusCode : null;
        
        if (!is_null($expectedStatus)) {
            if ($info['http_code'] !== $expectedStatus) {
                $GLOBALS['returnable'] .= "     [FAIL] Form submission expected status $expectedStatus but got " . $info['http_code'] . "\n";
                return false;
            }
        } elseif ($info['http_code'] !== 200 && $info['http_code'] !== 302) {
            $GLOBALS['returnable'] .= "     [FAIL] Form submission to $actionUrl failed with code " . $info['http_code'] . "\n";
            return false;
        }

        return true;
    }

    protected function handleAssertText($step) {
        $response = $this->lastResponse;
        $success = true;

        if (isset($step->expected->contains)) {
            foreach ($step->expected->contains as $expectedContains) {
                $expectedContains = (string)$expectedContains;
                if (strpos($response, $expectedContains) === false) {
                    $GLOBALS['returnable'] .= "     [FAIL] Expected text '$expectedContains' not found in response from {$this->lastUrl}.\n";
                    $snippet = trim(substr(strip_tags($response), 0, 200));
                    $GLOBALS['returnable'] .= "     [DEBUG] Response Snippet: ...$snippet...\n";
                    $success = false;
                }
            }
        }

        if (isset($step->expected->notContains)) {
            foreach ($step->expected->notContains as $expectedNotContains) {
                $expectedNotContains = (string)$expectedNotContains;
                if (strpos($response, $expectedNotContains) !== false) {
                    $GLOBALS['returnable'] .= "     [FAIL] Found forbidden text '$expectedNotContains' in response.\n";
                    $success = false;
                }
            }
        }

        return $success;
    }

    protected function handleClick($step) {
        $selector = (string)$step->selector; 
        $near = (isset($step->near)) ? (string)$step->near : null;
        
        $response = $this->lastResponse;
        if (empty($response)) {
            $ch = $this->prepare_curl($this->baseUrl, $this->cookieFile);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $fullResponse = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $response = substr($fullResponse, $headerSize);
            curl_close($ch);
            $this->lastResponse = $response;
            $this->lastUrl = $this->baseUrl;
        }

        $dom = DOM\HTMLDocument::createFromString('<!DOCTYPE html>'.$response, Dom\HTML_NO_DEFAULT_NS);
        $xpath = new DOM\XPath($dom);

        $targetNode = null;

        if ($selector === 'logout') {
            $targetNode = $xpath->query("//form[contains(@action, 'logout-action.php')]")->item(0);
        } elseif (strpos($selector, '#') === 0) {
            $id = ltrim($selector, '#');
            $targetNode = $xpath->query("//*[@id='$id']")->item(0);
        } else {
            // General link text or button text
            if ($near) {
                // Find node containing $near, then find $selector link in same container (e.g. row)
                $nearNode = $xpath->query("//*[contains(text(), '$near')]")->item(0);
                if ($nearNode) {
                    // Look for link in same parent or ancestor (like div.flex-row or tr)
                    $ancestor = $nearNode->parentNode;
                    for ($i = 0; $i < 3; $i++) {
                        if ($ancestor) {
                            $link = $xpath->query(".//form[.//a[contains(text(), '$selector')]]", $ancestor)->item(0);
                            if ($link) {
                                $targetNode = $link;
                                break;
                            }
                            $ancestor = $ancestor->parentNode;
                        }
                    }
                }
            } else {
                $targetNode = $xpath->query("//a[contains(text(), '$selector')] | //button[contains(text(), '$selector')]")->item(0);
            }
        }

        if (!$targetNode) {
            $GLOBALS['returnable'] .= "     [FAIL] Target for Click action '$selector' " . ($near ? "near '$near'" : "") . " not found.\n";
            return false;
        }

        if ($targetNode->nodeName === 'form') {
            $actionUrl = $targetNode->getAttribute('action');
            if (strpos($actionUrl, 'http') !== 0) {
                $actionUrl = $this->baseUrl . ltrim($actionUrl, '/');
            }
            $postData = [];
            $hiddenInputs = $xpath->query(".//input[@type='hidden']", $targetNode);
            foreach ($hiddenInputs as $input) {
                $postData[$input->getAttribute('name')] = $input->getAttribute('value');
            }
            $ch = $this->prepare_curl($actionUrl, $this->cookieFile);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $fullResponse = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->lastResponse = substr($fullResponse, $headerSize);
            $info = curl_getinfo($ch);
            $this->lastUrl = $info['url']; // Use effective URL
            curl_close($ch);
        } elseif ($targetNode->nodeName === 'a') {
            return $this->handleNavigate($targetNode->getAttribute('href'));
        } else {
            $GLOBALS['returnable'] .= "     [WARN] Click on {$targetNode->nodeName} not fully implemented, skipping.\n";
        }

        return true;
    }
}

$test_suite[] = new xmlDomTest();
