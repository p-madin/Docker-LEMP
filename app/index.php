<?php

include_once("Class files/config.php");

$query = $db->prepare("SELECT name FROM appUsers");

$query->execute();
$data = $query->fetchAll();

$wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div");

$heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "User list");
$unordered_list = $dom->fabricateChild(parent : $wrapper, tagName : "ul");

foreach($data as $key=>$value){
    $list_item = $dom->fabricateChild(parent : $unordered_list, tagName : "li", innerContent : $value['name']);

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
#$graph_details = $dom->fabricateChild(parent: $wrapper, tagName: "details", attributes: ["open"=>"open"]);
$graph_details = $dom->fabricateChild(parent: $wrapper, tagName: "details");
$heading = $dom->fabricateChild(parent : $graph_details, tagName : "summary", innerContent : "Visits per hour");
$graph->render($dom, $graph_details);

$heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "Login form");

if(!is_null($sessionController->getPrimary('userID'))){
    $heading = $dom->fabricateChild(parent : $wrapper, tagName : "p", innerContent : "You are already signed in");
    
    $logout_container = $dom->fabricateChild($wrapper, "div", ["style" => "margin-top: 10px;"]);
    $hyperlink = new Hyperlink();
    $hyperlink->appendHyperlinkForm($dom, $logout_container, "Click here to logout", "logout-action.php");
}else{
    $login_form = new xmlForm("login", $dom, $wrapper);
    $login_form->prep("login-action.php", "POST");
    $login_form->buildFromSchema('login', $formSchemas);
    $login_form->submitRow();
}

$heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "Register form");

$register_form = new xmlForm("register", $dom, $wrapper);
$register_form->prep("register-action.php", "POST");
$register_form->buildFromSchema('register', $formSchemas);
$register_form->submitRow();

echo $dom->dom->c14n();

?>
