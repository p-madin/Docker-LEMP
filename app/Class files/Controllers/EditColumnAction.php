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
                $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetFormPk);
                $checkStmt = $db->prepare($checkQb->toSQL());
                $checkQb->bindTo($checkStmt);
                $checkStmt->execute();
                $formStatus = $checkStmt->fetch(PDO::FETCH_ASSOC);
                
                $isReadOnly = false;
                if ($formStatus) {
                    $keys = ['tfReadOnly', 'tfreadonly', 'TFREADONLY'];
                    foreach ($keys as $k) {
                        if (array_key_exists($k, $formStatus)) {
                            if ((int)$formStatus[$k] === 1) $isReadOnly = true;
                            break;
                        }
                    }
                }
                
                if ($isReadOnly) {
                    header("Location: /form_management.php");
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
                    $stmt = $db->prepare($sql);
                    $qb->bindTo($stmt);
                    $stmt->execute();
                }
                header("Location: /edit_form.php?id=" . $form_id);
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

            if ($pk > 0) {
                // UPDATE
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblColumns')->where('tcPK', '=', $pk)->update($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                $redirectUrl = "/edit_form.php?id=" . $form_fk;
            } else {
                // INSERT
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblColumns')->insert($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                $redirectUrl = "/edit_form.php?id=" . $form_fk;
            }

            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /form_management.php");
        exit;
    }
}
$controllerList[EditColumnAction::$path] = new EditColumnAction();
?>
