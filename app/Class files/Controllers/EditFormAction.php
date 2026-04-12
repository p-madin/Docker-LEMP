<?php
class EditFormAction implements ControllerInterface {
    public static string $path = '/editForm';
    public bool $isAction = true;
    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        $targetPk = 0;
        if (isset($request->post['action']) && $request->post['action'] === 'delete') {
            $targetPk = (int)($request->post['tfPK'] ?? 0);
        } elseif (isset($request->post['tfPK'])) {
            $targetPk = (int)($request->post['tfPK'] ?? 0);
        }
        
        if ($targetPk > 0) {
            $checkQb = new QueryBuilder($dialect);
            $formStatus = $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetPk)->getFetch($db);
            
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
            $pk = (int)($request->post['tfPK'] ?? 0);
            if ($pk > 0) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->where('tfPK', '=', $pk)->delete();
                $qb->doExecute($db, $sql);
            }
            header("Location: /form_management");
            exit;
        }

        $cleanData = FormValidation::processAndValidate('editForm', $request->post, $formSchemas, $sessionController, function($clean) {
            $id = $clean['tfPK'] ?? 0;
            return "/edit_form" . ($id ? "?id=".$id : "");
        });
        
        $data = [
            'tfName' => $cleanData['tfName']
        ];

        $pk = (int)$cleanData['tfPK'];
        $qb = new QueryBuilder($dialect);

        if ($pk > 0) {
            // UPDATE
            $sql = $qb->table('tblForm')->where('tfPK', '=', $pk)->update($data);
            $qb->doExecute($db, $sql);
        } else {
            // INSERT
            $sql = $qb->table('tblForm')->insert($data);
            $qb->doExecute($db, $sql);
            $pk = $db->lastInsertId();
        }
        $redirectUrl = "/edit_form?id=" . $pk;

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => $redirectUrl]);
            exit;
        }

        header("Location: " . $redirectUrl);
        exit;
    }
}
?>
