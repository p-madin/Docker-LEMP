<?php

include_once("Class files/config.php");

Hyperlink::handleAction($sessionController);


$cleanData = FormValidation::processAndValidate('login', $_POST, $formSchemas, $sessionController, '/index.php');
$sanitizedUsername = $cleanData['username'] ?? '';


$qb = new QueryBuilder($dialect);
$qb->table('appUsers')->select(['auPK', 'password', 'verified'])->where('username', '=', $sanitizedUsername);
$stmt = $db->prepare($qb->toSQL());
$qb->bindTo($stmt);
$stmt->execute();

$user = $stmt->fetch();

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