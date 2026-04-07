<?php
class EditNavbarAction implements ControllerInterface {
    public static string $path = '/editNavbar';
    public bool $isAction = true;
    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST') {
            // Handle pure delete request
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['nbPK'] ?? 0);
                if ($pk > 0) {
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('tblNavBar')->where('nbPK', '=', $pk)->delete();
                    $stmt = $db->prepare($sql);
                    $qb->bindTo($stmt);
                    $stmt->execute();
                }
                header("Location: /navbar_management.php");
                exit;
            }

            $cleanData = FormValidation::processAndValidate('navbar', $request->post, $formSchemas, $sessionController, function($clean) {
                $id = $clean['nbPK'] ?? 0;
                return "/edit_navbar.php" . ($id ? "?id=".$id : "");
            });

            $isProtected = (isset($request->post['nbProtected']) && $request->post['nbProtected'] === '1') ? 1 : 0;
            
            $data = [
                'nbText' => $cleanData['nbText'],
                'nbDiscriminator' => $cleanData['nbDiscriminator'],
                'nbPath' => $cleanData['nbPath'],
                'nbOrder' => (int)$cleanData['nbOrder'],
                'nbProtected' => $isProtected
            ];

            $pk = (int)$cleanData['nbPK'];

            if ($pk > 0) {
                // UPDATE
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->where('nbPK', '=', $pk)->update($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            } else {
                // INSERT
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->insert($data);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            }

            $redirectUrl = "/navbar_management.php";
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /navbar_management.php");
        exit;
    }
}

$controllerList[EditNavbarAction::$path] = new EditNavbarAction();
?>
