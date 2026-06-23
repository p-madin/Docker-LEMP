<?php
class LoginAction implements ControllerInterface {
    public static string $path = '/login';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        $cleanData = FormValidation::processAndValidate('login', $request->post, $sessionController, '/');
        $sanitizedUsername = $cleanData['username'] ?? '';

        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers')->select(['auPK', 'password', 'verified'])->where('username', '=', $sanitizedUsername);
        $user = $qb->executeFetch($db);

        if($user && password_verify($request->post['password'] ?? '', $user['password'])){
            if($user['verified'] == 0){
                if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                    http_response_code(403);
                    echo json_encode(['errors' => ['password' => 'Invalid username or password.']]);
                    exit;
                }
                Hyperlink::redirection("/?error=unverified");
            }

            $sessionController->initializeUserSession((int)$user['auPK']);
            $sessionController->setPrimary('username', $sanitizedUsername);
        } else {
            // Invalid credentials
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(401);
                echo json_encode(['errors' => ['password' => 'Invalid username or password.']]);
                exit;
            }
        }
        
        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            Hyperlink::clientSideRedirection("/", null, true);
        }
        Hyperlink::redirection("/");
    }

    public static function getEventHandlers(): array {
        return [
            'UserLoggedIn' => function($payload, $db, $dialect) {
                // Audit-only event — no state mutation required.
            }
        ];
    }
}
?>
