<?php
class ChildServiceDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'csPK', 'label' => 'ID'],
            ['key' => 'csCreatedDate', 'label' => 'Created Date'],
            ['key' => 'csCreatedByFK', 'label' => 'Created By'],
            ['key' => 'csUpdatedDate', 'label' => 'Updated Date'],
            ['key' => 'csAdminFK', 'label' => 'Admin FK'],
            ['key' => 'csUptimeDate', 'label' => 'Uptime Date'],
            ['key' => 'csCheckDate', 'label' => 'Check Date'],
            ['key' => 'csName', 'label' => 'Name'],
            ['key' => 'csStatus', 'label' => 'Status', 'action' => 'discriminator_badge', 'actionConfig' => [
                'u' => 'Unknown', 'uColor' => 'black',
                'a' => 'Active', 'aColor' => 'green',
                'd' => 'Deleted', 'dColor' => 'red',
                'p' => 'Paused', 'pColor' => 'orange',
                'i' => 'Inactive', 'iColor' => 'gray'
            ]],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'link', 'config' => ['url' => 'https://' . $_SERVER['HTTP_HOST'] . '/', 'param' => 'csName', 'label' => 'View', 'target' => '_blank']],
                ['type' => 'button_form', 'config' => ['url' => '/childServiceAction', 'buttonLabel' => 'Sync', 'params' => ['action' => 'sync', 'csPK' => 'csPK']]],
                ['type' => 'button_form', 'config' => ['url' => '/childServiceAction', 'buttonLabel' => 'Start', 'params' => ['action' => 'start', 'csPK' => 'csPK']]],
                ['type' => 'button_form', 'config' => ['url' => '/childServiceAction', 'buttonLabel' => 'Stop', 'params' => ['action' => 'stop', 'csPK' => 'csPK']]],
                ['type' => 'button_form', 'config' => ['url' => '/childServiceAction', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'csPK' => 'csPK'], 'cssClasses' => ['delete']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('absChildServices')->select(['csPK','csCreatedDate','csCreatedByFK','csUpdatedDate','csAdminFK','csUptimeDate','csCheckDate','csName','csStatus'])->executeFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Child Services";
    }
}
