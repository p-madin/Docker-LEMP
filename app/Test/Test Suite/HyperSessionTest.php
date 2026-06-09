<?php
// app/Test/Test Suite/HyperSessionTest.php

class HyperSessionTest extends TestSuiteBase {
    public function __construct() {
        parent::__construct("Hyper Session Stress Test");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Hyper Session Stress Test (Multi-Cookie)...\n";
        
        $url = "https://localhost/Test/session_hyper_action.php";
        $concurrency = 100; // Launch 100 concurrent requests with different cookies
        
        $mh = curl_multi_init();
        $handles = [];
        $cookieFiles = [];

        for ($i = 0; $i < $concurrency; $i++) {
            $cookieFile = "/tmp/hyper_cookie_$i.txt";
            if (file_exists($cookieFile)) {
                unlink($cookieFile);
            }
            $cookieFiles[] = $cookieFile;
            
            $ch = $this->prepare_curl($url, $cookieFile);
            // Ensure follow location is off and we don't need headers in the body for JSON parsing
            curl_setopt($ch, CURLOPT_HEADER, false); 
            
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        $successCount = 0;
        foreach ($handles as $ch) {
            $response = curl_multi_getcontent($ch);
            $info = curl_getinfo($ch);
            
            if ($info['http_code'] == 200) {
                $data = json_decode($response, true);
                if (isset($data['status']) && $data['status'] === 'success') {
                    $successCount++;
                } else {
                    $GLOBALS['returnable'] .= "[FAIL] Request failed with body: " . $response . "\n";
                }
            } else {
                $GLOBALS['returnable'] .= "[FAIL] Request failed with HTTP " . $info['http_code'] . "\n";
            }
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        // Cleanup cookies
        foreach ($cookieFiles as $cf) if (file_exists($cf)) unlink($cf);

        if ($successCount === $concurrency) {
            $GLOBALS['returnable'] .= "[PASS] Hyper Session Stress Test: all $concurrency concurrent clients succeeded.\n";
            return true;
        } else {
            $GLOBALS['returnable'] .= "[FAIL] Hyper Session Stress Test: only $successCount/$concurrency clients succeeded.\n";
            return false;
        }
    }
}

$test_suite[] = new HyperSessionTest();
