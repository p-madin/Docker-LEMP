<?php
class UpdateAccountAction implements ControllerInterface {
    public static string $path = '/editAccount';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST'){

            $cleanData = FormValidation::processAndValidate('editUser', $request->post, $formSchemas, $sessionController, function($clean) {
                return "/edit_account.php?id=" . $clean['auPK'];
            });

            $userId = (int)($cleanData['auPK'] ?? 0);
            $authorId = $sessionController->getSystemUserId();

            if ($userId <= 0) {
                error_log("UpdateAccountAction: Invalid auPK provided in cleanData.");
                header("Location: /account_management");
                exit;
            }

            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('appUsers')->where('auPK', '=', $userId)->getFetch($db);
            
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

            $redirectUrl = "/account_management";
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /account_management");
        exit;
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