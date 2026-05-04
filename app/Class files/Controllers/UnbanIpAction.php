<?php
class UnbanIpAction implements ControllerInterface {
    public static string $path = '/unban_ip';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect, $sessionController;

        if ($request->getMethod() !== 'POST') {
            header("Location: /banned_ips");
            exit;
        }

        $pk = $request->post['biPK'] ?? null;
        $action = $request->post['action'] ?? '';

        if ($pk && $action === 'unban') {
            $eventStore = new EventStore($db, $dialect);
            $authorId = $sessionController->getSystemUserId();
            
            // Fetch current data for undo support
            $qb_data = new QueryBuilder($dialect);
            $banData = $qb_data->table('banned_ips')->where('biPK', '=', (int)$pk)->getFetch($db);
            
            if ($banData) {
                $eventStore->append('IpUnbanned', $banData, (int)$pk, $authorId);
            }

            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['success' => true]);
                exit;
            }
        }

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['success' => false, 'error' => 'Invalid request']);
            exit;
        }

        header("Location: /banned_ips");
        exit;
    }

    public static function getEventHandlers(): array {
        return [
            'IpUnbanned' => function($payload, $db, $dialect) {
                $pk = (int)$payload['biPK'];
                
                // 1. Get the IP address first
                $qb_get = new QueryBuilder($dialect);
                $sql_get = $qb_get->table('banned_ips')->select(['biIP'])->where('biPK', '=', $pk)->toSQL();
                $stmt_get = $db->prepare($sql_get);
                $qb_get->bindTo($stmt_get);
                $stmt_get->execute();
                $row = $stmt_get->fetch();

                if ($row) {
                    $ip = $row['biIP'];

                    // 2. Delete the ban record
                    $qb_del = new QueryBuilder($dialect);
                    $sql_del = $qb_del->table('banned_ips')->where('biPK', '=', $pk)->delete();
                    $db->prepare($sql_del)->execute($qb_del->getBindings());

                    // 3. Clear httpAction history for this IP (last 24 hours)
                    $oneDayAgo = (new DateTime())->modify("-24 hours")->format('Y-m-d H:i:s');
                    $qb_clear = new QueryBuilder($dialect);
                    $sql_clear = $qb_clear->table('httpAction')
                                         ->where('haIP', '=', $ip)
                                         ->where('haDate', '>=', $oneDayAgo)
                                         ->delete();
                    $db->prepare($sql_clear)->execute($qb_clear->getBindings());
                }
            },
        ];
    }
}
?>
