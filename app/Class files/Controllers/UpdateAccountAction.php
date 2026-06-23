<?php
class UpdateAccountAction implements ControllerInterface {
    public static string $path = '/editAccount';
    public static string $manage_URI = '/account_management';
    public static string $object_URI = '/edit_account';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

            $cleanData = FormValidation::processAndValidate('editUser', $request->post, $sessionController, function($clean) {
                return self::$object_URI . "?id=" . $clean['auPK'];
            });

            $userId = (int)($cleanData['auPK'] ?? 0);
            $authorId = $sessionController->getSystemUserId();

            if ($userId <= 0) {
                error_log("UpdateAccountAction: Invalid auPK provided in cleanData.");
                Hyperlink::redirection(self::$manage_URI);
            }

            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('appUsers')->where('auPK', '=', $userId)->executeFetch($db);
            
            // Ensure we have an array for the event store
            $previousPayload = is_array($oldData) ? $oldData : null;

            $updateData = [
                'username' => $cleanData['username'],
                'name'     => $cleanData['name'],
                'age'      => (int)$cleanData['age'],
                'city'     => $cleanData['city'],
                'email'    => $cleanData['email'],
                'verified' => (isset($request->post['verified_status']) && $request->post['verified_status'] === '1') ? 1 : 0,
            ];
            
            $eventId = $eventStore->append('AccountUpdated', array_merge(['auPK' => $userId], $updateData), $userId, $authorId, $previousPayload);
            $eventStore->waitUntilProcessed($eventId);

            Hyperlink::redirection(self::$object_URI . '?id=' . $userId, $userId);
    }

    public static function getEventHandlers(): array {
        return [
            'AccountUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['auPK'];
                unset($payload['auPK'], $payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('appUsers')->where('auPK', '=', $pk)->update($payload);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            },
        ];
    }
}
?>