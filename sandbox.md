## Idea: Event Sourcing Snapshots & Compaction
### Description
Prevent the `event_store` replay sequence from taking an unacceptably long time as the application ages.

### Ground Running tips
- Store snapshot payloads in a dedicated snapshots table (`tblSnapshots`) rather than appending them as raw events in `tblEventStore`. 
- This allows quick retrieval of the latest state and its ID, enabling the replayer to read only events with `evsPK > snapshot.last_event_id`.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: Medium
- **Estimated Time**: 3-4 Days
- **Number of Files**: ~3

### Prerequisites
- Event Store implementation ([EventStore.php](app/Class%20files/EventStore.php)).
- Cron background worker ([worker.php](app/Cron/worker.php)).

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [worker.php](app/Cron/worker.php)

---

## Idea: CMS Draft & Publish Workflow
### Description
Address the limitation where all Page Builder edits are instantly pushed live.

### Ground Running tips
- Add a `status` VARCHAR or ENUM column to `tblPages`. 
- For the `/preview` route, ensure you perform administrator authentication checks (`SessionController`) before bypassing the `status = 'Published'` filter.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Low
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~2

### Prerequisites
- Understanding of the rendering pipeline ([PageRendererController.php](app/Class%20files/Controllers/PageRendererController.php)).

