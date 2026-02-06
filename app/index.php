<?php

include_once("Class files/config.php");

include_once("Class files/xmlDom.php");
include_once("Class files/xmlForm.php");

$query = $db->prepare("SELECT name FROM appUsers");

$query->execute();
$data = $query->fetchAll();

$dom = new xmlDom();
$dom->decorate_javascript();
$dom->decorate_cascade();

$wrapper = $dom->appendChild(parent : $dom->body, tagName : "div");

$heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "User list");
$unordered_list = $dom->appendChild(parent : $wrapper, tagName : "ul");

foreach($data as $key=>$value){
    $list_item = $dom->appendChild(parent : $unordered_list, tagName : "li", innerContent : $value['name']);

}

$graphQuery = $db->query("SELECT year(haDate) y, month(haDate) m, day(haDate) d, hour(haDate) h, count(*) c 
                          FROM httpAction GROUP BY y, m, d, h");
$graphData = [];
foreach($graphQuery as $row){
    $dt = (new DateTime())->setDate($row['y'], $row['m'], $row['d'])->setTime($row['h'], 0);
    $graphData[] = [
        'x' => $dt->format('Y-m-d H:i:s'),
        'y' => $row['c']
    ];
}
$graph = new DataGraph($graphData);
$graph_details = $dom->appendChild(parent: $wrapper, tagName: "details");
$heading = $dom->appendChild(parent : $graph_details, tagName : "summary", innerContent : "Visits per hour");
$graph->render($dom, $graph_details);

$heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "Login form");

$login_form = new xmlForm("login", $dom, $wrapper);
$login_form->prep("login-action.php", "POST");

if(!is_null($sessionController->getPrimary('userID'))){
    $heading = $dom->appendChild(parent : $login_form->formWrapper, tagName : "p", innerContent : "You are already signed in");
    $heading = $dom->appendChild(parent : $login_form->formWrapper, tagName : "a", innerContent : "Click here to logout", attributes: ["href"=>"logout-action.php"]);
}else{
    $login_form->addRow('username', 'Username:', 'text');
    $login_form->addRow('password', 'Password:', 'password');
    $login_form->submitRow();
}

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