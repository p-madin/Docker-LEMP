<?php
include_once("Class files/config.php");

// Dispatch Request
try {
    $router->dispatch($request);
} catch (Exception $e) {
    http_response_code(500);
    echo "Internal Server Error";
    error_log($e->getMessage());
    trigger_error($e->getMessage().$e->getTraceAsString(), E_USER_ERROR);
}
?>
