<?php
class AccountManagementController implements ControllerInterface {
    public static string $path = '/account_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;

        $wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
        $dom->fabricateChild($wrapper, "h1", [], "Account Management");

        // User List Section
        $qb_list = new QueryBuilder($dialect);
        $qb_list->table('appUsers')->select(['auPK', 'username', 'name', 'verified']);
        $users = $qb_list->getFetchAll($db);
        $table = $dom->fabricateChild($wrapper, "div", ["class"=>"flex-table"]);

        // Header row
        $header = $dom->fabricateChild($table, "div", ["class"=>"flex-row flex-header"]);
        $dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Username");
        $dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Name");
        $dom->fabricateChild($header, "div", ["class"=>"flex-cell"], "Verified");
        $dom->fabricateChild($header, "div", ["class"=>"flex-cell actions-cell"], "Actions");

        foreach($users as $user){
            $row = $dom->fabricateChild($table, "div", ["class"=>"flex-row", "data-username"=>$user['username']]);
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['username']);
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $user['name']);
            
            $statusText = $user['verified'] ? "Verified" : "Not yet verified";
            $dom->fabricateChild($row, "div", ["class"=>"flex-cell"], $statusText);
            
            $aCell = $dom->fabricateChild($row, "div", ["class"=>"flex-cell actions-cell"]);
            $hyperlink = new Hyperlink();
            $editForm = $hyperlink->appendHyperlinkForm($dom, $aCell, "Edit", "/edit_account?id={$user['auPK']}");
            $editForm->setAttribute('id', 'edit-user-' . $user['username']);
        }

        echo $dom->dom->saveHTML();
    }
}
?>
