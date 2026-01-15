<?php

include_once("Class files/config.php");

$passwordHash = password_hash($_POST['password'], PASSWORD_DEFAULT);

$sql = "INSERT INTO appUsers(name, age, city, username, password, email)
        VALUES (:i_name, :i_age, :i_city, :i_username, :i_password, :i_email);";

$query = $db->prepare($sql);
$query->execute([
    ':i_name'     => $_POST['name'],
    ':i_age'      => $_POST['age'],
    ':i_city'     => $_POST['city'],
    ':i_username' => $_POST['username'],
    ':i_password' => $passwordHash,
    ':i_email'    => $_POST['email']
]);

header("location:/");

?>