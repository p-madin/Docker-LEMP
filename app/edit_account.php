<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
$dom->fabricateChild($wrapper, "h1", [], "Edit Account");

// Get current user verification status
$myID = (int)$sessionController->getPrimary('userID');
$qb = new QueryBuilder($dialect);
$sql = $qb->table('appUsers')->select(['verified'])->where('auPK', '=', $myID)->toSQL();
$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();
$me = $stmt->fetch();
$iAmVerified = $me['verified'] ?? 0;

// Edit Form Section
if(isset($_GET['id'])){
    $id = (int)$_GET['id'];
    $qb = new QueryBuilder($dialect);
    $qb->table('appUsers')->select(['auPK', 'username', 'name', 'age', 'city', 'email', 'verified'])->where('auPK', '=', $id);
    $sql = $qb->toSQL();
    $stmt = $db->prepare($sql);
    $qb->bindTo($stmt);
    $stmt->execute();
    $u = $stmt->fetch();

    if($u){
        $dom->fabricateChild($wrapper, "h2", [], "Editing User: " . $u['username']);
        $form = new xmlForm("editUser", $dom, $wrapper);
        $form->prep("update_account_action.php", "POST");
        $form->formWrapper->setAttribute("id", "editUserFormComponent");
        $form->formWrapper->setAttribute("data-initial-validate", "true");
        $form->buildFromSchema('editUser', $formSchemas, $u);
        
        // Add verification toggle for verified admins
        if($iAmVerified){
            $form->addRow('verified_status', 'Verified:', 'checkbox', $u['verified']);
        }
        
        $form->submitRow();
        
        // Add back link
        $dom->fabricateChild($wrapper, "hr");
        $dom->fabricateChild($wrapper, "a", ["href"=>"account_management.php", "class"=>"button"], "Back to List");
    } else {
        $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "User not found.");
        $dom->fabricateChild($wrapper, "a", ["href"=>"account_management.php", "class"=>"button"], "Back to List");
    }
} else {
    header("Location: account_management.php");
    exit;
}

echo $dom->dom->saveHTML();
?>
