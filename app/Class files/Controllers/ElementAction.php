<?php
class ElementAction implements ControllerInterface {
    public static string $path = '/elementAction';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

        if($request->getMethod() === 'POST') {
            $authorId = $sessionController->getSystemUserId();

            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['elePK'] ?? 0);
                if ($pk > 0) {
                    $qb_data = new QueryBuilder($dialect);
                    $elementData = $qb_data->table('tblElements')->where('elePK', '=', $pk)->getFetch($db);
                    if ($elementData) {
                        $eventId = $eventStore->append('ElementDeleted', $elementData, null, $authorId);
                        $eventStore->waitUntilProcessed($eventId);
                    }
                }
                if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                    echo json_encode(['success' => true]);
                    exit;
                }
                exit;
            }

            $cleanData = FormValidation::processAndValidate('element', $request->post, $formSchemas, $sessionController, function($clean) {
                return "/";
            });

            $data = [
                'eleType'       => $cleanData['eleType'],
                'eleContent'    => $cleanData['eleContent'],
                'eleCSSClasses' => $cleanData['eleCSSClasses'],
                'eleParentFK'   => !empty($cleanData['eleParentFK']) ? (int)$cleanData['eleParentFK'] : null
            ];

            $pk = (int)($cleanData['elePK'] ?? 0);
            $pageId = (int)($request->post['pageId'] ?? 0);
            $pelOrder = (int)($request->post['pelOrder'] ?? 0);

            if ($pk > 0) {
                $qb_old = new QueryBuilder($dialect);
                $oldData = $qb_old->table('tblElements')->where('elePK', '=', $pk)->getFetch($db);
                $eventId = $eventStore->append('ElementUpdated', array_merge(['elePK' => $pk, 'pageId' => $pageId, 'pelOrder' => $pelOrder], $data), null, $authorId, $oldData);
            } else {
                $eventId = $eventStore->append('ElementCreated', array_merge($data, ['pageId' => $pageId, 'pelOrder' => $pelOrder]), null, $authorId);
            }

            $eventStore->waitUntilProcessed($eventId);

            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['success' => true, 'eventId' => $eventId, 'elePK' => $pk ?: $eventStore->getAggregateId($eventId)]);
                exit;
            }

            header("Location: /");
            exit;
        }
    }

    public static function getEventHandlers(): array {
        return [
            'ElementCreated' => function($payload, $db, $dialect) {
                $pageId = (int)($payload['pageId'] ?? 0);
                $pelOrder = (int)($payload['pelOrder'] ?? 0);
                unset($payload['pageId'], $payload['pelOrder'], $payload['original_event_id']);
                
                // Ensure nulls are handled correctly for QueryBuilder
                if (isset($payload['eleParentFK']) && $payload['eleParentFK'] === 0) {
                    $payload['eleParentFK'] = null;
                }
                
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblElements')->insert($payload);
                $qb->doExecute($db, $sql);
                $elementId = (int)$db->lastInsertId();

                if ($pageId > 0) {
                    if ($pelOrder === 0) {
                        // Get max order fallback
                        $qb_order = new QueryBuilder($dialect);
                        $row = $qb_order->table('brgPageElements')->select([$qb_order->raw('MAX(pelOrder) as max_order')])->where('pelPageFK', '=', $pageId)->getFetch($db);
                        $pelOrder = (($row['max_order'] ?? 0) + 10);
                    }

                    $qb_bridge = new QueryBuilder($dialect);
                    $sql_bridge = $qb_bridge->table('brgPageElements')->insert([
                        'pelPageFK' => $pageId,
                        'pelElementFK' => $elementId,
                        'pelOrder' => $pelOrder
                    ]);
                    $qb_bridge->doExecute($db, $sql_bridge);
                }
                return $elementId;
            },
            'ElementUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['elePK'];
                $pageId = (int)($payload['pageId'] ?? 0);
                $pelOrder = (int)($payload['pelOrder'] ?? 0);
                unset($payload['elePK'], $payload['pageId'], $payload['pelOrder'], $payload['original_event_id']);
                
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblElements')->where('elePK', '=', $pk)->update($payload);
                $qb->doExecute($db, $sql);

                if ($pageId > 0 && $pelOrder > 0) {
                    $qb_bridge = new QueryBuilder($dialect);
                    $sql_bridge = $qb_bridge->table('brgPageElements')
                        ->where('pelPageFK', '=', $pageId)
                        ->where('pelElementFK', '=', $pk)
                        ->update(['pelOrder' => $pelOrder]);
                    $qb_bridge->doExecute($db, $sql_bridge);
                }
            },
            'ElementDeleted' => function($payload, $db, $dialect) {
                $pk = (int)$payload['elePK'];
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblElements')->where('elePK', '=', $pk)->delete();
                $qb->doExecute($db, $sql);
            },
        ];
    }
}
?>
