<?php
// app/Test/session_hyper_action.php
include_once(__DIR__ . "/../Class files/config.php");

header('Content-Type: application/json');

if (!$sessionController->isLoggedIn()) {
    $sessionController->seed();
}

$testKey = "hyper_stress_test";
$data = [
    "time" => (string)microtime(true),
    "rand" => (string)bin2hex(random_bytes(16)),
    "nested" => [
        "a" => (string)rand(1, 1000),
        "b" => ["x", "y", "z"]
    ]
];

try {
    $sessionController->setPrimary($testKey, $data);
    $retrieved = $sessionController->getPrimary($testKey);
    
    if (json_encode($data) === json_encode($retrieved)) {
        echo json_encode(["status" => "success", "session_id" => $_COOKIE['session'] ?? 'new']);
    } else {
        echo json_encode(["status" => "mismatch", "error" => "Retrieved data does not match sent data"]);
    }
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
