<?php
class BannedIpDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'ip_address', 'label' => 'IP Address'],
            ['key' => 'banned_at', 'label' => 'Banned At'],
            ['key' => 'reason', 'label' => 'Reason'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'button_form', 'actionConfig' => ['url' => '/unban_ip', 'params' => ['ip' => 'ip_address'], 'buttonLabel' => 'Unban']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('banned_ips')->select(['ip_address', 'banned_at', 'reason'])->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Banned IP Addresses";
    }
}
