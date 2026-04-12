<?php
class NavbarManagementController implements ControllerInterface {
    public static string $path = '/navbar_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom;

        $wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
        $dom->fabricateChild($wrapper, "h1", [], "Navbar Management");

        $hlink = new Hyperlink();
        $createLinkWrapper = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Navbar Item", "/edit_navbar");

        $qb = new QueryBuilder($dialect);
        $items = $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder'])
                    ->orderBy('nbOrder', 'ASC')->getFetchAll($db);

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
                $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row", "data-nb-path"=>$item['nbPath']]);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbPK']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbText']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbDiscriminator']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbPath']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbProtected'] ? 'Yes' : 'No');
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['nbOrder']);
                
                $actionsCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
                $editForm = $hlink->appendHyperlinkForm($dom, $actionsCell, "Edit", "/edit_navbar?id=" . $item['nbPK']);
                $editForm->setAttribute('id', 'edit-navbar-' . $item['nbPath']);
                $deleteForm = $hlink->appendHyperlinkForm($dom, $actionsCell, "Delete", "/editNavbar", 
                                            ['action' => 'delete', 'nbPK' => $item['nbPK']], ['delete']);
                $deleteForm->setAttribute('id', 'delete-navbar-' . $item['nbPath']);
            }
        } else {
            $dom->fabricateChild($wrapper, "p", [], "No navbar items found.");
        }

        echo $dom->dom->saveHTML();
    }
}
?>
