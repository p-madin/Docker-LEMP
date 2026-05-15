<?php

class AccountManagementController implements ControllerInterface, DataProviderInterface {
    public static string $path = '/account_management';
    public bool $isAction = false;

    public function getColumns(): array {
        return [
            ['key' => 'auPK', 'label' => 'ID'],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'name', 'label' => 'Full Name'],
            ['key' => 'verified', 'label' => 'Status', 'action' => 'status_badge', 'actionConfig' => ['true' => 'Verified', 'false' => 'Pending']],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'button_form', 'actionConfig' => ['url' => '/edit_account?id=', 'param' => 'auPK', 'buttonLabel' => 'Edit']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('appUsers')->select(['auPK', 'username', 'name', 'verified'])->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "System Accounts";
    }

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;

        $users = $this->getData();

        return View::render('management/accounts', [
            'users' => $users
        ]);
    }
}
?>
