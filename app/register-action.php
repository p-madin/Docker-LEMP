<?php

include_once("Class files/config.php");

if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $sessionController->destroySession();
    header("location:/?error=csrf");
    exit;
}

$cleanData = FormValidation::processAndValidate('register', $_POST, $formSchemas, $sessionController, '/index.php');

$passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO appUsers(name, age, city, username, password, email, verified)
        VALUES (:i_name, :i_age, :i_city, :i_username, :i_password, :i_email, 0);";

$query = $db->prepare($sql);
$query->execute([
    ':i_name'     => $cleanData['name'],
    ':i_age'      => $cleanData['age'],
    ':i_city'     => $cleanData['city'],
    ':i_username' => $cleanData['username'],
    ':i_password' => $passwordHash,
    ':i_email'    => $cleanData['email']
]);

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    echo json_encode(['redirect' => '/']);
    exit;
}
header("location:/");

?>