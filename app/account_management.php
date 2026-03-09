<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->appendChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
$dom->appendChild($wrapper, "h1", [], "Account Management");

// Get current user verification status
$myID = (int)$sessionController->getPrimary('userID');
$stmt = $db->prepare("SELECT verified FROM appUsers WHERE auPK = :id");
$stmt->execute(['id'=>$myID]);
$me = $stmt->fetch();
$iAmVerified = $me['verified'] ?? 0;

// User List Section
$users = $db->query("SELECT auPK, username, name, verified FROM appUsers")->fetchAll();
$table = $dom->appendChild($wrapper, "div", ["class"=>"flex-table"]);

// Header row
$header = $dom->appendChild($table, "div", ["class"=>"flex-row", "style"=>"font-weight:bold; background:#eee;"]);
$dom->appendChild($header, "div", ["class"=>"flex-cell"], "Username");
$dom->appendChild($header, "div", ["class"=>"flex-cell"], "Name");
$dom->appendChild($header, "div", ["class"=>"flex-cell"], "Verified");
$dom->appendChild($header, "div", ["class"=>"flex-cell"], "Actions");

foreach($users as $user){
    $row = $dom->appendChild($table, "div", ["class"=>"flex-row"]);
    $dom->appendChild($row, "div", ["class"=>"flex-cell"], $user['username']);
    $dom->appendChild($row, "div", ["class"=>"flex-cell"], $user['name']);
    
    $statusText = $user['verified'] ? "Verified" : "Not yet verified";
    $dom->appendChild($row, "div", ["class"=>"flex-cell"], $statusText);
    
    $aCell = $dom->appendChild($row, "div", ["class"=>"flex-cell"]);
    $dom->appendChild($aCell, "a", ["href"=>"account_management.php?edit={$user['auPK']}"], "Edit");
}

// Edit Form Section
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM appUsers WHERE auPK = :id");
    $stmt->execute(['id'=>$id]);
    $u = $stmt->fetch();

    if($u){
        $dom->appendChild($wrapper, "h2", [], "Editing User: " . $u['username']);
        $form = new xmlForm("editUser", $dom, $wrapper);
        $form->prep("update_account_action.php", "POST");
        $form->addRow('auPK', '', 'hidden', $u['auPK']);
        $form->addRow('username', 'Username:', 'text', $u['username']);
        $form->addRow('name', 'Name:', 'text', $u['name']);
        $form->addRow('age', 'Age:', 'number', $u['age']);
        $form->addRow('city', 'City:', 'text', $u['city']);
        $form->addRow('email', 'Email:', 'email', $u['email']);
        
        // Add verification toggle for verified admins
        if($iAmVerified){
            $form->addRow('verified_status', 'Verified:', 'checkbox', $u['verified']);
        }
        
        $form->submitRow();
    }
}

echo $dom->dom->saveHTML();
?>
