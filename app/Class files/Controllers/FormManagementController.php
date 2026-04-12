<?php
class FormManagementController implements ControllerInterface {
    public static string $path = '/form_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom;

        $wrapper = $dom->fabricateChild($dom->body, "div", ["class"=>"container"]);
        $dom->fabricateChild($wrapper, "h1", [], "Form Management");

        $hlink = new Hyperlink();
        $createLinkWrapper = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Form", "/edit_form");

        $qb = new QueryBuilder($dialect);
        $items = $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly', $qb->raw('COUNT(tcPK) AS columnCount')])
                    ->leftJoin('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
                    ->groupBy(['tfPK', 'tfName', 'tfReadOnly'])
                    ->orderBy('tfName', 'ASC')->getFetchAll($db);

        if (count($items) > 0) {
            $table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);
            
            // Header
            $headerRow = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
            $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "ID");
            $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Form Name");
            $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell"], "Form Size");
            $dom->fabricateChild($headerRow, "div", ["class"=>"flex-cell actions-cell"], "Actions");

            foreach ($items as $item) {
                $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row", "data-form-pk"=>$item['tfPK'], "data-form-name"=>$item['tfName']]);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['tfPK']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['tfName']);
                $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $item['columnCount']);
                
                $actionsCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
                $editForm = $hlink->appendHyperlinkForm($dom, $actionsCell, "Edit", "/edit_form?id=" . $item['tfPK']);
                $editForm->setAttribute('id', 'edit-form-' . $item['tfName']);
                
                $readOnly = (int)($item['tfReadOnly']);
                
                // Delete form
                $delStyle = ['delete'];
                if ($readOnly === 1) {
                    $delStyle[] = 'disabled';
                }

                $deleteForm = $hlink->appendHyperlinkForm($dom, $actionsCell, "Delete", "/editForm", ['action' => 'delete', 'tfPK' => $item['tfPK']], $delStyle);
                $deleteForm->setAttribute('id', 'delete-form-' . $item['tfName']);
            }
        } else {
            $dom->fabricateChild($wrapper, "p", [], "No form definitions found.");
        }

        echo $dom->dom->saveHTML();
    }
}
?>
