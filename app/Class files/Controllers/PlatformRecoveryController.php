<?php
class PlatformRecoveryController implements ControllerInterface {
    public static string $path = '/platform_recovery';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $sessionController, $eventStore;

        // Ensure user has admin privileges to access platform recovery
        $userId = $sessionController->getSystemUserId();
        if (!$userId) {
            header("Location: /");
            exit;
        }

        $qb = new QueryBuilder($dialect);

        // Handle Replay Request
        if ($request->getMethod() === 'POST' && isset($request->post['action']) && $request->post['action'] === 'replay') {
            $targetTime = $request->post['target_time'] ?? null;
            if ($targetTime) {
                $eventStore->append('PlatformRecoveryReplay', ['target_time' => $targetTime], null, $userId);

                header("Location: /platform_recovery?msg=replay_queued");
                exit;
            }
        }

        // 1. Fetch General Event Log
        $sql = $qb->table('event_store')->orderBy('id', 'DESC')->limit(100)->toSQL();
        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
            $event['aggregate_id'] = $event['aggregate_id'] ?? 'N/A';
            $event['user_id']      = $event['user_id'] ?? 'System';
            $event['_payload_details'] = [
                ['is_full_width' => true, 'content' => $event['payload']]
            ];
            $result[] = $event;
        }
        return $result;

    }
}
?>
