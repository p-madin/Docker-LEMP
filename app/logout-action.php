<?php

include_once("Class files/config.php");
include_once("Class files/extranet.php");

Hyperlink::handleAction($sessionController);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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