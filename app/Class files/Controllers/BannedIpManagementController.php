<?php

class BannedIpManagementController implements ControllerInterface {
    public static string $path = '/banned_ips';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;

        // Fetch Banned IPs
        $qb = new QueryBuilder($dialect);
        $sql = $qb->table('banned_ips')
                  ->select(['biPK', 'biIP', 'biReason', 'biExpires', 'biDateAdded'])
                  ->toSQL();
        
        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $items = $stmt->fetchAll();

        return View::render('management/banned_ips', [
            'items' => $items
        ]);
    }
}
?>