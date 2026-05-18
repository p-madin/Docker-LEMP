<?php
class PageDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'pagPK', 'label' => 'ID'],
            ['key' => 'pagTitle', 'label' => 'Title'],
            ['key' => 'pagSlug', 'label' => 'Slug'],
            ['key' => 'pagCreated', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/page_editor?id=', 'param' => 'pagPK', 'buttonLabel' => 'Edit']],
                ['type' => 'button_form', 'config' => ['url' => '/preview?id=', 'param' => 'pagPK', 'buttonLabel' => 'View']],
                ['type' => 'button_form', 'config' => ['url' => '/pageAction', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'pagPK' => 'pagPK'], 'cssClasses' => ['delete']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('tblPages')->select(['pagPK', 'pagTitle', 'pagSlug', 'pagCreated'])->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Content Pages";
    }
}
