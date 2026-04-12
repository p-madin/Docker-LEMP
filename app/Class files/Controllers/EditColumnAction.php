<?php
class EditColumnAction implements ControllerInterface {
    public static string $path = '/editColumn';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST') {
            $targetFormPk = 0;
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $targetFormPk = (int)($request->post['form_id'] ?? 0);
            } elseif (isset($request->post['tcFormFK'])) {
                $targetFormPk = (int)($request->post['tcFormFK'] ?? 0);
            } elseif (isset($request->post['form_id'])) {
                $targetFormPk = (int)($request->post['form_id'] ?? 0);
            }
            
            if ($targetFormPk > 0) {
                $checkQb = new QueryBuilder($dialect);
                $formStatus = $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetFormPk)->getFetch($db);
                
                $isReadOnly = false;
                if($formStatus['tfReadOnly']){
                    $isReadOnly = true;
                }
                
                if ($isReadOnly) {
                    header("Location: /form_management");
                    exit;
                }
            }

            // Handle pure delete request
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['tcPK'] ?? 0);
                $form_id = (int)($request->post['form_id'] ?? 0);
                if ($pk > 0) {
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('tblColumns')->where('tcPK', '=', $pk)->delete();
                    $qb->doExecute($db, $sql);
                }
                header("Location: /edit_form?id=" . $form_id);
                exit;
            }

            $cleanData = FormValidation::processAndValidate('editColumn', $request->post, $formSchemas, $sessionController, function($clean) {
                $id = $clean['tcPK'] ?? 0;
                $form_id = $clean['tcFormFK'] ?? 0;
                return "/edit_column.php" . ($id ? "?id=".$id : "?form_id=".$form_id);
            });
            
            $data = [
                'tcFormFK' => $cleanData['tcFormFK'],
                'tcName' => $cleanData['tcName'],
                'tcLabel' => $cleanData['tcLabel'],
                'tcType' => $cleanData['tcType'],
                'tcRules' => $cleanData['tcRules'],
                'tcOrder' => $cleanData['tcOrder']
            ];
            
            $decoded = json_decode($data['tcRules'], true);
            if (!is_array($decoded)) {
                $data['tcRules'] = '{}';
            }

            $pk = (int)$cleanData['tcPK'];
            $form_fk = (int)$cleanData['tcFormFK'];
            $qb = new QueryBuilder($dialect);
            $redirectUrl = "/edit_form?id=" . $form_fk;

            if ($pk > 0) {
                // UPDATE
                $sql = $qb->table('tblColumns')->where('tcPK', '=', $pk)->update($data);
            } else {
                // INSERT
                $sql = $qb->table('tblColumns')->insert($data);
            }
            $qb->doExecute($db, $sql);

            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /form_management");
        exit;
    }
}
?>
