<?php
class FormManagementController implements ControllerInterface {
    public static string $path = '/form_management';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom;

        $qb = new QueryBuilder($dialect);
        $items = $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly', $qb->raw('COUNT(tcPK) AS columnCount')])
                    ->leftJoin('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
                    ->groupBy(['tfPK', 'tfName', 'tfReadOnly'])
                    ->orderBy('tfName', 'ASC')->getFetchAll($db);

        return View::render('management/forms', [
            'items' => $items
        ]);
    }
}
?>
