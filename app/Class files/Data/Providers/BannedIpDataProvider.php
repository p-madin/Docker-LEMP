<?php
class BannedIpDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'biIP', 'label' => 'IP Address'],
            ['key' => 'biDateAdded', 'label' => 'Banned At'],
            ['key' => 'biReason', 'label' => 'Reason'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'button_form', 'actionConfig' => ['url' => '/unban_ip', 'params' => ['biPK' => 'biPK'], 'buttonLabel' => 'Unban']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('banned_ips')->select(['biPK', 'biIP', 'biDateAdded', 'biReason'])->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Banned IP Addresses";
    }
}
