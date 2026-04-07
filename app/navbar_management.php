<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
$dom->fabricateChild($wrapper, "h1", [], "Navbar Management");

$hlink = new Hyperlink();
$createLinkWrapper = $dom->fabricateChild($wrapper, "div");
$hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Navbar Item", "/edit_navbar.php");

$qb = new QueryBuilder($dialect);
$sql = $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder'])->toSQL();
$sql .= " ORDER BY nbOrder ASC";
$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($items) > 0) {
    $table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);
    
    // Header
    $headerRow = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "ID");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Text");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Type (c/p)");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Path");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Protected");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Order");
    $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell actions-cell"], "Actions");

    foreach ($items as $item) {
        $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row"]);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbPK']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbText']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbDiscriminator']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbPath']);
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbProtected'] ? 'Yes' : 'No');
        $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbOrder']);
        
        $actionsCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
        $hlink->appendHyperlinkForm($dom, $actionsCell, "Edit", "/edit_navbar.php?id=" . $item['nbPK']);
        $hlink->appendHyperlinkForm($dom, $actionsCell, "Delete", "/edit_navbar_action.php", 
                                    ['action' => 'delete', 'nbPK' => $item['nbPK']], ['delete']);
    }
} else {
    $dom->fabricateChild($wrapper, "p", [], "No navbar items found.");
}

echo $dom->dom->saveHTML();
?>
