<?php

include_once("Class files/config.php");
include_once("Class files/extranet.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $sessionController->destroySession();
        header("location:/?error=csrf");
        exit;
    }
} else {
    // Prevent direct GET access to logout if strictly following PRG
    header("Location: /");
    exit;
}

$sessionController->destroySession();

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    echo json_encode(['redirect' => '/']);
    exit;
}

header("location:/");
exit;

?>