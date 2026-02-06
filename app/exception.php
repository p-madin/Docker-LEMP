<?php

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $momo = "";
    if (isset($data['error'])) {
        $momo = "Behavioural error: " . $data['message'] . " at " . $data['filename'] . " line " . $data['lineno'] . " column " . $data['colno'] . "\n";
    } else {
        $momo = "Resource error: " . $data['target'] . "\n";
    }
    
    $momo .= "Language: " . ($data['language'] ?? 'N/A') . "\n";
    $momo .= "Platform: " . ($data['platform'] ?? 'N/A') . "\n";
    $momo .= "Timezone: " . ($data['timezone'] ?? 'N/A') . "\n";
    $momo .= "User Agent: " . ($data['userAgent'] ?? 'N/A') . "\n";

    error_log("CLIENT ERROR:\n" . $momo);
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
}
