<?php

class SessionTest extends TestSuiteBase {
    public function __construct() {
        parent::__construct("Session Consistency Test");
    }

    public function test() {
        $GLOBALS['returnable'] .= "Running Session Consistency Test...\n";
        
        // We capture the output of the existing test_session_tree.php
        ob_start();
        include __DIR__ . '/../../test_session_tree.php';
        $output = ob_get_clean();
        
        if (strpos($output, "ALL TESTS PASSED") !== false) {
            $GLOBALS['returnable'] .= "[PASS] Session tree consistency verified.\n";
            return true;
        } else {
            $GLOBALS['returnable'] .= "[FAIL] Session tree consistency check failed.\n";
            $GLOBALS['returnable'] .= "Output:\n" . $output . "\n";
            return false;
        }
    }
}

$test_suite[] = new SessionTest();
