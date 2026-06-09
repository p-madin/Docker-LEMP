<?php
class CreateChildServiceAction implements ControllerInterface {
    public static string $path = '/createChildService';
    public static string $manage_URI = '/child_management';
    public static string $object_URI = '/child_management';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect, $sessionController;

        $serviceName = trim($request->post['csName'] ?? '');
        $adminUserId = (int)($request->post['csAdminFK'] ?? 0);
        $authorId = $sessionController->getSystemUserId();

        if (empty($serviceName)) {
            $this->redirectError('Service name is required');
            return;
        }

        if (!preg_match('/^[a-z0-9_-]+$/i', $serviceName)) {
            $this->redirectError('Invalid service name format');
            return;
        }
        
        if ($adminUserId <= 0) {
            $this->redirectError('An administrator user must be selected for the new tenant');
            return;
        }

        $manager = new ChildServiceManager();
        
        try {
            $qb = new QueryBuilder($dialect);
            $sql = $qb->table('absChildServices')->insert([
                'csName' => $serviceName,
                'csAdminFK' => $adminUserId,
                'csCreatedByFK' => $authorId,
                'csStatus' => 'u'
            ]);
            $qb->doExecute($db, $sql);
            $newId = $db->lastInsertId();

            $manager->start($serviceName);
            
            Hyperlink::redirection(self::$object_URI . "?id=" . $newId, (int)$newId);
        } catch (Exception $e) {
            error_log("CreateChildServiceAction error: " . $e->getMessage());
            $this->redirectError('An error occurred while creating child service');
        }
    }

    private function redirectError(string $message) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => ['_general' => $message]]);
            exit;
        }
        Hyperlink::redirection(self::$manage_URI . "?error=" . urlencode($message));
    }
}
?>
