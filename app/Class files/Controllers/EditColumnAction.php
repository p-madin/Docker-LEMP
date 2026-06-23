<?php
class EditColumnAction implements ControllerInterface {
    public static string $path = '/editColumn';
    public static string $manage_URI = '/form_management';
    public static string $object_URI = '/edit_column';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

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
            $formStatus = $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetFormPk)->executeFetch($db);
            if($formStatus['tfReadOnly']) {
                Hyperlink::redirection(self::$manage_URI);
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
                $columnData = $qb_data->table('tblColumns')->where('tcPK', '=', $pk)->executeFetch($db);
                
                if ($columnData) {
                    $eventId = $eventStore->append('ColumnDeleted', $columnData, $targetFormPk, $authorId);
                    if ($eventId) {
                        $eventStore->waitUntilProcessed($eventId);
                    }
                }
            }
            Hyperlink::redirection("/edit_form?id=" . $form_id);
        }

        $cleanData = FormValidation::processAndValidate('editColumn', $request->post, $formSchemas, $sessionController, function($clean) {
            $id = $clean['tcPK'] ?? 0;
            $form_id = $clean['tcFormFK'] ?? 0;
            return self::$object_URI . ($id ? "?id=".$id : "?form_id=".$form_id);
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

        if ($pk > 0) {
            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('tblColumns')->where('tcPK', '=', $pk)->executeFetch($db);
            $previousPayload = is_array($oldData) ? $oldData : null;
            $eventId = $eventStore->append('ColumnUpdated', array_merge(['tcPK' => $pk], $data), $form_fk, $authorId, $previousPayload);
        } else {
            $eventId = $eventStore->append('ColumnCreated', $data, $form_fk, $authorId);
        }

        $eventStore->waitUntilProcessed($eventId);
        $newId = $eventStore->getAggregateId($eventId);
        $dependency = $newId ? (int)$newId : $pk;

        Hyperlink::redirection(self::$object_URI . "?id=" . $dependency, $dependency);
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
