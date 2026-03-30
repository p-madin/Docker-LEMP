<?php

include_once("Class files/config.php");

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
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success']);
    try {
        throw new Exception("CLIENT ERROR:\n" . $momo);
    } catch (Exception $e) {
        error_log("CLIENT ERROR:\n" . $e->getMessage());
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
}
