<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
$hlink = new Hyperlink();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;
$u = [];

if ($id > 0) {
    $dom->fabricateChild($wrapper, "h1", [], "Edit Field");
    $qb = new QueryBuilder($dialect);
    $qb->table('tblColumns')->select(['tcPK', 'tcFormFK', 'tcName', 'tcLabel', 'tcType', 'tcRules', 'tcOrder'])->where('tcPK', '=', $id);
    $sql = $qb->toSQL();
    $stmt = $db->prepare($sql);
    $qb->bindTo($stmt);
    $stmt->execute();
    $raw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$raw) {
        $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "Field not found.");
        $wrapper_back = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $wrapper_back, "Back to Forms", "/form_management.php");
        echo $dom->dom->saveHTML();
        exit;
    }
    $form_id = $raw['tcFormFK'];
} else {
    if ($form_id === 0) {
         $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "Missing Form ID context.");
         echo $dom->dom->saveHTML();
         exit;
    }
    $dom->fabricateChild($wrapper, "h1", [], "Create New Field");
    $raw = [
        'tcPK' => '', 
        'tcFormFK' => $form_id, 
        'tcName' => '', 
        'tcLabel' => '', 
        'tcType' => 'text', 
        'tcRules' => '{}', 
        'tcOrder' => 1
    ];
}

if ($form_id > 0) {
    $checkQb = new QueryBuilder($dialect);
    $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $form_id);
    $checkStmt = $db->prepare($checkQb->toSQL());
    $checkQb->bindTo($checkStmt);
    $checkStmt->execute();
    $fraw = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ($fraw && (int)($fraw['tfReadOnly']) === 1) {
        header("Location: /edit_form.php?id=" . $form_id);
        exit;
    }
}

$form = new xmlForm("editColumn", $dom, $wrapper);
$form->prep("editColumn", "POST");
$form->formWrapper->setAttribute("id", "editColumnFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editColumn', $formSchemas, $raw);
$form->submitRow();

$dom->fabricateChild($wrapper, "div", ["style" => "margin-top: 20px;"], "");
$wrapper_bottom = $dom->fabricateChild($wrapper, "div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Form Fields", "/edit_form.php?id=" . $form_id);

echo $dom->dom->saveHTML();
?>
