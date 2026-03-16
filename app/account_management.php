<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
$dom->fabricateChild($wrapper, "h1", [], "Account Management");

// Get current user verification status
$myID = (int)$sessionController->getPrimary('userID');
$stmt = $db->prepare("SELECT verified FROM appUsers WHERE auPK = :id");
$stmt->execute(['id'=>$myID]);
$me = $stmt->fetch();
$iAmVerified = $me['verified'] ?? 0;

// User List Section
$users = $db->query("SELECT auPK, username, name, verified FROM appUsers")->fetchAll();
$table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);

// Header row
$header = $dom->fabricateChild($table, "div", ["class"=>"flex-row", "style"=>"font-weight:bold; background:#eee;"]);
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Username");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Name");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Verified");
$dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Actions");

foreach($users as $user){
    $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row"]);
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['username']);
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['name']);
    
    $statusText = $user['verified'] ? "Verified" : "Not yet verified";
    $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $statusText);
    
    $aCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell"]);
    $dom->fabricateChild($aCell, "a", ["href"=>"account_management.php?edit={$user['auPK']}"], "Edit");
}

// Edit Form Section
if(isset($_GET['edit'])){
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM appUsers WHERE auPK = :id");
    $stmt->execute(['id'=>$id]);
    $u = $stmt->fetch();

    if($u){
        $dom->fabricateChild($wrapper, "hr");
        $dom->fabricateChild($wrapper, "h2", [], "Editing User: " . $u['username']);
        $form = new xmlForm("editUser", $dom, $wrapper);
        $form->prep("update_account_action.php", "POST");
        $form->buildFromSchema('editUser', $formSchemas, $u);
        
        // Add verification toggle for verified admins
        if($iAmVerified){
            $form->addRow('verified_status', 'Verified:', 'checkbox', $u['verified']);
        }
        
        $form->submitRow();
    }
}

echo $dom->dom->saveHTML();
?>
