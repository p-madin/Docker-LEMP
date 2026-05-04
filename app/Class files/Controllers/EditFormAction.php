<?php
class EditFormAction implements ControllerInterface {
    public static string $path = '/editForm';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

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
            if($formStatus['tfReadOnly']) {
                header("Location: /form_management");
                exit;
            }
        }

        $authorId = $sessionController->getSystemUserId();

        // Handle pure delete request
        if (isset($request->post['action']) && $request->post['action'] === 'delete') {
            $pk = (int)($request->post['tfPK'] ?? 0);
            if ($pk > 0) {
                // Fetch current data for undo support
                $qb_data = new QueryBuilder($dialect);
                $formData = $qb_data->table('tblForm')->where('tfPK', '=', $pk)->getFetch($db);
                
                if ($formData) {
                    $eventStore->append('FormDeleted', $formData, $pk, $authorId);
                }
            }
            header("Location: /form_management");
            exit;
        }

        $cleanData = FormValidation::processAndValidate('editForm', $request->post, $formSchemas, $sessionController, function($clean) {
            $id = $clean['tfPK'] ?? 0;
            return "/edit_form" . ($id ? "?id=".$id : "");
        });

        $data = ['tfName' => $cleanData['tfName']];
        $pk = (int)$cleanData['tfPK'];

        if ($pk > 0) {
            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('tblForm')->where('tfPK', '=', $pk)->getFetch($db);
            $previousPayload = is_array($oldData) ? $oldData : null;
            $eventId = $eventStore->append('FormUpdated', array_merge(['tfPK' => $pk], $data), $pk, $authorId, $previousPayload);
            $redirectUrl = "/edit_form?id=" . $pk;
        } else {
            $eventId = $eventStore->append('FormCreated', $data, null, $authorId);
            $redirectUrl = "/form_management";
        }

        $eventStore->waitUntilProcessed($eventId);

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => $redirectUrl]);
            exit;
        }

        header("Location: " . $redirectUrl);
        exit;
    }

    public static function getEventHandlers(): array {
        return [
            'FormCreated' => function($payload, $db, $dialect) {
                unset($payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->insert($payload);
                $qb->doExecute($db, $sql);
                return (int)$db->lastInsertId();
            },
            'FormUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['tfPK'];
                unset($payload['tfPK'], $payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->where('tfPK', '=', $pk)->update($payload);
                $qb->doExecute($db, $sql);
            },
            'FormDeleted' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblForm')->where('tfPK', '=', (int)$payload['tfPK'])->delete();
                $qb->doExecute($db, $sql);
            },
        ];
    }
}
?>
