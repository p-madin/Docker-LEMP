<?php

include_once("Class files/config.php");

$sessionController->destroySession();

header("location:/");
exit;

?>