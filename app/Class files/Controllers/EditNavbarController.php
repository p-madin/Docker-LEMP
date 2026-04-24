<?php
class EditNavbarController implements ControllerInterface {
    public static string $path = '/edit_navbar';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $id = isset($request->get['id']) ? (int)$request->get['id'] : 0;

        $u = [];
        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder', 'nbParentFK'])->where('nbPK', '=', $id);
            $u = $qb->getFetch($db);
        } else {
            $u = [
                'nbPK' => '',
                'nbText' => '',
                'nbDiscriminator' => 'p',
                'nbPath' => '/',
                'nbProtected' => 0,
                'nbOrder' => 1,
                'nbParentFK' => ''
            ];
        }

        return View::render('management/edit_navbar', [
            'id' => $id,
            'u' => $u,
            'formSchemas' => $formSchemas
        ]);
    }
}
?>
