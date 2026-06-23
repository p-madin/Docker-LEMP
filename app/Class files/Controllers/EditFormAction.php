<?php
class EditFormAction implements ControllerInterface {
    public static string $path = '/editForm';
    public static string $manage_URI = '/form_management';
    public static string $object_URI = '/edit_form';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

        $targetPk = 0;
        if (isset($request->post['action']) && $request->post['action'] === 'delete') {
            $targetPk = (int)($request->post['tfPK'] ?? 0);
        } elseif (isset($request->post['tfPK'])) {
            $targetPk = (int)($request->post['tfPK'] ?? 0);
        }

        if ($targetPk > 0) {
            $checkQb = new QueryBuilder($dialect);
            $formStatus = $checkQb->table('tblForm')->select(['tfReadOnly'])->where('tfPK', '=', $targetPk)->executeFetch($db);
            if($formStatus['tfReadOnly']) {
                Hyperlink::redirection(self::$manage_URI);
            }
        }

        $authorId = $sessionController->getSystemUserId();

        // Handle pure delete request
        if (isset($request->post['action']) && $request->post['action'] === 'delete') {
            $pk = (int)($request->post['tfPK'] ?? 0);
            if ($pk > 0) {
                // Fetch current data for undo support
                $qb_data = new QueryBuilder($dialect);
                $formData = $qb_data->table('tblForm')->where('tfPK', '=', $pk)->executeFetch($db);
                
                if ($formData) {
                    $eventId = $eventStore->append('FormDeleted', $formData, $pk, $authorId);
                    if ($eventId) {
                        $eventStore->waitUntilProcessed($eventId);
                    }
                }
            }
            Hyperlink::redirection(self::$manage_URI);
        }

        $cleanData = FormValidation::processAndValidate('editForm', $request->post, $sessionController, function($clean) {
            $id = $clean['tfPK'] ?? 0;
            return self::$object_URI . ($id ? "?id=".$id : "");
        });

        $data = ['tfName' => $cleanData['tfName']];
        $pk = (int)$cleanData['tfPK'];

        if ($pk > 0) {
            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('tblForm')->where('tfPK', '=', $pk)->executeFetch($db);
            $previousPayload = is_array($oldData) ? $oldData : null;
            $eventId = $eventStore->append('FormUpdated', array_merge(['tfPK' => $pk], $data), $pk, $authorId, $previousPayload);
        } else {
            $eventId = $eventStore->append('FormCreated', $data, null, $authorId);
        }

        $eventStore->waitUntilProcessed($eventId);
        $newId = $eventStore->getAggregateId($eventId);
        $dependency = $newId ? (int)$newId : $pk;

        Hyperlink::redirection(self::$object_URI . "?id=" . $dependency, $dependency);
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
