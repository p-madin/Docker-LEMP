<?php

include_once("Class files/db.php");
include_once("Class files/config.php");
include_once("Class files/xmlDom.php");
include_once("Class files/xmlForm.php");

$db_connect_controller = new db_connect_controller($dsn, $username, $password, $options);
$db = $db_connect_controller->connect();

$query = $db->prepare("SELECT name FROM appUsers");

$query->execute();
$data = $query->fetchAll();


$dom = new xmlDom();
$dom->decorate_cascade();

$wrapper = $dom->appendChild(parent : $dom->body, tagName : "div");

$heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "User list");
$unordered_list = $dom->appendChild(parent : $wrapper, tagName : "ul");

foreach($data as $key=>$value){
    $list_item = $dom->appendChild(parent : $unordered_list, tagName : "li", innerContent : $value['name']);

}

$heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "Login form");

$login_form = new xmlForm("login", $dom, $wrapper);
$login_form->prep("login-action.php", "POST");
$login_form->addRow('username', 'Username:', 'text');
$login_form->addRow('password', 'Password:', 'password');
$login_form->submitRow();


$heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "Register form");

$register_form = new xmlForm("register", $dom, $wrapper);
$register_form->prep("register-action.php", "POST");
$register_form->addRow('username', 'Username:', 'text');
$register_form->addRow('password', 'Password:', 'password');
$register_form->addRow('confirm_password', 'Confirm Password:', 'password');
$register_form->addRow('name', 'Name:', 'name');
$register_form->addRow('age', 'Age:', 'age');
$register_form->addRow('city', 'City:', 'city');
$register_form->addRow('email', 'Email:', 'email');
$register_form->submitRow();

echo $dom->dom->c14n();

?>