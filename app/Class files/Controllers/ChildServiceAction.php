<?php
class ChildServiceAction implements ControllerInterface {
    public static string $path = '/childServiceAction';
    public static string $manage_URI = '/child_management';
    public static string $object_URI = '/child_management';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect;

        $action = $request->post['action'];
        $csPK = $request->post['csPK'];

        if (!$action || !$csPK) {
            $this->redirectError('Missing parameters');
            return;
        }

        $qb = new QueryBuilder($dialect);
        $service = $qb->table('absChildServices')
            ->select(['csPK', 'csName', 'csStatus'])
            ->where('csPK', '=', $csPK)
            ->executeFetch($db);

        if (!$service) {
            $this->redirectError('Child service not found');
            return;
        }

        $tenant = $service['csName'];
        $manager = new ChildServiceManager();

        try {
            switch ($action) {
                case 'start':
                    $manager->start($tenant);
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('absChildServices')->where('csPK', '=', $csPK)->update(['csStatus' => 'a']);
                    $qb->doExecute($db, $sql);
                    break;
                case 'stop':
                    $manager->stop($tenant);
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('absChildServices')->where('csPK', '=', $csPK)->update(['csStatus' => 'p']);
                    $qb->doExecute($db, $sql);
                    break;
                case 'delete':
                    $manager->delete($tenant);
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('absChildServices')->where('csPK', '=', $csPK)->delete();
                    $qb->doExecute($db, $sql);
                    break;
                case 'sync':
                    $statusArray = $manager->getChildStatus($tenant, $service['csStatus'] ?? 'u', (int)($service['csFailureCount'] ?? 0));
                    
                    $updateData = [
                        'csStatus' => $statusArray['csStatus'], 
                        'csFailureCount' => $statusArray['csFailureCount'],
                        'csCheckDate' => date('Y-m-d H:i:s')
                    ];
                    if ($statusArray['csStatus'] === 'a') {
                        $updateData['csUptimeDate'] = date('Y-m-d H:i:s');
                    }
                    
                    $qb = new QueryBuilder($dialect);
                    $sql = $qb->table('absChildServices')->where('csPK', '=', $csPK)->update($updateData);
                    $qb->doExecute($db, $sql);
                    break;
                default:
                    $this->redirectError('Invalid action');
                    return;
            }
            
            // Redirect back to referring page or child services list
            Hyperlink::redirection(self::$object_URI . "?id=" . $csPK, (int)$csPK);
        } catch (Exception $e) {
            error_log("ChildServiceAction error: " . $e->getMessage());
            $this->redirectError('An error occurred while processing the action');
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