### Related Components
- [PageRendererController.php](app/Class%20files/Controllers/PageRendererController.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Idea: The Schema Dictionary for PostgreSQL & MSSQL Case-Sensitivity
### Description
Address the limitation where PostgreSQL natively folds unquoted identifiers to lowercase, and where `ANSIStandardDialect` forces identifiers to lowercase for maximum portability. This destroys camelCase column names (e.g., `nbPK` becomes `nbpk`) when using wildcard `SELECT *` queries in databases like PostgreSQL and MSSQL. This breaks Memento JSON payloads that require exact case matching.

### Ground Running tips
- Parse the XML schema definition `01_db_ddl.xml` directly rather than SQL files to ensure single source-of-truth alignment. 
- In `QueryBuilder::executeFetch` and `executeFetchAll`, check if the active dialect is PostgreSQL or MSSQL; if so, intercept the resulting array keys and map them back to camelCase using the dictionary.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium-High (Direct impact on all database SELECT operations)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Prerequisites
- Basic XML parsing skills (`SimpleXML`).
- QueryBuilder database retrieval wrappers ([QueryBuilder.php](app/Class%20files/QueryBuilder.php)).

### Related Components
- [QueryBuilder.php](app/Class%20files/QueryBuilder.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Idea: Robust Container Orchestration
### Description
The current `ChildServiceManager` relies heavily on brittle shell execution (`shell_exec`), direct file manipulation, and blocking delays (`sleep(3)`). This subsystem should be modernised for reliability and scalability.

### Ground Running tips
- Route Docker API requests through a secure socket proxy (like `docker-socket-proxy`) to restrict PHP container capabilities (prevent volume mounting of host root, running privileged containers, etc.). 
- Move template stubs to `/conf/templates/` as `.stub` files.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: High (Involves system-level commands and Docker socket access)
- **Estimated Time**: 4-5 Days
- **Number of Files**: ~4

### Prerequisites
- PHP cURL library installation.
- Background cron queues ([worker.php](app/Cron/worker.php)).

### Related Components
- [ChildServiceManager.php](app/Class%20files/Services/ChildServiceManager.php)
- [worker.php](app/Cron/worker.php)

---

## Idea: Action Tracker Event Dictionary
### Description
Generate user-friendly titles and contextual descriptions for events in the UI.

### Ground Running tips
- Create a dictionary class or helper (e.g. `EventDictionary`) mapping event names to templates. 
- Use a basic regex template engine (e.g. `str_replace` or placeholder replacement) to inject values like username, ID, and action names into the titles.

### Metrics (Qualities & Quantities)
- **Complexity**: Low
- **Risk Level**: Low
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~2

### Prerequisites
- Understanding of event payloads and JSON parsing in PHP.

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [action-tracker.js](app/Static/action-tracker.js)

---

## Idea: Client side site tools
### Description
Implement code resuse to design fit for purpose client side tools, fetch requests, session status, and other appropriate client side mechanisms should live here and be reused by the other existing Javascript.

### Ground Running tips
- Author a global namespace or modular ES class (e.g. `SiteTools`) that intercepts all requests to inject CSRF tokens in headers and monitor session validity periodically via a `/session/status` JSON endpoint.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low-Medium
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Prerequisites
- Modern ES6 JavaScript.
- Front-end DOM integration knowledge.

### Related Components
- [validator.js](app/Static/validator.js)
- [behaviour.js](app/Static/behaviour.js)

---

## Logic Blocked: Docker out of Docker hurdle - cannot bind host directory to new volume for siblings
### Description
To be able to startup an instance from within the application's container, it must have visibility over the host's file system.

### Ground Running tips
- When mounting directories for child/sibling containers in a Docker-in-Docker setup, you must specify paths relative to the *host* filesystem, not the container filesystem, because the host Docker daemon performs the mounting.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: High
- **Estimated Time**: Unknown (Blocked)
- **Number of Files**: N/A

### Prerequisites
- Host filesystem visibility inside Docker.
- Sibling container volume mapping setup.

### Related Components
- [ChildServiceManager.php](app/Class%20files/Services/ChildServiceManager.php)

---

## Idea: Responsive FlexTable Stacked Cards
### Description
Address the table collapse of `FlexTableComponent` rendering to a stacked card layout on widths < 768px for mobile responsiveness.

### Ground Running tips
- Add HTML5 `data-label` attributes to table cells matching the header title. 
- In CSS under `@media (max-width: 768px)`, hide the normal headers (`.flex-table-header`), set rows to `flex-direction: column`, and display data-labels using `:before` pseudo-elements.

### Metrics (Qualities & Quantities)
- **Complexity**: Low
- **Risk Level**: Low
- **Estimated Time**: 1 Day
- **Number of Files**: ~2

### Prerequisites
- CSS Flexbox and media query rules.

### Related Components
- [FlexTableComponent.php](app/Class%20files/Components/FlexTableComponent.php)
- [styles.css](app/Static/styles.css)

---

## Idea: Content Export Engine (The Data Sovereign)
### Description
Provide comprehensive data portability to prevent vendor lock-in. Allow users to export their entire site's structured content (pages and elements) as a clean JSON package.

### Ground Running tips
- Extract and walk the nested page components recursively, dumping their attributes, styling classes, and structural relationships into a unified JSON format for full portability.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low
- **Estimated Time**: 2 Days
- **Number of Files**: ~2

### Prerequisites
- Dynamic layout rendering models.
- JSON parsing and structuring rules.

### Related Components
- [Component.php](app/Class%20files/Component.php)
- [PageController.php](app/Class%20files/Controllers/PageController.php)

---

## Idea: CRUD Role-Based Access Control (RBAC)
### Description
Introduce granular permissions to allow Operations Managers to delegate tasks securely. Different users will have distinct capabilities (e.g., Admin vs. Editor vs. Viewer).

### Ground Running tips
- Implement a clean middleware validation step checking roles stored on the logged-in user against requirements mapped in routes. 
- Render navbar items conditionally based on the user's role.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium-High
- **Risk Level**: High (Affects platform authorization safety)
- **Estimated Time**: 3-4 Days
- **Number of Files**: ~4

### Prerequisites
- Middleware routing filters.
- Relational user role database schema.

### Related Components
- [ExtranetMiddleware.php](app/Class%20files/Middleware/ExtranetMiddleware.php)
- [navbar.php](app/Class%20files/navbar.php)

---

## Idea: Headless Content API (The Headless Pioneer)
### Description
Expose the application's content structure via secure RESTful endpoints, allowing developers to pull structured content directly into external frontends, mobile applications, or static site generators.

### Ground Running tips
- Bypass standard layout wrapping in `ViewDecorationMiddleware` when routing paths match `/api/*`. 
- Support bearer tokens in the request authorization header.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Prerequisites
- Custom routing controllers.
- API authentication token validation.

### Related Components
- [Router.php](app/Class%20files/Router/Router.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Idea: Pagination Standardization
### Description
Pagination should be considered in a separate project. Implement a unified pagination standard for components like `FlexTableComponent` when handling large datasets.

### Ground Running tips
- Standardize pagination metadata structure (`page`, `limit`, `total_records`, `total_pages`) in raw data mapper.
- Pass pagination config objects directly to component rendering.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Low
- **Estimated Time**: 2 Days
- **Number of Files**: ~3

### Prerequisites
- Database limit/offset support in dialect mapping.

### Related Components
- [QueryBuilder.php](app/Class%20files/QueryBuilder.php)
- [FlexTableComponent.php](app/Class%20files/Components/FlexTableComponent.php)

---

## Idea: WAF & Rate Limiter Database Performance Optimization
### Description
`WafMiddleware` runs on every HTTP request and queries the `httpAction` and `banned_ips` tables to check for bans, rate-limit clients, and detect cookie rotation. Currently, these tables lack indexes on the columns used in query filtering (like `haIP`, `haDate`, and `biIP`), which forces the database to perform slow full-table scans on every page view. Furthermore, without log rotation, the table size will grow unbounded.

### Ground Running tips
- Apply compound indexes explicitly in `01_db_ddl.xml` so that they migrate correctly across all DBMS engines. 
- Schedule the log pruning task in the background worker (`worker.php`) to avoid slowing down HTTP request processing.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Medium (Modifying migration schema and middleware query performance)
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~3

### Prerequisites
- Database schema indexes configuration.
- Background worker execution flow.

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [WafMiddleware.php](app/Class%20files/Middleware/WafMiddleware.php)
- [worker.php](app/Cron/worker.php)

---

## Idea: Optimistic Concurrency Control (OCC) for Event Store
### Description
With background jobs executing event sourcing projections asynchronously, concurrently running commands or projections can cause race conditions or split-brain states where events are applied out-of-order, causing database corruption.

### Ground Running tips
- Verify that every event appended checking an aggregate root queries the latest event ID or aggregate version first. 
- Throw a descriptive custom validation exception when a version mismatch is encountered, allowing the caller controller to catch it and notify the client.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium-High
- **Risk Level**: High (Impacts write transaction flow and event logging)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Prerequisites
- Relational database schema modifications.
- Event serialization constraints.

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Idea: Centralized Exception Handling & System Integrity Dashboard
### Description
Currently, the system lacks a centralized uncaught exception handler (`set_exception_handler`). Custom exceptions (e.g., in background tasks like `worker.php` and Docker API commands) are handled fragmentedly or written to system log files (like `/var/log/action.log`) that are invisible to administrators. To prevent integrity failures from passing unnoticed, we need to:
1. Conduct a system-wide discovery to pinpoint failure-prone zones (database, network, docker shell invocations, and queue processors).
2. Establish a unified custom exception class hierarchy.
3. Enhance the `phpErrorLog` database log schema to capture stack traces, request context, and exception severity.
4. Upgrade the admin dashboard's Error Log UI to display these detailed stack traces and system health metrics.

### Ground Running tips
- Register a global exception handler in `errorHandler.php` using `set_exception_handler()`.
- Log the full exception stack trace (`$e->getTraceAsString()`) and context (IP, URI, inputs) to `phpErrorLog`.
- Create a dedicated UI panel or indicator on the admin screen that checks the count of fatal logs or unhandled exceptions in the last 24 hours, notifying administrators of any system integrity failures.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low-Medium (Centralizing error interception is highly safe and isolated)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~4

### Prerequisites
- Core understanding of PHP Exception hierarchies and standard error interception (`set_exception_handler`).
- Admin console routing and rendering layouts (Phase 6).

### Related Components
- [errorHandler.php](app/Class%20files/errorHandler.php)
- [ErrorLogController.php](app/Class%20files/Controllers/ErrorLogController.php)
- [error_log.php](app/views/management/error_log.php)
