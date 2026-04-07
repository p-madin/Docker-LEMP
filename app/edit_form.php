<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
$hlink = new Hyperlink();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$u = [];

if ($id > 0) {
    $dom->fabricateChild($wrapper, "h1", [], "Edit Form Settings");
    $qb = new QueryBuilder($dialect);
    $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly'])->where('tfPK', '=', $id);
    $sql = $qb->toSQL();
    $stmt = $db->prepare($sql);
    $qb->bindTo($stmt);
    $stmt->execute();
    $raw = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$raw) {
        $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "Form not found.");
        $wrapper_back = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $wrapper_back, "Back to List", "/form_management.php");
        echo $dom->dom->saveHTML();
        exit;
    }
    
    $u['tfPK'] = $raw['tfPK'] ?? $raw['tfpk'] ?? $raw['TFPK'];
    $u['tfName'] = $raw['tfName'] ?? $raw['tfname'] ?? $raw['TFNAME'];
    $readOnly = (int)($raw['tfReadOnly'] ?? $raw['tfreadonly'] ?? $raw['TFREADONLY'] ?? 0);
} else {
    $dom->fabricateChild($wrapper, "h1", [], "Create New Form");
    $u = ['tfPK' => '', 'tfName' => ''];
    $readOnly = 0;
}

$form = new xmlForm("editForm", $dom, $wrapper);
$form->prep("editForm", "POST");
$form->formWrapper->setAttribute("id", "editFormFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editForm', $formSchemas, $u);

if ($readOnly === 1) {
    // Disable submission block safely
    $submitAttributes = ['disabled' => 'disabled', 'style' => 'pointer-events:none; background-color: #ccc; color:#666; cursor:not-allowed; padding:10px 15px; border:none; border-radius:4px; font-weight:bold; margin-top:20px;'];
    $form->addSubmit($form->formWrapper, 'sub_btn', $submitAttributes);
} else {
    $form->submitRow();
}

$dom->fabricateChild($wrapper, "hr");

// If editing an existing form, list its columns
if ($id > 0) {
    $dom->fabricateChild($wrapper, "h2", [], "Form Fields");
    $addStyle = "margin-bottom: 15px; display:inline-block;";
    if ($readOnly === 1) {
        $addStyle .= " pointer-events:none; background-color: #ccc; opacity:0.6;";
    }
    $wrapper_add = $dom->fabricateChild($wrapper, "div", ["style"=>$addStyle]);
    $hlink->appendHyperlinkForm($dom, $wrapper_add, "Add New Field", "/edit_column.php?form_id=" . $id);
    
    $qb2 = new QueryBuilder($dialect);
    $sql2 = $qb2->table('tblColumns')->select(['tcPK', 'tcName', 'tcLabel', 'tcType', 'tcOrder'])->where('tcFormFK', '=', $id)->orderBy('tcOrder', 'ASC')->toSQL();
    $stmt2 = $db->prepare($sql2);
    $qb2->bindTo($stmt2);
    $stmt2->execute();
    $cols = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cols) > 0) {
        $table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);
        $headerRow = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
        $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Order");
        $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Name");
        $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Label");
        $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Type");
        $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Actions");

        foreach ($cols as $c) {
            $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row"]);
            
            $cOrder = $c['tcOrder'];
            $cName = $c['tcName'];
            $cLabel = $c['tcLabel'];
            $cType = $c['tcType'];
            $cPK = $c['tcPK'];

            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $cOrder);
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $cName);
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $cLabel);
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $cType);
            
            $actionsCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
            
            $editStyle = ['edit'];
            $delStyle = ['delete'];
            
            if ($readOnly === 1) {
                $editStyle[] = 'disabled';
                $delStyle[] = 'disabled';
            }

            $hlink->appendHyperlinkForm($dom, $actionsCell, "Edit", "/edit_column.php?id=" . $cPK, [], $editStyle);
            $hlink->appendHyperlinkForm($dom, $actionsCell, "Delete", "/edit_column_action.php", 
                                        ['action' => 'delete', 'tcPK' => $cPK, 'form_id' => $id], $delStyle);
        }
    } else {
        $dom->fabricateChild($wrapper, "p", [], "No fields defined for this form yet.");
    }
}

$dom->fabricateChild($wrapper, "div");
$wrapper_bottom = $dom->fabricateChild($wrapper, "div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Forms List", "/form_management.php");

echo $dom->dom->saveHTML();
?>
