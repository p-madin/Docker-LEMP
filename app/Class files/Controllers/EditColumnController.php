<?php
class EditColumnController implements ControllerInterface {
    public static string $path = '/edit_column';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $formSchemas;

        $id = isset($request->get['id']) ? (int)$request->get['id'] : 0;
        $form_id = isset($request->get['form_id']) ? (int)$request->get['form_id'] : 0;
        $raw = null;

        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblColumns')->select(['tcPK', 'tcFormFK', 'tcName', 'tcLabel', 'tcType', 'tcRules', 'tcOrder'])->where('tcPK', '=', $id);
            $raw = $qb->getFetch($db);
            if ($raw) {
                $form_id = $raw['tcFormFK'];
            }
        } else {
            if ($form_id > 0) {
                $raw = [
                    'tcPK' => '', 
                    'tcFormFK' => $form_id, 
                    'tcName' => '', 
                    'tcLabel' => '', 
                    'tcType' => 'text', 
                    'tcRules' => '{}', 
                    'tcOrder' => 1
                ];
            }
        }

        // Check if form is read-only
        if ($form_id > 0) {
            $checkQb = new QueryBuilder($dialect);
            $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $form_id);
            $fraw = $checkQb->getFetch($db);
            if ($fraw && (int)($fraw['tfReadOnly']) === 1) {
                header("Location: /edit_form?id=" . $form_id);
                exit;
            }
        }

        return View::render('management/edit_column', [
            'id' => $id,
            'form_id' => $form_id,
            'raw' => $raw,
            'formSchemas' => $formSchemas
        ]);
    }
}
?>
