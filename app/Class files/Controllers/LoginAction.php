<?php
class LoginAction implements ControllerInterface {
    public static string $path = '/login';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        $cleanData = FormValidation::processAndValidate('login', $request->post, $formSchemas, $sessionController, '/');
        $sanitizedUsername = $cleanData['username'] ?? '';

        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers')->select(['auPK', 'password', 'verified'])->where('username', '=', $sanitizedUsername);
        $stmt = $db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();

        $user = $stmt->fetch();

        if($user && password_verify($request->post['password'] ?? '', $user['password'])){
            if($user['verified'] == 0){
                if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                    // http_response_code(403);
                    // echo json_encode(['errors' => ['username' => 'Account is pending verification.']]);
                    http_response_code(403);
                    echo json_encode(['errors' => ['password' => 'Invalid username or password.']]);
                    exit;
                }
                header("location:/?error=unverified");
                exit;
            }
            $sessionController->setPrimary('username', $sanitizedUsername);
            $sessionController->setPrimary('userID', (int)$user['auPK']);
        } else {
            // Invalid credentials
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                http_response_code(401);
                echo json_encode(['errors' => ['password' => 'Invalid username or password.']]);
                exit;
            }
        }
        
        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => '/']);
            exit;
        }
        header("location:/");
        exit;
    }
}
?>
