# Roadmap

This document outlines the comprehensive roadmap for the development of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Retrofit Remaining CRUD Actions for AJAX [COMPLETED]
### Description
Refactor the internal Action Controllers to intercept `Accept: application/json` headers and return standard JSON error arrays, ensuring the management suite enjoys the same seamless, no-reload UX as the public forms.
> [!NOTE]
> **Ground Running**: The Phase 7 `validator.js` engine expects unified JSON responses, but several of the internal management controllers (e.g., Edit Account, Edit Form, Edit Navbar) relied on legacy `header("Location: ...")` redirects upon form failure. These must be updated to return JSON when an AJAX request is detected.

### Metrics (Qualities & Quantities)
- **Complexity**: Low
- **Risk Level**: Low (Isolated to controller return types)
- **Estimated Time**: 1 Day
- **Number of Files**: ~5
- **Lines of Code**: ~100 LOC

### Related Components
- `./app/Class files/Controllers/UnbanIpAction.php`
- Other CRUD Action Controllers

### Code Example
```php
if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
    echo json_encode(['success' => true]);
    exit;
}
header("Location: /fallback");
exit;
```

---

## Phase 2: Review WAF Middleware for Nested Arrays [COMPLETED]
### Description
Audit the `WafMiddleware.php` to ensure its recursive sanitization logic gracefully handles deeply nested `$_POST` arrays without flattening them or triggering false-positive security blocks.
> [!IMPORTANT]
> **Ground Running**: Recent UI updates introduced nested data structures (like the recursive Navbar components). The middleware must iterate over nested arrays natively.

### Metrics (Qualities & Quantities)
- **Complexity**: Low
- **Risk Level**: High (Directly affects WAF security processing)
- **Estimated Time**: 1 Day
- **Number of Files**: 1
- **Lines of Code**: ~50 LOC

### Related Components
- `./app/Class files/Middleware/WafMiddleware.php`

### Code Example
```php
protected function inspectPayload(array $payload, string $ip, Request $request) {
    foreach ($payload as $key => $value) {
        if (is_array($value)) {
            $this->inspectPayload($value, $ip, $request);
        } else if (is_string($value)) {
            // ... apply dangerous pattern checks
        }
    }
}
```

---

## Phase 3: Implement Event Sourcing Mechanism [COMPLETED]
### Description
Replace traditional database CRUD operations with an immutable event sourcing pattern, creating a verifiable history of every change made to the system.
> [!NOTE]
> **Ground Running**: Introduce the `event_store` table to hold `aggregate_id`, `event_type`, and `payload`. Refactor database interactions in key actions to queue these events rather than mutating state directly.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: High (Architectural shift)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~4
- **Lines of Code**: ~300 LOC

### Related Components
- `./conf/01_db_ddl.sql`
- `./app/Class files/EventStore.php`

### Code Example
```php
$eventStore = new EventStore($db, $dialect);
$eventStore->append('UserRegistered', [
    'username' => 'john_doe',
    'email' => 'john@example.com'
]);
```

---

## Phase 4: Implement worker.php
### Description
`worker.php` is implemented to execute users' actions in the event sourcing pattern established in Phase 3, functioning as an "Event Handler".
> [!IMPORTANT]
> **Ground Running**: This script must use `FOR UPDATE SKIP LOCKED` to allow concurrency, processing events asynchronously via a cron job or daemon. Note: `cron` and the crontabs still need to be installed in `./Dockerfile` & `./conf/crontab`.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium
- **Estimated Time**: 1-2 Days
- **Number of Files**: 1
- **Lines of Code**: ~150 LOC

### Related Components
- `./app/Cron/worker.php`
- `./Dockerfile`
- `./conf/crontab`

### Code Example
```php
while (true) {
    $db->beginTransaction();
    
    // Find the next unprocessed event and lock it exclusively
    $stmt = $db->prepare("
        SELECT id, payload 
        FROM event_store 
        WHERE status = 'pending' 
        ORDER BY id ASC 
        LIMIT 1 
        FOR UPDATE SKIP LOCKED
    ");
    $stmt->execute();
    $event = $stmt->fetch();

    if ($event) {
        try {
            $handler->process($event['payload']);
            $db->exec("UPDATE event_store SET status = 'processed' WHERE id = " . $event['id']);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            $db->exec("UPDATE event_store SET status = 'failed' WHERE id = " . $event['id']);
        }
    } else {
        $db->rollBack();
        break; // Exit if using simple cron
    }
}
```

---

## Phase 5: Event Sourcing Command & Memento Pattern [COMPLETED]
### Description
Use the Event Sourcing Mechanism to establish Command Pattern and Memento Pattern-like behaviour, empowering users to easily undo/redo their actions.
> [!NOTE]
> **Ground Running**: Implementing an `UndoRedoController` that fetches the last executed event for the user and generates an inverse compensating event.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~1
- **Lines of Code**: ~100 LOC

### Related Components
- `./app/Class files/Controllers/UndoRedoController.php`

### Code Example
```php
$inverseEventType = 'Undo' . $lastEvent['event_type'];
$eventStore->append($inverseEventType, ['original_event_id' => $lastEvent['id']], $lastEvent['aggregate_id']);
```

---

## Phase 6: Platform Recovery User Interface
### Description
Implement a Platform Recovery User Interface that includes an Immutable Event Store viewer, allowing administrators to recover their platform from a corrupted state.
> [!IMPORTANT]
> **Ground Running**: Use internal DOM components like `FlexTableComponent` to display the event log, and provide a secure mechanism to trigger point-in-time recovery replays up to a specific timestamp.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium
- **Estimated Time**: 2 Days
- **Number of Files**: ~2
- **Lines of Code**: ~200 LOC

### Related Components
- `./app/Class files/Controllers/PlatformRecoveryController.php`

### Code Example
```php
$flexTable = new FlexTableComponent($dom);
$flexTable->setColumns([
    ['key' => 'id', 'label' => 'ID', 'isAction' => false],
    ['key' => 'event_type', 'label' => 'Type', 'isAction' => false],
    ['key' => 'status', 'label' => 'Status', 'isAction' => false]
]);
$flexTable->setData($events);
$container->appendChild($flexTable->getRoot());
```
