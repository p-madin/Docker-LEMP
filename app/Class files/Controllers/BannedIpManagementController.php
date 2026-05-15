<?php

class BannedIpManagementController implements ControllerInterface, DataProviderInterface {
    public static string $path = '/banned_ips';
    public bool $isAction = false;

    public function getColumns(): array {
        return [
            ['key' => 'biPK', 'label' => 'ID'],
            ['key' => 'biIP', 'label' => 'IP Address'],
            ['key' => 'biReason', 'label' => 'Reason'],
            ['key' => 'biDateAdded', 'label' => 'Banned On'],
            ['key' => 'biExpires', 'label' => 'Expires'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'button_form', 'actionConfig' => ['url' => '/unban_ip?id=', 'param' => 'biPK', 'buttonLabel' => 'Unban', 'method' => 'POST']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('banned_ips')
                  ->select(['biPK', 'biIP', 'biReason', 'biExpires', 'biDateAdded'])
                  ->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Banned IP Management";
    }

    public function execute(Request $request) {
        $items = $this->getData();

        return View::render('management/banned_ips', [
            'items' => $items
        ]);
    }
}
?>