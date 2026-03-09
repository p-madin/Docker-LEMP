<?php

include_once("Class files/config.php");

if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $sessionController->destroySession();
    header("location:/?error=csrf");
    exit;
}


$sql = "SELECT auPK, password, verified FROM appUsers WHERE username = :i_username;";

$query = $db->prepare($sql);
$query->execute([':i_username' => $_POST['username']]);

$user = $query->fetch();

if($user && password_verify($_POST['password'], $user['password'])){
    if($user['verified'] == 0){
        header("location:/?error=unverified");
        exit;
    }
    header("location:/");
    $sessionController->setPrimary('username', $_POST['username']);
    $sessionController->setPrimary('userID', (int)$user['auPK']);
    exit;
}else{
    header("location:/");
    exit;
}

?>