<?php
class AccountManagementController implements ControllerInterface {
    public static string $path = '/account_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;

        // User List Section
        $qb_list = new QueryBuilder($dialect);
        $qb_list->table('appUsers')->select(['auPK', 'username', 'name', 'verified']);
        $users = $qb_list->getFetchAll($db);

        return View::render('management/accounts', [
            'users' => $users
        ]);
    }
}
?>
