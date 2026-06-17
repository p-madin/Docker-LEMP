<?php
class ArchivedPageDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'pagPK', 'label' => 'ID'],
            ['key' => 'pagTitle', 'label' => 'Title'],
            ['key' => 'pagDeleted', 'label' => 'Deleted At'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/pageAction', 'buttonLabel' => 'Restore', 'params' => ['action' => 'restore', 'pagPK' => 'pagPK']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db;
        $sql = "SELECT p.pagPK, p.pagTitle, p.pagDeleted
                FROM tblPages p WHERE p.pagDeleted IS NOT NULL ORDER BY p.pagDeleted DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Archived Pages";
    }
}
