<?php
class EditNavbarController implements ControllerInterface {
    public static string $path = '/edit_navbar';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div", attributes: ["class"=>"container"]);
        $hlink = new Hyperlink();
        $id = isset($request->get['id']) ? (int)$request->get['id'] : 0;

        $u = [];
        if ($id > 0) {
            $dom->fabricateChild($wrapper, "h1", [], "Edit Navbar Item");
            $qb = new QueryBuilder($dialect);
            $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder'])->where('nbPK', '=', $id);
            $u = $qb->getFetch($db);
            if (!$u) {
                $dom->fabricateChild($wrapper, "p", ["style"=>"color:red;"], "Navbar item not found.");
                $dom->fabricateChild($wrapper, "a", ["href"=>"/navbar_management", "class"=>"button"], "Back to List");
                echo $dom->dom->saveHTML();
                return;
            }
        } else {
            $dom->fabricateChild($wrapper, "h1", [], "Create Navbar Item");
            $u = [
                'nbPK' => '',
                'nbText' => '',
                'nbDiscriminator' => 'p',
                'nbPath' => '/',
                'nbProtected' => 0,
                'nbOrder' => 1
            ];
        }

        $form = new xmlForm("navbar", $dom, $wrapper);
        $form->prep("/editNavbar", "POST");
        $form->formWrapper->setAttribute("id", "editNavbarFormComponent");
        $form->formWrapper->setAttribute("data-initial-validate", "true");
        $form->buildFromSchema('navbar', $formSchemas, $u);

        // Append boolean dynamic field
        $form->addRow('nbProtected', 'Protected (Requires Login):', 'checkbox', $u['nbProtected']);

        $form->submitRow();

        $dom->fabricateChild($wrapper, "hr");
        $wrapper_bottom = $dom->fabricateChild($wrapper, "div");
        $hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Navbar List", "/navbar_management");

        echo $dom->dom->saveHTML();
    }
}
?>
