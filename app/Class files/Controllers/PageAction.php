<?php
class PageAction implements ControllerInterface {
    public static string $path = '/pageAction';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;
        
        if($request->getMethod() === 'POST') {
            $authorId = $sessionController->getSystemUserId();

            // Handle delete request
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['pagPK'] ?? 0);
                if ($pk > 0) {
                    $qb_data = new QueryBuilder($dialect);
                    $pageData = $qb_data->table('tblPages')->where('pagPK', '=', $pk)->getFetch($db);
                    if ($pageData) {
                        $eventId = $eventStore->append('PageDeleted', $pageData, $pk, $authorId);
                        $eventStore->waitUntilProcessed($eventId);
                    }
                }
                if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                header("Location: /");
                exit;
            }

            $cleanData = FormValidation::processAndValidate('page', $request->post, $formSchemas, $sessionController, function($clean) {
                return "/";
            });

            $data = [
                'pagTitle' => $cleanData['pagTitle'],
                'pagSlug'  => $cleanData['pagSlug'] ?? '',
                'pagAuthorFK' => $authorId
            ];

            $pk = (int)($cleanData['pagPK'] ?? 0);

            if ($pk > 0) {
                $qb_old = new QueryBuilder($dialect);
                $oldData = $qb_old->table('tblPages')->where('pagPK', '=', $pk)->getFetch($db);
                $eventId = $eventStore->append('PageUpdated', array_merge(['pagPK' => $pk], $data), $pk, $authorId, $oldData);
            } else {
                $eventId = $eventStore->append('PageCreated', $data, null, $authorId);
            }

            $eventStore->waitUntilProcessed($eventId);

            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['success' => true, 'eventId' => $eventId]);
                exit;
            }

            header("Location: /");
            exit;
        }
    }

    public static function getEventHandlers(): array {
        return [
            'PageCreated' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblPages')->insert($payload);
                $qb->doExecute($db, $sql);
                return (int)$db->lastInsertId();
            },
            'PageUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['pagPK'];
                unset($payload['pagPK'], $payload['original_event_id']);
                $payload['pagUpdated'] = date('Y-m-d H:i:s');
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblPages')->where('pagPK', '=', $pk)->update($payload);
                $qb->doExecute($db, $sql);
            },
            'PageDeleted' => function($payload, $db, $dialect) {
                $pk = (int)$payload['pagPK'];
                $qb = new QueryBuilder($dialect);
                // Soft delete
                $sql = $qb->table('tblPages')->where('pagPK', '=', $pk)->update(['pagDeleted' => date('Y-m-d H:i:s')]);
                $qb->doExecute($db, $sql);
            },
        ];
    }
}
?>
