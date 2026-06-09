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
Audit the `WafMiddleware.php` to ensure its recursive sanitisation logic gracefully handles deeply nested `$_POST` arrays without flattening them or triggering false-positive security blocks.
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

## Phase 7: Page Builder & Block Editor [COMPLETED]
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

### Data Model

Pages are recorded to `tblPages` and blocks are recorded to `tblElements`. A many-to-many bridge table `brgPageElements` associates elements to pages and defines their ordering via `pelOrder`.

```
┌──────────────┐       ┌──────────────────┐       ┌──────────────┐
│   tblPages   │       │  brgPageElements │       │  tblElements │
├──────────────┤       ├──────────────────┤       ├──────────────┤
│ pagPK (PK)   │◄──────│ pelPageFK (FK)   │       │ elePK (PK)   │
│ pagTitle     │       │ pelElementFK(FK) │──────►│ eleType      │
│ pagSlug      │       │ pelOrder         │       │ eleContent   │
│ pagAuthorFK  │       └──────────────────┘       │ eleCSSClasses│
│ pagCreated   │                                  │ eleParentFK ─┼──┐
│ pagUpdated   │                                  │ eleCreated   │  │
│ pagDeleted   │                                  └──────────────┘  │
└──────────────┘                                        ▲           │
                                                        └───────────┘
                                                     (self-referential)
```

Container elements (e.g. flexbox) may have children. The parent-child hierarchy is defined by `tblElements.eleParentFK`, which is a self-referential foreign key back to `tblElements.elePK`. This allows arbitrarily nested block structures within a single page.

## Phase 8: Persistence & C(-r)UD Controller [COMPLETED]
### Description
Introduce a server-side controller to manage the persistence of pages and their associated elements using the established Event Sourcing architecture. This ensures all changes to the site structure are auditable and reversible.

### Components

1 - **PageController & ElementController**:
   Implement controllers following the `EditColumnAction` pattern:
   - Utilise `QueryBuilder` for database interactions with `tblPages` and `tblElements`.
   - Use `FormValidation` for server-side integrity checks.
   - Integrate with `EventStore` to record `PageCreated`, `PageUpdated`, `PageDeleted`, and element-level events (`ElementCreated`, `ElementUpdated`, `ElementDeleted`).
   - Define event handlers in `getEventHandlers()` to materialise the state into the relational tables (`tblPages`, `tblElements`, `brgPageElements`).

2 - **Bridge Management**:
   Automate the management of the `brgPageElements` table to maintain the ordering and association of elements within pages during the event materialisation phase.

3 - **Persistence API**:
   Expose JSON endpoints to allow the Javascript Block Editor to "Save" the current workspace state by dispatching events to the server.

4 - **Soft Deletion**:
   Implement soft-deletion for pages using the `pagDeleted` timestamp, consistent with existing system patterns.

## Phase 9: SaaS enabled platform - CRUD independant tennant manager [COMPLETED]
### Description
Implement the core management functionalities for the SaaS platform, isolating tenant-specific resources and handling their lifecycle.

### Implemented Components
1. **`ChildServiceManager`** (`./app/Class files/Services/ChildServiceManager.php`):
   - Encapsulates complex Docker orchestration and filesystem operations to adhere to SOLID principles.
   - Responsible for generating tenant-specific `compose.yaml` and `.env` files.
   - Generates Nginx routing configurations dynamically and automatically reloads the host Nginx daemon to route `/<tenant-name>` to the specific container.
   - Provides wrappers for `docker-compose up/down` and container status synchronisation (`sync`).

2. **`ChildServiceAction`** (`./app/Class files/Controllers/ChildServiceAction.php`):
   - Acts as the unified controller handling row-level management requests (`start`, `stop`, `delete`, `sync`).
   - Translates the database ID (`csPK`) into the correct tenant name and orchestrates state changes via the `ChildServiceManager`, before updating the `absChildServices` database table.

3. **`CreateChildServiceAction`** (`./app/Class files/Controllers/CreateChildServiceAction.php`):
   - Handles form submissions for provisioning new SaaS tenants.
   - Enforces strict security through the `AlphaDashDecorator` to sanitize inputs and uses `Validator` to ensure the tenant name strictly follows `alpha_dash` formatting requirements.

4. **User Interface** (`./app/Class files/Data/Providers/ChildServiceDataProvider.php`):
   - Provides a Data Provider mapped to the `absChildServices` table.
   - Incorporates a rich multi-action column, enabling administrators to easily `View`, `Sync`, `Start`, `Stop`, and `Delete` tenant containers directly from the Flex Table.
   - The provisioning form is dynamically injected into the management page view via structural DML records in `02_db_dml.sql`.

### Outstanding Items
- **Create client form required fields**: Ensure the form explicitly requires fields for `admin`, `admin username`, and `password` to properly initialise new tenants with an administrative account.
- **SaaS Feature Testing**: Build comprehensive test coverage for all SaaS functionalities within `./app/Test/main.php`, validating tenant creation, isolation, and teardown.

---

## Phase 10: Enhance testing functionality [COMPLETED]
### Description
The current application and testing framework (in `./app/Test/main.php`) does not yet have functionality to record functional (database) dependencies for storing created objects (users, forms, pages). The main concern this addresses is **simple test definition**.

Currently, in `./app/Test/Test Suite/Test Contract/test-suite.xml`, tests rely on complex CSS selectors targeting strings (e.g., `.form_hyperlink[id^="edit-user-LifecycleUser"]`) to interact with newly created rows. The testing framework needs to support capturing the auto-increment ID of registered resources and storing them as runtime **test suite variables**.

### Proposed Solution
Implement a **Test Variable State Manager** within the testing framework:
1. **Variable Extraction**: Enhance the test engine to capture IDs of newly created records upon successful form submissions or specific assertions, saving them into a runtime variable dictionary (e.g., `registered_user_id = 42`).
2. **Variable Interpolation**: Allow subsequent steps in the XML test definition to reference these variables using a specific syntax (like `${registered_user_id}`).
3. **Simplified Selectors**: Update the test contracts to use these IDs for direct navigation or specific button targeting, drastically simplifying the test logic and making the XML contracts cleaner and more robust against UI changes.
4. **Automated Teardown (Bonus)**: This variable registry can also be used at the end of the test execution to easily clean up and delete the created dependencies, preventing state pollution between runs.
