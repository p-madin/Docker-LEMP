<?php
class EditFormController implements ControllerInterface {
    public static string $path = '/edit_form';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $id = isset($request->get['id']) ? (int)$request->get['id'] : 0;
        $u = [];
        $cols = null;
        $readOnly = 0;

        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly'])->where('tfPK', '=', $id);
            $raw = $qb->getFetch($db);
            
            if ($raw) {
                $u['tfPK'] = $raw['tfPK'] ?? $raw['tfpk'] ?? $raw['TFPK'];
                $u['tfName'] = $raw['tfName'] ?? $raw['tfname'] ?? $raw['TFNAME'];
                $readOnly = (int)($raw['tfReadOnly'] ?? $raw['tfreadonly'] ?? $raw['TFREADONLY'] ?? 0);

                // If editing an existing form, fetch its columns
                $qb2 = new QueryBuilder($dialect);
                $cols = $qb2->table('tblColumns')->select(['tcPK', 'tcName', 'tcLabel', 'tcType', 'tcOrder'])->where('tcFormFK', '=', $id)->orderBy('tcOrder', 'ASC')->getFetchAll($db);
            }
        } else {
            $u = ['tfPK' => '', 'tfName' => ''];
        }

        return View::render('management/edit_form', [
            'id' => $id,
            'u' => $u,
            'cols' => $cols,
            'readOnly' => $readOnly,
            'formSchemas' => $formSchemas
        ]);
    }
}
?>
