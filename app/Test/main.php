<?php

require_once __DIR__ . '/TestBase.php';

$test_suite = [];

// Include all tests in the "Test Suite" directory
foreach (glob(__DIR__ . "/Test Suite/*.php") as $filename) {
    include_once($filename);
}

$total = count($test_suite);
$passed = 0;
$failed = 0;
$returnable = "";

foreach ($test_suite as $test) {
    if ($test->test()) {
        $passed++;
    } else {
        $failed++;
    }
}

echo $returnable;

echo "\n===============================\n";
echo "Test Results:\n";
echo "Total:  $total\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    exit(1);
} else {
    exit(0);
}
