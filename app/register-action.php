<?php

include_once("Class files/db.php");
include_once("Class files/config.php");

$db_connect_controller = new db_connect_controller($dsn, $username, $password, $options);
$db = $db_connect_controller->connect();

$sql = "INSERT INTO appUsers(name, age, city, username, password, email)
        VALUES (:i_name, :i_age, :i_city, :i_username, :i_password, :i_email);";

$query = $db->prepare($sql);
$query->bindParam(":i_name", $_POST['name']);
$query->bindParam(":i_age", $_POST['age']);
$query->bindParam(":i_city", $_POST['city']);
$query->bindParam(":i_username", $_POST['username']);
$query->bindParam(":i_password", $_POST['password']);
$query->bindParam(":i_email", $_POST['email']);

$query->execute();

header("location:/");

?>