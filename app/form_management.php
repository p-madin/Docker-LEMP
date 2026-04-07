<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
$dom->fabricateChild($wrapper, "h1", [], "Form Management");

$hlink = new Hyperlink();
$createLinkWrapper = $dom->fabricateChild($wrapper, "div");
$hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Form", "/edit_form.php");

$qb = new QueryBuilder($dialect);
$sql = $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly', $qb->raw('COUNT(tcPK) AS columnCount')])
           ->join('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
           ->groupBy(['tfPK', 'tfName', 'tfReadOnly'])
           ->orderBy('tfName', 'ASC')->toSQL();
$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) > 0) {
    $table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);
    
    // Header
    $headerRow = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "ID");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Form Name");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Form Size");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell actions-cell"], "Actions");

    foreach ($items as $item) {
        $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row"]);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['tfPK']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['tfName']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['columnCount']);
        
        $actionsCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
        $hlink->appendHyperlinkForm($dom, $actionsCell, "Edit", "/edit_form.php?id=" . $item['tfPK']);
        
        $readOnly = (int)($item['tfReadOnly']);
        
        // Delete form
        $delStyle = ['delete'];
        if ($readOnly === 1) {
            $delStyle[] = 'disabled';
        }

        $hlink->appendHyperlinkForm($dom, $actionsCell, "Delete", "/edit_form_action.php", ['action' => 'delete', 'tfPK' => $item['tfPK']], $delStyle);
    }
} else {
    $dom->fabricateChild($wrapper, "p", [], "No form definitions found.");
}

echo $dom->dom->saveHTML();
?>
