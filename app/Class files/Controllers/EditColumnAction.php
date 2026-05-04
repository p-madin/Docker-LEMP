<?php
class EditColumnAction implements ControllerInterface {
    public static string $path = '/editColumn';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

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
                if($formStatus['tfReadOnly']) {
                    header("Location: /form_management");
                    exit;
                }
            }

            $authorId = $sessionController->getSystemUserId();

            // Handle pure delete request
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['tcPK'] ?? 0);
                $form_id = (int)($request->post['form_id'] ?? 0);
                if ($pk > 0) {
                    // Fetch current data for undo support
                    $qb_data = new QueryBuilder($dialect);
                    $columnData = $qb_data->table('tblColumns')->where('tcPK', '=', $pk)->getFetch($db);
                    
                    if ($columnData) {
                        $eventStore->append('ColumnDeleted', $columnData, $targetFormPk, $authorId);
                    }
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
                'tcName'   => $cleanData['tcName'],
                'tcLabel'  => $cleanData['tcLabel'],
                'tcType'   => $cleanData['tcType'],
                'tcRules'  => $cleanData['tcRules'],
                'tcOrder'  => $cleanData['tcOrder']
            ];

            $decoded = json_decode($data['tcRules'], true);
            if (!is_array($decoded)) {
                $data['tcRules'] = '{}';
            }

            $pk = (int)$cleanData['tcPK'];
            $form_fk = (int)$cleanData['tcFormFK'];
            $redirectUrl = "/edit_form?id=" . $form_fk;

            if ($pk > 0) {
                // Fetch current state for Memento support
                $qb_old = new QueryBuilder($dialect);
                $oldData = $qb_old->table('tblColumns')->where('tcPK', '=', $pk)->getFetch($db);
                $previousPayload = is_array($oldData) ? $oldData : null;
                $eventId = $eventStore->append('ColumnUpdated', array_merge(['tcPK' => $pk], $data), $form_fk, $authorId, $previousPayload);
            } else {
                $eventId = $eventStore->append('ColumnCreated', $data, $form_fk, $authorId);
            }

            $eventStore->waitUntilProcessed($eventId);

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

    public static function getEventHandlers(): array {
        return [
            'ColumnCreated' => function($payload, $db, $dialect) {
                unset($payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblColumns')->insert($payload);
                $qb->doExecute($db, $sql);
                return (int)$db->lastInsertId();
            },
            'ColumnUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['tcPK'];
                unset($payload['tcPK'], $payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblColumns')->where('tcPK', '=', $pk)->update($payload);
                $qb->doExecute($db, $sql);
            },
            'ColumnDeleted' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblColumns')->where('tcPK', '=', (int)$payload['tcPK'])->delete();
                $qb->doExecute($db, $sql);
            },
        ];
    }
}
?>
