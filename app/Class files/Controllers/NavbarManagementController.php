<?php
class NavbarManagementController implements ControllerInterface, DataProviderInterface {
    public static string $path = '/navbar_management';
    public bool $isAction = false;

    public function getColumns(): array {
        return [
            ['key' => 'nbPK', 'label' => 'ID'],
            ['key' => 'nbText', 'label' => 'Label'],
            ['key' => 'nbPath', 'label' => 'Path'],
            ['key' => 'nbProtected', 'label' => 'Protected', 'action' => 'status_badge', 'actionConfig' => ['true' => 'Yes', 'false' => 'No']],
            ['key' => 'nbOrder', 'label' => 'Order'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'button_form', 'actionConfig' => ['url' => '/edit_navbar?id=', 'param' => 'nbPK', 'buttonLabel' => 'Edit']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $all_items = $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder', 'nbParentFK'])
                    ->orderBy('nbOrder', 'ASC')->getFetchAll($db);

        // Organize into tree
        $tree = [];
        $lookup = [];
        foreach ($all_items as $item) {
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
        return "Navigation Menu";
    }

    public function execute(Request $request) {
        $items = $this->getData();

        return View::render('management/navbar', [
            'items' => $items
        ]);
    }
}
?>
