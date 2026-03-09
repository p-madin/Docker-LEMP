<?php
if(!$sessionController->isLoggedIn()){
    header("Location: /index.php");
    exit;
}
?>
