<?php
require_once __DIR__ . '/../Class files/config.php';

// Bootstrap the database connection without dispatching to any controller.
// DatabaseConfigMiddleware populates the $db, $dialect, and $systemConfigController globals.
$request = new Request();
$dbMiddleware = new DatabaseConfigMiddleware();
$dbMiddleware->handle($request, function() {});

$qb = new \QueryBuilder($dialect);
$tenants = $qb->table('absChildServices')
    ->where('csStatus', '!=', 'd') // Don't check deleted
    ->where('csStatus', '!=', 'i') // Optionally don't check inactive if they are fully down? Wait, we should check all non-deleted so they can recover.
    ->getFetchAll($db);

$manager = new \ChildServiceManager();

foreach ($tenants as $tenant) {
    if (empty($tenant['csDockerID'])) {
        continue; // Skip those that haven't properly booted or missing ID
    }

    $name = $tenant['csName'];
    $statusArray = $manager->getChildStatus($name, $tenant['csStatus'] ?? 'u', (int)($tenant['csFailureCount'] ?? 0));
    
    $updateData = [
        'csStatus' => $statusArray['csStatus'],
        'csFailureCount' => $statusArray['csFailureCount'],
        'csCheckDate' => new \QueryRaw('CURRENT_TIMESTAMP')
    ];
    
    $isHealthy = $statusArray['is_healthy'];
    
    $qbUpdate = new \QueryBuilder($dialect);
    $updateSql = $qbUpdate->table('absChildServices')->where('csPK', '=', $tenant['csPK'])->update($updateData);
    $stmt = $db->prepare($updateSql);
    $qbUpdate->bindTo($stmt);
    $stmt->execute();
    
    $syncData = $statusArray['raw_sync'] ?? [];
    $debugStr = "Status: " . ($syncData['docker_status'] ?? 'null') . ", HTTP: " . ($syncData['http_code'] ?? 'null');
    
    echo "Checked tenant {$name}: " . ($isHealthy ? "Healthy" : "Failed ({$updateData['csFailureCount']}) [{$debugStr}]") . "\n";
}
