<?php
class EditAccountController implements ControllerInterface {
    public static string $path = '/edit_account';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $id = (int)($request->get['id'] ?? 0);
        if ($id <= 0) {
            header("Location: /account_management");
            exit;
        }

        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers')->select(['auPK', 'username', 'name', 'age', 'city', 'email', 'verified'])->where('auPK', '=', $id);
        $user = $qb->getFetch($db);

        return View::render('management/edit_account', [
            'user' => $user,
            'formSchemas' => $formSchemas
        ]);
    }
}
?>
