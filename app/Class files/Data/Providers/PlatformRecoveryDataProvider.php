<?php
class PlatformRecoveryDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'evsPK', 'label' => 'Event ID'],
            ['key' => 'evsEventType', 'label' => 'Type'],
            ['key' => 'evsAggregateFK', 'label' => 'Target ID'],
            ['key' => 'evsUserFK', 'label' => 'User'],
            ['key' => 'evsCreatedAt', 'label' => 'Timestamp']
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $data = $qb->table('tblEventStore')->select(['evsPK', 'evsEventType', 'evsAggregateFK', 'evsUserFK', 'evsCreatedAt'])->orderBy('evsPK', 'DESC')->limit(100)->executeFetchAll($db);
        
        foreach ($data as &$row) {
            $row['evsUserFK'] = $row['evsUserFK'] ?? 'System';
            $row['evsAggregateFK'] = $row['evsAggregateFK'] ?? 'N/A';
        }
        return $data;
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Platform Event Store";
    }
}
