<?php
class NavbarDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'nbPK', 'label' => 'ID'],
            ['key' => 'nbText', 'label' => 'Display Text'],
            ['key' => 'nbPath', 'label' => 'Path'],
            ['key' => 'nbOrder', 'label' => 'Order'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                ['type' => 'button_form', 'config' => ['url' => '/edit_navbar?id=', 'param' => 'nbPK', 'buttonLabel' => 'Edit', 'idPrefix' => 'edit-navbar-', 'idSuffixKey' => 'nbPK']],
                ['type' => 'button_form', 'config' => ['url' => '/editNavbar', 'buttonLabel' => 'Delete', 'params' => ['action' => 'delete', 'nbPK' => 'nbPK'], 'cssClasses' => ['delete'], 'idPrefix' => 'delete-navbar-', 'idSuffixKey' => 'nbPK']]
            ]]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $data = $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbPath', 'nbOrder', 'nbParentFK'])->orderBy('nbOrder', 'ASC')->executeFetchAll($db);
        $tree = [];
        $lookup = [];
        foreach ($data as $item) {
            $item['children'] = [];
            $lookup[$item['nbPK']] = $item;
        }

        foreach ($lookup as $id => &$item) {
            if ($item['nbParentFK'] && isset($lookup[$item['nbParentFK']])) {
                $lookup[$item['nbParentFK']]['children'][] = &$item;
            } else {
                $tree[] = &$item;
            }
        }
        return $tree;
    }

    public function getNestedKey(): ?string {
        return 'children';
    }

    public function getDataSourceName(): string {
        return "Navigation Menu Items";
    }
}
