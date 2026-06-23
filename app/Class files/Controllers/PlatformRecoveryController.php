<?php
class PlatformRecoveryController implements ControllerInterface {
    public static string $path = '/platform_recovery';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $sessionController, $eventStore;

        // Ensure user has admin privileges to access platform recovery
        $userId = $sessionController->getSystemUserId();
        if (!$userId) {
            Hyperlink::redirection("/");
        }

        $qb = new QueryBuilder($dialect);

        // Handle Replay Request
        if ($request->getMethod() === 'POST' && isset($request->post['action']) && $request->post['action'] === 'replay') {
            $targetTime = $request->post['target_time'] ?? null;
            if ($targetTime) {
                $eventStore->append('PlatformRecoveryReplay', ['target_time' => $targetTime, 'user_id' => $userId], null, $userId);
                Hyperlink::redirection("/platform_recovery?msg=replay_queued");
            }
        }

        // 1. Fetch General Event Log
        $events = $qb->table('tblEventStore')->orderBy('evsPK', 'DESC')->limit(100)->executeFetchAll($db);

        // 2. Fetch Current User's Undoable Path
        $undoableEvents = $eventStore->getUndoableEvents($sessionController);
        $redoEvents = $eventStore->getRedoableEvents($sessionController);

        $events = $this->listInfer($events);
        $undoableEvents = $this->listInfer($undoableEvents);
        $redoEvents = $this->listInfer($redoEvents);

        return View::render('management/platform_recovery', [
            'events'         => $events,
            'undoableEvents' => $undoableEvents,
            'redoEvents'     => $redoEvents,
            'msg'            => $request->get['msg'] ?? null,
            
        ]);
    }

    public function listInfer($list){
        $result = [];
        foreach ($list as $event) {
            $event['evsAggregateFK'] = $event['evsAggregateFK'] ?? 'N/A';
            $event['evsUserFK']      = $event['evsUserFK'] ?? 'System';
            $event['_payload_details'] = [
                ['is_full_width' => true, 'content' => $event['evsPayload']]
            ];
            $result[] = $event;
        }
        return $result;

    }

    public static function getEventHandlers(): array {
        return [
            'PlatformRecoveryReplay' => function($payload, $db, $dialect) {
                $targetTimeRaw = $payload['target_time'] ?? null;
                $userId = $payload['user_id'] ?? null;
                if (!$targetTimeRaw || !$userId) return null;
                
                // Format datetime-local to standard SQL datetime
                $targetTimeStr = str_replace('T', ' ', $targetTimeRaw);
                if (strlen($targetTimeStr) == 16) {
                    $targetTimeStr .= ':00';
                }

                $eventStore = new EventStore($db, $dialect);

                // 1. Fetch events to undo
                $qb = new QueryBuilder($dialect);
                $eventsToUndo = $qb->table('tblEventStore')
                          ->where('evsCreatedAt', '>', $targetTimeStr)
                          ->where('evsStatus', '=', 'processed')
                          ->where('evsEventType', '!=', 'PlatformRecoveryReplay')
                          ->orderBy('evsPK', 'DESC')
                          ->executeFetchAll($db);

                $lastReversalId = null;

                foreach ($eventsToUndo as $event) {
                    $newId = $eventStore->createReversalEvent($event, $userId, true);
                    if ($newId) {
                        $lastReversalId = $newId;
                    }
                }

                // 2. Clear redo_stack for all users globally
                $qbDel = new QueryBuilder($dialect);
                $sqlDel = $qbDel->table('tblSessionAtt')->where('sattKey', '=', 'redo_stack')->delete();
                $stmtDel = $db->prepare($sqlDel);
                $qbDel->bindTo($stmtDel);
                $stmtDel->execute();

                // 3. Update the performing user's current_event_id
                if ($lastReversalId) {
                    $sessionController = new SessionController($db, $dialect);
                    
                    $rawSql = "SELECT a.sattSessionFK 
                               FROM tblSessionAtt a 
                               JOIN tblSessionAttValue v ON a.sattPrimaryValueFK = v.sattvPK 
                               WHERE a.sattKey = 'userID' AND v.sattvValue = :uid";
                    $stmtSess = $db->prepare($rawSql);
                    $stmtSess->execute([':uid' => $userId]);
                    $sessions = $stmtSess->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($sessions as $sessPK) {
                        $sessionController->sessPK = $sessPK;
                        $sessionController->setPrimary('current_event_id', $lastReversalId);
                    }
                }

                return null;
            }
        ];
    }
}
?>
