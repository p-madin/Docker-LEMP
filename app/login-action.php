<?php

include_once("Class files/config.php");

if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $sessionController->destroySession();
    header("location:/?error=csrf");
    exit;
}


$cleanData = FormValidation::processAndValidate('login', $_POST, $formSchemas, $sessionController, '/index.php');
$sanitizedUsername = $cleanData['username'] ?? '';


$sql = "SELECT auPK, password, verified FROM appUsers WHERE username = :i_username;";

$query = $db->prepare($sql);
$query->execute([':i_username' => $sanitizedUsername]);

$user = $query->fetch();

if($user && password_verify($_POST['password'], $user['password'])){
    if($user['verified'] == 0){
        header("location:/?error=unverified");
        exit;
    }
    $sessionController->setPrimary('username', $sanitizedUsername);
    $sessionController->setPrimary('userID', (int)$user['auPK']);
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        echo json_encode(['redirect' => '/']);
        exit;
    }
    header("location:/");
    exit;
}else{
    if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
        echo json_encode(['redirect' => '/']);
        exit;
    }
    header("location:/");
    exit;
}

?>