<?php
class NavbarManagementController implements ControllerInterface {
    public static string $path = '/navbar_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom;

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

        return View::render('management/navbar', [
            'items' => $tree
        ]);
    }
}
?>
