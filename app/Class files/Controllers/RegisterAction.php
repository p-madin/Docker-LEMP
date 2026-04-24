<?php
class RegisterAction implements ControllerInterface {
    public static string $path = '/register';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        $cleanData = FormValidation::processAndValidate('register', $request->post, $formSchemas, $sessionController, '/');

        $passwordHash = password_hash($request->post['password'] ?? '', PASSWORD_DEFAULT);

        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers');
        $sql = $qb->insert([
            'name'     => $cleanData['name'] ?? '',
            'age'      => (int)($cleanData['age'] ?? 0),
            'city'     => $cleanData['city'] ?? "",
            'username' => $cleanData['username'] ?? '',
            'password' => $passwordHash,
            'email'    => $cleanData['email'] ?? "",
            'verified' => 0
        ]);

        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();

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
}
?>
