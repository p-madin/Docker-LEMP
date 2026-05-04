<?php
class RegisterAction implements ControllerInterface {
    public static string $path = '/register';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $eventStore;

        Hyperlink::handleAction($sessionController);

        $cleanData = FormValidation::processAndValidate('register', $request->post, $formSchemas, $sessionController, '/');

        $passwordHash = password_hash($request->post['password'] ?? '', PASSWORD_DEFAULT);

        $authorId = $sessionController->getSystemUserId();
        $eventId = $eventStore->append('UserCreated', [
            'name'     => $cleanData['name'] ?? '',
            'age'      => (int)($cleanData['age'] ?? 0),
            'city'     => $cleanData['city'] ?? "",
            'username' => $cleanData['username'] ?? '',
            'password' => $passwordHash,
            'email'    => $cleanData['email'] ?? "",
            'verified' => 0
        ], null, $authorId);
        
        $eventStore->waitUntilProcessed($eventId);

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode([
                'success' => true,
                'html' => '<div class="success-message"><h1>Registration Successful!</h1><p>Welcome, ' . htmlspecialchars($cleanData['name']) . '. You can now sign in.</p><a href="/">Go to Home</a></div>'
            ]);
            exit;
        }
        header("location:/");
        exit;
    }

    public static function getEventHandlers(): array {
        return [
            'UserCreated' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $insertData = [
                    'name'     => $payload['name'] ?? '',
                    'age'      => (int)($payload['age'] ?? 0),
                    'city'     => $payload['city'] ?? "",
                    'username' => $payload['username'] ?? '',
                    'password' => $payload['password'] ?? '',
                    'email'    => $payload['email'] ?? "",
                    'verified' => 0
                ];
                if (isset($payload['auPK'])) {
                    $insertData['auPK'] = (int)$payload['auPK'];
                }
                $sql = $qb->table('appUsers')->insert($insertData);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                return isset($payload['auPK']) ? (int)$payload['auPK'] : (int)$db->lastInsertId();
            },
            'UserDeleted' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('appUsers')
                          ->where('auPK', '=', (int)$payload['auPK'])
                          ->delete();
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                return $stmt->execute();
            }
        ];
    }
}
?>
