<?php
class UpdateAccountAction implements ControllerInterface {
    public static string $path = '/editAccount';
    public bool $isAction = true;
    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST'){

            $cleanData = FormValidation::processAndValidate('editUser', $request->post, $formSchemas, $sessionController, function($clean) {
                return "/edit_account.php?id=" . $clean['auPK'];
            });

            if(isset($request->post['toggle_verify'])){
                // Legacy toggle support (if needed)
                $isVerified = (isset($request->post['is_verified']) && $request->post['is_verified'] === '1') ? 1 : 0;
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('appUsers')->where('auPK', '=', $cleanData['auPK'])->update(['verified' => $isVerified]);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            } else {
                // Build the update query
                $updateData = [
                    'username' => $cleanData['username'],
                    'name'     => $cleanData['name'],
                    'age'      => (int)$cleanData['age'],
                    'city'     => $cleanData['city'],
                    'email'    => $cleanData['email']
                ];

                // Admin verification update
                $updateData['verified'] = (isset($request->post['verified_status']) && $request->post['verified_status'] === '1') ? 1 : 0;

                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('appUsers')->where('auPK', '=', (int)$cleanData['auPK'])->update($updateData);
                
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            }

            $redirectUrl = "/account_management.php";
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /account_management.php");
        exit;
    }
}
$controllerList[UpdateAccountAction::$path] = new UpdateAccountAction();
?>