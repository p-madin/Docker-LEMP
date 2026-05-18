<?php
class NavbarDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'nbPK', 'label' => 'ID'],
            ['key' => 'nbText', 'label' => 'Display Text'],
            ['key' => 'nbPath', 'label' => 'Path'],
            ['key' => 'nbOrder', 'label' => 'Order'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/edit_navbar?id=', 'param' => 'nbPK', 'buttonLabel' => 'Edit']],
                ['type' => 'button_form', 'config' => ['url' => '/editNavbar', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'nbPK' => 'nbPK'], 'cssClasses' => ['delete']]]
            ]]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbPath', 'nbOrder', 'nbParentFK'])->orderBy('nbOrder', 'ASC')->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return 'nbParentFK';
    }

    public function getDataSourceName(): string {
        return "Navigation Menu Items";
    }
}
