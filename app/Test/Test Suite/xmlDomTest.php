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
                #if($test->index != 10){
                #    continue;
                #}
                unlink($this->cookieFile);
                $this->cookieFile = sys_get_temp_dir() . '/test_cookie_' . uniqid() . '.txt';
                $GLOBALS['returnable'] .= " - Test: " . $test->name . "\n";
                $step_number = 1;
                foreach ($test->stepList->step as $step) {
                    $action = (string)$step->action;
                    $GLOBALS['returnable'] .= "   - Step: $step_number ($action)\n";

                    $step_number++;

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

    /**
     * Build a DOM document from the current HTML response.
     * Uses Dom\HTMLDocument::createFromString for querySelector() support.
     */
    protected function buildDom(): DOM\HTMLDocument {
        return DOM\HTMLDocument::createFromString('<!DOCTYPE html>' . $this->lastResponse, Dom\HTML_NO_DEFAULT_NS);
    }

    protected function handleNavigate($url, $expected = null) {
        if (strpos($url, 'http') !== 0) {
            $url = $this->baseUrl . ltrim($url, '/');
        }
        
        $ch = $this->prepare_curl($url, $this->cookieFile);
        
        if (!empty($this->lastUrl)) {
            curl_setopt($ch, CURLOPT_REFERER, $this->lastUrl);
        }
        
        $follow = true;
        if ($expected && isset($expected->statusCode) && (int)$expected->statusCode === 302) {
            $follow = false;
        }
        
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $follow);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $fullResponse = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastResponse = substr($fullResponse, $headerSize);
        $info = curl_getinfo($ch);
        $this->lastUrl = $info['url'];
        curl_close($ch);

        if ($expected && isset($expected->statusCode)) {
            $actualCode = $info['http_code'];
            if ($actualCode !== (int)$expected->statusCode) {
                $GLOBALS['returnable'] .= "     [FAIL] Navigate to $url expected status {$expected->statusCode} but got $actualCode\n";
                return false;
            }
        } else {
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
        
        $response = $this->lastResponse ?: '';
        if (empty($response)) {
            $ch = $this->prepare_curl($this->baseUrl, $this->cookieFile);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $fullResponse = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $response = substr($fullResponse, $headerSize);
            $this->lastResponse = $response;
            $this->lastUrl = $this->baseUrl;
            curl_close($ch);
        }

        $dom = $this->buildDom();

        // Use querySelector('#id') — direct, no XPath
        $form = $dom->querySelector("form#{$id}");
        if (!$form) {
            $GLOBALS['returnable'] .= "     [FAIL] Form '#$id' not found (URL: {$this->lastUrl}).\n";
            $GLOBALS['returnable'] .= "     [DEBUG] Response Length: " . strlen($response) . "\n";
            $GLOBALS['returnable'] .= "     [DEBUG] Body Snippet: " . substr($response, 0, 500) . "\n";
            file_put_contents('/tmp/last_failure.html', $response);
            return false;
        }

        $actionUrl = $form->getAttribute('action');
        if (strpos($actionUrl, 'http') !== 0) {
            $actionUrl = $this->baseUrl . ltrim($actionUrl, '/');
        }

        $postData = [];
        if ($csrfMode !== 'invalid') {
            // Use querySelectorAll for inputs within the form
            $inputs = $form->querySelectorAll('input, select, textarea');
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
                    if ($input->nodeName === 'textarea') {
                        $postData[$name] = $input->nodeValue;
                    }
                }
            }
        }

        if ($csrfMode === 'invalid') {
            $postData['csrf_token'] = 'invalid_token_value';
        }

        // Apply overrides from XML parameters
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

        $ch = $this->prepare_curl($actionUrl, $this->cookieFile);
        if (!empty($this->lastUrl)) {
            curl_setopt($ch, CURLOPT_REFERER, $this->lastUrl);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $fullResponse = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $this->lastResponse = substr($fullResponse, $headerSize);
        $info = curl_getinfo($ch);
        $this->lastUrl = $info['url'];
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
                    $GLOBALS['returnable'] .= "     [FAIL] Expected '$expectedContains' not found in response from {$this->lastUrl}.\n";
                    $snippet = trim(substr(strip_tags($response), 0, 200));
                    $GLOBALS['returnable'] .= "     [DEBUG] Snippet: ...$snippet...\n";
                    $success = false;
                }
            }
        }

        if (isset($step->expected->not_contains)) {
            foreach ($step->expected->not_contains as $expectedNotContains) {
                $expectedNotContains = (string)$expectedNotContains;
                if (strpos($response, $expectedNotContains) !== false) {
                    $GLOBALS['returnable'] .= "     [FAIL] Forbidden text '$expectedNotContains' found in response.\n";
                    $success = false;
                }
            }
        }

        return $success;
    }

    /**
     * Handle a Click action.
     *
     * Selector resolution (in priority order):
     *   1. 'logout'          → form[action*="logout"]
     *   2. '#some-id'        → querySelector('#some-id')
     *   3. Plain text        → querySelector('a, button') with matching text content (fallback)
     *
     * Once resolved, the target is submitted (form) or navigated (a).
     */
    protected function handleClick($step) {
        $selector = (string)$step->selector; 
        
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

        $dom = $this->buildDom();
        $targetNode = null;

        if ($selector === 'logout') {
            // Special case: find any form whose action contains 'logout'
            $targetNode = $dom->querySelector("form[action*='logout']");

        } elseif (strpos($selector, '#') === 0 || strpos($selector, '[') === 0 || strpos($selector, '.') === 0) {
            // Direct CSS selector
            $targetNode = $dom->querySelector($selector);

        } else {
            // Fallback: match button/input[type=submit] by value text, or <a> by text content
            // We scan all forms that contain a submit with matching value
            $allForms = $dom->querySelectorAll('form');
            foreach ($allForms as $form) {
                $btn = $form->querySelector("input[value='{$selector}'], button");
                if ($btn && trim($btn->getAttribute('value')) === $selector) {
                    $targetNode = $form;
                    break;
                }
            }
        }

        if (!$targetNode) {
            $GLOBALS['returnable'] .= "     [FAIL] Click target '$selector' not found.\n";
            file_put_contents('/tmp/last_failure.html', $response);
            return false;
        }

        return $this->activateNode($targetNode, $dom);
    }

    /**
     * Submit a form node or navigate an anchor node.
     */
    protected function activateNode(DOM\Element $targetNode, DOM\HTMLDocument $dom): bool {
        $nodeName = strtolower($targetNode->nodeName);

        if ($nodeName === 'form') {
            $actionUrl = $targetNode->getAttribute('action');
            if (strpos($actionUrl, 'http') !== 0) {
                $actionUrl = $this->baseUrl . ltrim($actionUrl, '/');
            }
            // Collect all hidden inputs from this form
            $postData = [];
            $hiddenInputs = $targetNode->querySelectorAll("input[type='hidden']");
            foreach ($hiddenInputs as $input) {
                $postData[$input->getAttribute('name')] = $input->getAttribute('value');
            }
            $ch = $this->prepare_curl($actionUrl, $this->cookieFile);
            if (!empty($this->lastUrl)) {
                curl_setopt($ch, CURLOPT_REFERER, $this->lastUrl);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            $fullResponse = curl_exec($ch);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $this->lastResponse = substr($fullResponse, $headerSize);
            $info = curl_getinfo($ch);
            $this->lastUrl = $info['url'];
            curl_close($ch);
            return true;

        } elseif ($nodeName === 'a') {
            return $this->handleNavigate($targetNode->getAttribute('href'));

        } else {
            // If we landed on a child element (e.g. input[type=submit]), walk up to the form
            $parent = $targetNode->parentNode;
            while ($parent && strtolower($parent->nodeName) !== 'form') {
                $parent = $parent->parentNode;
            }
            if ($parent) {
                return $this->activateNode($parent, $dom);
            }
            $GLOBALS['returnable'] .= "     [WARN] Click on {$targetNode->nodeName} — no form ancestor found.\n";
            return false;
        }
    }
}

$test_suite[] = new xmlDomTest();
