<?php
class PlatformRecoveryDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'id', 'label' => 'Event ID'],
            ['key' => 'event_type', 'label' => 'Type'],
            ['key' => 'aggregate_id', 'label' => 'Target ID'],
            ['key' => 'user_id', 'label' => 'User'],
            ['key' => 'created_at', 'label' => 'Timestamp']
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $data = $qb->table('event_store')->select(['id', 'event_type', 'aggregate_id', 'user_id', 'created_at'])->orderBy('id', 'DESC')->limit(100)->getFetchAll($db);
        
        foreach ($data as &$row) {
            $row['user_id'] = $row['user_id'] ?? 'System';
            $row['aggregate_id'] = $row['aggregate_id'] ?? 'N/A';
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
