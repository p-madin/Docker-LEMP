<?php
/**
 * worker.php
 * Background daemon for processing Event Sourcing queues.
 * To be run via a simple cron job (e.g., * * * * * php /path/to/worker.php)
 */

require_once __DIR__ . '/../Class files/config.php';
// Bootstrap the database connection without dispatching to any controller.
// DatabaseConfigMiddleware populates the $db, $dialect, and $systemConfigController globals.
$request = new Request();
$dbMiddleware = new DatabaseConfigMiddleware();
// no-op: we only need the DB bootstrap, not a controller dispatch
$dbMiddleware->handle($request, function() {});

// Dynamically collect event handlers from all registered controllers.
// Each controller can define a static getEventHandlers() method.
$handlers = [];
foreach ($router->getRegistry() as $path => $meta) {
    $controller = $meta['controller'];
    if (method_exists($controller, 'getEventHandlers')) {
        $handlers = array_merge($handlers, $controller::getEventHandlers());
    }
}

#echo "Loaded handlers: " . implode(', ', array_keys($handlers)) . "\n";
echo "Worker started...\n";

$startDateTime = new DateTime();
$startMinute = $startDateTime->format('i');

while (true) {
    $currentDateTime = new DateTime();
    $currentMinute = $currentDateTime->format('i');
    // Exit before beginning a new cycle if our time window has elapsed.
    if ($startMinute != $currentMinute) {
        echo "Time limit reached. Exiting worker.\n";
        exit(0);
    }

    $db->beginTransaction();

    
    // Find the next unprocessed event and lock it exclusively
    // FOR UPDATE SKIP LOCKED is supported in MySQL 8.0+ and PostgreSQL
    $stmt = $db->prepare("SELECT id, event_type, payload 
                          FROM event_store 
                          WHERE status = 'pending' 
                          ORDER BY id ASC 
                          LIMIT 1 
                          FOR UPDATE SKIP LOCKED");
    $stmt->execute();
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($event) {
        try {
            $updateStmt = $db->prepare("UPDATE event_store SET status = 'processing' WHERE id = :id");
            $updateStmt->execute([':id' => $event['id']]);
            // $db->commit();
            $eventType = $event['event_type'];
            $payload = json_decode($event['payload'], true);

            echo "Processing event ID: {$event['id']} ({$eventType})...\n";

            if (isset($handlers[$eventType])) {
                $returnedId = $handlers[$eventType]($payload, $db, $dialect);
                
                // If the handler returned an ID (typically for 'Created' events), 
                // update the aggregate_id in the store so it can be used for Undos.
                if ($returnedId && is_numeric($returnedId)) {
                    $updateAggStmt = $db->prepare("UPDATE event_store SET aggregate_id = :agg_id WHERE id = :id");
                    $updateAggStmt->execute([':agg_id' => $returnedId, ':id' => $event['id']]);
                }
            } else {
                throw new Exception("No handler found for event type: {$eventType}");
            }
            
            // Mark as complete and commit
            $updateStmt = $db->prepare("UPDATE event_store SET status = 'processed' WHERE id = :id");
            $updateStmt->execute([':id' => $event['id']]);
            $db->commit();
            
            echo "Successfully processed event ID: {$event['id']}\n";
        } catch (Exception $e) {
            $db->rollBack();
            
            // Re-open a non-transactional connection to mark as failed
            $failStmt = $db->prepare("UPDATE event_store SET status = 'failed' WHERE id = :id");
            $failStmt->execute([':id' => $event['id']]);
            
            echo "Failed to process event ID: {$event['id']}. Error: " . $e->getMessage() . "\n";
        }
    } else {
        $db->rollBack();
        // Queue is empty — sleep briefly and poll again within this cron window.
        usleep(10000); // 10 ms
    }
}
?>
