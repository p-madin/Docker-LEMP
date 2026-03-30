<?php

include_once("Class files/config.php");

Hyperlink::handleAction($sessionController);

$cleanData = FormValidation::processAndValidate('register', $_POST, $formSchemas, $sessionController, '/index.php');

$passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

$qb = new QueryBuilder($dialect);
$qb->table('appUsers');
$sql = $qb->insert([
    'name'     => $cleanData['name'],
    'age'      => $cleanData['age'],
    'city'     => $cleanData['city'] ?? "",
    'username' => $cleanData['username'],
    'password' => $passwordHash,
    'email'    => $cleanData['email'] ?? "",
    'verified' => 0
]);

$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();

// Output the new ID for testing traceability
#$GLOBALS['returnable'] .= "Registered User ID: " . $db->lastInsertId() . "\n";

if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    echo json_encode(['redirect' => '/']);
    exit;
}
header("location:/");

?>