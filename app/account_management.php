<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
$dom->fabricateChild($wrapper, "h1", [], "Account Management");


// User List Section
$qb_list = new QueryBuilder($dialect);
$qb_list->table('appUsers')->select(['auPK', 'username', 'name', 'verified']);
$stmt_list = $db->prepare($qb_list->toSQL());
$qb_list->bindTo($stmt_list);
$stmt_list->execute();
$users = $stmt_list->fetchAll();
$table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);

// Header row
$header = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Username");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Name");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Verified");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell actions-cell"], "Actions");

foreach($users as $user){
    $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row"]);
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['username']);
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['name']);
    
    $statusText = $user['verified'] ? "Verified" : "Not yet verified";
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $statusText);
    
    $aCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
    $hyperlink = new Hyperlink();
    $hyperlink->appendHyperlinkForm($dom, $aCell, "Edit", "/edit_account.php?id={$user['auPK']}");
}


echo $dom->dom->saveHTML();
?>
