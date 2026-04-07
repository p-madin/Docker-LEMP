<?php

class CSRFTest extends TestSuiteBase {
    public function __construct() {
        parent::__construct("CSRF Forgery Test");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Advanced CSRF Cross-Session Test...\n";
        
        $homeUrl = "https://localhost/index.php";
        $actionUrl = "https://localhost/login";
        
        $cookieA = "/tmp/cookie_user_a.txt";
        $cookieB = "/tmp/cookie_user_b.txt";
        
        if (file_exists($cookieA)) {
            unlink($cookieA);
        }
        if (file_exists($cookieB)) {
            unlink($cookieB);
        }
        
        // 1. User A: Get Session A and Token A
        $GLOBALS['returnable'] .= " - User A: Retrieving session and token...\n";
        $chA = $this->prepare_curl($homeUrl, $cookieA);
        $resA = curl_exec($chA);
        curl_close($chA);
        
        $docA = \Dom\HTMLDocument::createFromString($resA, LIBXML_NOERROR);
        $inputA = $docA->querySelector('input[name="csrf_token"]');

        if (!$inputA) {
            $GLOBALS['returnable'] .= "[FAIL] User A could not find CSRF token in home page via querySelector.\n";
            return false;
        }
        $tokenA = $inputA->getAttribute('value');
        
        // 2. User B: Get Session B and Token B
        $GLOBALS['returnable'] .= " - User B: Retrieving session and token...\n";
        $chB = $this->prepare_curl($homeUrl, $cookieB);
        $resB = curl_exec($chB);
        curl_close($chB);
        
        $docB = \Dom\HTMLDocument::createFromString($resB, LIBXML_NOERROR);
        $inputB = $docB->querySelector('input[name="csrf_token"]');

        if (!$inputB) {
            $GLOBALS['returnable'] .= "[FAIL] User B could not find CSRF token in home page via querySelector.\n";
            return false;
        }
        $tokenB = $inputB->getAttribute('value');
        
        if ($tokenA === $tokenB) {
            $GLOBALS['returnable'] .= "[FAIL] User A and User B received the same CSRF token!\n";
            return false;
        }

        // 3. Attack: User B attempts to POST with User A's token
        $GLOBALS['returnable'] .= " - Attack: User B submitting with User A's token...\n";
        $chAttack = $this->prepare_curl($actionUrl, $cookieB);
        curl_setopt($chAttack, CURLOPT_POST, true);
        curl_setopt($chAttack, CURLOPT_POSTFIELDS, http_build_query([
            'username' => 'attacker',
            'password' => 'password',
            'csrf_token' => $tokenA // WRONG TOKEN for this session
        ]));
        
        $resAttack = curl_exec($chAttack);
        $infoAttack = curl_getinfo($chAttack);
        curl_close($chAttack);
        // Expected: 403 Forbidden
        if ($infoAttack['http_code'] == 403) {
            $GLOBALS['returnable'] .= "[PASS] Cross-session CSRF attack was correctly rejected and session destroyed.\n";
            if (file_exists($cookieA)) unlink($cookieA);
            if (file_exists($cookieB)) unlink($cookieB);
            return true;
        } else {
            $GLOBALS['returnable'] .= "[FAIL] Cross-session CSRF attack was NOT rejected.\n";
            $GLOBALS['returnable'] .= "HTTP Code: " . $infoAttack['http_code'] . "\n";
            $GLOBALS['returnable'] .= "Redirect URL: " . ($infoAttack['redirect_url'] ?? 'None') . "\n";
            return false;
        }
    }
}

$test_suite[] = new CSRFTest();
