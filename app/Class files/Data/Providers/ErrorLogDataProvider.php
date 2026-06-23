<?php
class ErrorLogDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'pelPK', 'label' => 'ID'],
            ['key' => 'pelTimestamp', 'label' => 'Timestamp'],
            ['key' => 'pelSeverity', 'label' => 'Severity', 'action' => 'status_badge', 'actionConfig' => ['Error' => 'Error', 'Warning' => 'Warning', 'Notice' => 'Notice', 'ErrorColor' => 'red', 'WarningColor' => 'orange', 'NoticeColor' => 'blue']],
            ['key' => 'pelMessage', 'label' => 'Message'],
            ['key' => 'pelFile', 'label' => 'File'],
            ['key' => 'pelLine', 'label' => 'Line']
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('phpErrorLog')->select(['pelPK', 'pelTimestamp', 'pelSeverity', 'pelMessage', 'pelFile', 'pelLine'])->orderBy('pelPK', 'DESC')->limit(100)->executeFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "PHP Error Logs";
    }
}
