<?php

include_once("Class files/config.php");


$sql = "SELECT auPK, password FROM appUsers WHERE username = :i_username;";

$query = $db->prepare($sql);
$query->execute([':i_username' => $_POST['username']]);

$user = $query->fetch();

if($user && password_verify($_POST['password'], $user['password'])){
    header("location:/");
    $sessionController->setPrimary('username', $_POST['username']);
    $sessionController->setPrimary('userID', (int)$user['auPK']);
    exit;
}else{
    header("location:/");
    exit;
}

?>