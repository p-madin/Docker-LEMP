<?php
class EditAccountController implements ControllerInterface {
    public static string $path = '/edit_account';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
        $hlink = new Hyperlink();
        $dom->fabricateChild($wrapper, "h1", [], "Edit Account");

        $id = (int)($request->get['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /account_management");
            exit;
        }

        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers')->select(['auPK', 'username', 'name', 'age', 'city', 'email', 'verified'])->where('auPK', '=', $id);
        $user = $qb->getFetch($db);

        if (!$user) {
            $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "User not found.");
            $dom->fabricateChild($wrapper, "a", ["href"=>"/account_management", "class"=>"button"], "Back to List");
            echo $dom->dom->saveHTML();
            return;
        }

        $dom->fabricateChild($wrapper, "h2", [], "Editing User: " . $user['username']);
        $form = new xmlForm("editUser", $dom, $wrapper);
        $form->prep("/editAccount", "POST");
        $form->formWrapper->setAttribute("id", "editUserFormComponent");
        $form->formWrapper->setAttribute("data-initial-validate", "true");
        $form->buildFromSchema('editUser', $formSchemas, $user);

        // Add verification toggle for verified admins
        $form->addRow('verified_status', 'Verified:', 'checkbox', $user['verified']);
        $form->submitRow();

        // Add back link
        $dom->fabricateChild($wrapper, "hr");
        $wrapper_bottom = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Account List", "/account_management");

        echo $dom->dom->saveHTML();
    }
}
?>
