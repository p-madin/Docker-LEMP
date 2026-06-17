<?php
class PageDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'pagPK', 'label' => 'ID'],
            ['key' => 'pagTitle', 'label' => 'Title'],
            ['key' => 'nbPath', 'label' => 'Primary URL'],
            ['key' => 'pagCreated', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/page_editor?id=', 'param' => 'pagPK', 'buttonLabel' => 'Edit']],
                ['type' => 'button_form', 'config' => ['url' => '/preview?id=', 'param' => 'pagPK', 'buttonLabel' => 'View']],
                ['type' => 'button_form', 'config' => ['url' => '/pageAction', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'pagPK' => 'pagPK'], 'cssClasses' => ['delete']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db;
        $sql = "SELECT p.pagPK, p.pagTitle, p.pagCreated, 
                       (SELECT nbPath FROM tblNavBar WHERE nbPageFK = p.pagPK ORDER BY nbPK ASC LIMIT 1) as nbPath
                FROM tblPages p WHERE p.pagDeleted IS NULL ORDER BY p.pagPK ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Content Pages";
    }
}
