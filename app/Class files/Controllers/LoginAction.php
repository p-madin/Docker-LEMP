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
                header("location:/?error=unverified");
                exit;
            }
            $sessionController->setPrimary('username', $sanitizedUsername);
            $sessionController->setPrimary('userID', (int)$user['auPK']);
        }
        
        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => '/']);
            exit;
        }
        header("location:/");
        exit;
    }
}

$controllerList[LoginAction::$path] = new LoginAction();
?>
