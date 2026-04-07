<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
$hlink = new Hyperlink();
$dom->fabricateChild($wrapper, "h1", [], "Edit Account");

// Edit Form Section
if(!isset($_GET['id'])){
    header("Location: account_management.php");
    exit;
}

$id = (int)$_GET['id'];
$qb = new QueryBuilder($dialect);
$qb->table('appUsers')->select(['auPK', 'username', 'name', 'age', 'city', 'email', 'verified'])->where('auPK', '=', $id);
$user = $qb->getFetch($db);

if(!$user){
    $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "User not found.");
    $dom->fabricateChild($wrapper, "a", ["href"=>"account_management.php", "class"=>"button"], "Back to List");
    echo $dom->dom->saveHTML();
    exit;
}

$dom->fabricateChild($wrapper, "h2", [], "Editing User: " . $user['username']);
$form = new xmlForm("editUser", $dom, $wrapper);
$form->prep(UpdateAccountAction::$path, "POST");
$form->formWrapper->setAttribute("id", "editUserFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editUser', $formSchemas, $user);

// Add verification toggle for verified admins
$form->addRow('verified_status', 'Verified:', 'checkbox', $user['verified']);
$form->submitRow();

// Add back link
$dom->fabricateChild($wrapper, "hr");
$wrapper_bottom = $dom->fabricateChild($wrapper, "div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Account List", "/account_management.php");

echo $dom->dom->saveHTML();
?>
