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

## Phase 4: Implement worker.php [COMPLETED]
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

## Phase 6: Platform Recovery User Interface [COMPLETED]
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

---

## Phase 7: Page Builder & Block Editor
### Description
A page builder is introduced with a vanilla javascript client-side (only) block editor.

The following blocks may be clicked and dragged into a (page) workspace: Text (Heading, Paragraph), Container (flexbox), Buttons (CTA buttons, hyperlink.php), Divider (Separator)

### Components

1 - The user may click & drag components from the left sidebar into the central (page) workspace canvas.

2 - The user may click (to select) any instance of a block on the canvas. This action will open a property panel (right sidebar) with properties specific to the selected block type.

The property panel allows the user to modify the properties of the selected block instance.

The properties include:
1. **Block Type**: The type of block selected.
2. **Block ID**: The ID of the block selected.
3. **Block Inner Text**: The inner text of the block selected. (cannot include html entities, this allows user to type what they want without breaking the page)
4. **Block Class name**: The CSS Class name of the block selected.

3 - The "inner text" content of the block may be selected, and edited in place on the canvas.

This allows the user to edit the content of the block directly on the canvas, features like italics, bold, underline, hyperlink, ordered list and unordered list may be applied to this inner text.

## Phase 8: Persistence & C(-r)UD Controller
### Description
Introduce a server-side controller to manage the persistence of pages and their associated elements using the established Event Sourcing architecture. This ensures all changes to the site structure are auditable and reversible.

### Components

1 - **PageController & ElementController**:
   Implement controllers following the `EditColumnAction` pattern:
   - Utilize `QueryBuilder` for database interactions with `tblPages` and `tblElements`.
   - Use `FormValidation` for server-side integrity checks.
   - Integrate with `EventStore` to record `PageCreated`, `PageUpdated`, `PageDeleted`, and element-level events (`ElementCreated`, `ElementUpdated`, `ElementDeleted`).
   - Define event handlers in `getEventHandlers()` to materialize the state into the relational tables (`tblPages`, `tblElements`, `brgPageElements`).

2 - **Bridge Management**:
   Automate the management of the `brgPageElements` table to maintain the ordering and association of elements within pages during the event materialization phase.

3 - **Persistence API**:
   Expose JSON endpoints to allow the Javascript Block Editor to "Save" the current workspace state by dispatching events to the server.

4 - **Soft Deletion**:
   Implement soft-deletion for pages using the `pagDeleted` timestamp, consistent with existing system patterns.


## Phase 9: DBMS Vendor Support
### Description
SQLite - AUTOINCREMENT or INTEGER PRIMARY KEY, Boolean = INTEGER (0/1), strftime('%H', haDate)
MS SQL - auPK INT NOT NULL IDENTITY(1,1), Boolean = BIT, SELECT TOP(), IDENTITY(1,1), DATEPART(datepart, date)

## Phase 10: Infrastructure & Advanced Blocks
### Description
This phase is dedicated to building the necessary data abstraction and reusable presentation components that power advanced blocks like Forms, Tables, and Charts, ensuring these blocks are data-aware rather than just display-aware.

**Key Deliverable**: Implementation of the `GenericDataMapper` service and associated component scaffolding.
> [!IMPORTANT]
> **Ground Running**: The structure of `FlexTableComponent` and `FormComponent` must be updated to depend on data provided by a Mapper Service, which consumes raw DB results and returns a standardized array.

### Core Components to Implement

1.  **GenericDataMapper Service**:
    *   **Purpose**: To abstract the difference between raw database result sets (e.g., PDOStatement fetch rows) and the structured, key-value arrays required by presentation components.
    *   **Location**: `./app/Class files/Services/GenericDataMapper.php`
    *   **Functionality**: Must take a raw result set and a metadata array (defining column names, data types, and required formatting) and output a clean `array` suitable for component consumption.

2.  **Advanced Block Support (Table & Form)**:
    *   **Table (FlexTableComponent)**: The component will be updated to expect data passed through the `GenericDataMapper` before rendering.
    *   **Form (FormComponent)**: Will require a more formalized data mapping step to convert database-ready structures into form field definitions and data payloads.
    *   **Chart (DataGraphComponent)**: Will rely on the Mapper Service to aggregate and pivot raw data into the specific time-series or statistical formats required by the charting library.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: Medium (Architectural change, but isolated to the service layer)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3 (Mapper Service, Component Updates)
- **Lines of Code**: ~400 LOC
