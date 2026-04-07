<?php
class EditFormAction implements ControllerInterface {
    public static string $path = '/editForm';
    public bool $isAction = true;
    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST') {
            $targetPk = 0;
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $targetPk = (int)($request->post['tfPK'] ?? 0);
            } elseif (isset($request->post['tfPK'])) {
                $targetPk = (int)($request->post['tfPK'] ?? 0);
            }
            
            if ($targetPk > 0) {
                $checkQb = new QueryBuilder($dialect);
                $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetPk);
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
                $pk = (int)($request->post['tfPK'] ?? 0);
                if ($pk > 0) {
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('tblForm')->where('tfPK', '=', $pk)->delete();
                    $stmt = $db->prepare($sql);
                    $qb->bindTo($stmt);
                    $stmt->execute();
                }
                header("Location: /form_management.php");
                exit;
            }

            $cleanData = FormValidation::processAndValidate('editForm', $request->post, $formSchemas, $sessionController, function($clean) {
                $id = $clean['tfPK'] ?? 0;
                return "/edit_form.php" . ($id ? "?id=".$id : "");
            });
            
            $data = [
                'tfName' => $cleanData['tfName']
            ];

            $pk = (int)$cleanData['tfPK'];

            if ($pk > 0) {
                // UPDATE
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->where('tfPK', '=', $pk)->update($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                $redirectUrl = "/edit_form.php?id=" . $pk;
            } else {
                // INSERT
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->insert($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                $redirectUrl = "/form_management.php";
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

$controllerList[EditFormAction::$path] = new EditFormAction();
?>
