## Phase 8: Event Sourcing Snapshots & Compaction
### Description
Prevent the `event_store` replay sequence from taking an unacceptably long time as the application ages.

### Implementation Goals
1. **Snapshot Engine**: Periodically capture the complete JSON state of an aggregate root (like a Page or Form) and append a `SnapshotCreated` event.
2. **Fast-Forward Replay**: Modify `worker.php`'s replay logic (during Platform Recovery) to begin replaying from the most recent Snapshot rather than Event ID 1.

---

## Phase 9: CMS Draft & Publish Workflow
### Description
Address the limitation where all Page Builder edits are instantly pushed live.

### Implementation Goals
1. **State Flags**: Add a `status` (Draft, Published, Archived) column to `tblPages`.
2. **Preview Mode**: Create an isolated rendering route (`/preview?id=X`) that bypasses the "Published" filter for authenticated administrators.

---

## Idea: The Schema Dictionary for PostgreSQL Case-Sensitivity
### Description
Address the limitation where PostgreSQL natively folds unquoted identifiers to lowercase, which destroys camelCase column names (e.g., `nbPK` becomes `nbpk`) when using wildcard `SELECT *` queries. This breaks Memento JSON payloads that require exact case matching.

### Implementation Goals
1. **Schema Parser**: Create a utility script that parses `01_db_ddl.sql` and builds an array mapping lowercase keys back to their original camelCase strings (e.g., `['nbpk' => 'nbPK']`).
2. **QueryBuilder Interception**: Update `QueryBuilder::getFetch()` and `getFetchAll()` to automatically run the PDO result array through this dictionary map before returning it to the application.
3. **Strict Parameter Validation**: Expose the dictionary to the `Validator` class and API endpoints. This provides a definitive whitelist of valid database tables and columns, allowing the system to strictly validate any dynamically POSTed table or column parameters (e.g., for sorting, filtering, or dynamic data ingestion).
4. **Outcome**: 
   - Allows developers to safely use `SELECT *` without worrying about database-engine level case normalisation stripping expected keys.
   - Drastically improves security by preventing SQL injection and arbitrary schema traversal on endpoints that accept dynamic table or column references.

---

## Idea: Robust Container Orchestration
### Description
The current `ChildServiceManager` relies heavily on brittle shell execution (`shell_exec`), direct file manipulation, and blocking delays (`sleep(3)`). This subsystem should be modernised for reliability and scalability.

### Implementation Goals
1. **Direct Docker API Integration**: Deprecate `shell_exec('docker-compose ...')`. Utilise PHP's cURL extension to communicate directly with the Docker Engine API via `/var/run/docker.sock`. This provides structured JSON responses, eliminates shell-escaping vulnerabilities, and allows for robust error handling.
2. **Asynchronous Provisioning Queues**: Move the tenant creation logic (which is inherently slow) into a background Job Queue managed by `worker.php`. This will allow the main web server thread to return instantly, enabling the UI to display a non-blocking "Provisioning in progress..." state.
3. **Dedicated Templating Engine**: Replace manual string concatenation for configuration files (`nginx.conf`, `compose.yaml`, `tenant_admin.sql`) with dedicated `.stub` or `.tpl` template files stored in the `/conf/` directory, adhering to the separation of concerns principle.

---

## Idea: Action Tracker Event Dictionary
### Description
Generate user-friendly titles and contextual descriptions for events in the UI.

### Implementation Goals
1. **Event Dictionary**: Create a localized dictionary mapping raw `event_type`s (e.g., `FormCreated`) to user-friendly titles (e.g., "Created a Form").
2. **Payload Parsing**: Dynamically parse the event JSON payload to generate contextual descriptions (e.g., "User 'john_doe' was banned").

---

## Idea: Client side site tools
### Description
Implement code resuse to design fit for purpose client side tools, fetch requests, session status, and other appropriate client side mechanisms should live here and be reused by the other existing Javascript.

### Implementation Goals
1. **Site Tools**: A suite of tools to be used by the other existing Javascript files.
2. **Fetch**: A fit for purpose fetch requests tool, with auto CSRF token handling and other appropriate mechanisms.
3. **Session Status**: A fit for purpose session status tool, with auto session refresh, redirect to login and other appropriate mechanisms.
4. **xmlDom Client Side**: A javascript equivalent of ./app/Class files/xmlDom.php
5. **Other**: Other appropriate client side mechanisms.

## Logic Blocked: Docker out of Docker hurdle - cannot bind host directory to new volume for siblings
### Description
To be able to startup an instance from within the application's container, it must have visibility over the host's file system.


---

## Idea: Responsive FlexTable Stacked Cards
### Description
Address the table collapse of `FlexTableComponent` rendering to a stacked card layout on widths < 768px for mobile responsiveness.
### Implementation Goals
1. Convert `.flex-table` row elements to use `flex-direction: column`.
2. Hide table headers and append data-labels to cells for stacked contexts.

---

## Idea: Content Export Engine (The Data Sovereign)
### Description
Provide comprehensive data portability to prevent vendor lock-in. Allow users to export their entire site's structured content (pages and elements) as a clean JSON package.

### Implementation Goals
1. **JSON Export Action**: Create a backend action that aggregates data from `tblPages` and `tblElements` into a structured, nested JSON tree.
2. **Management UI**: Add an "Export Content" button to the Page Management dashboard that triggers the JSON download.

---

## Idea: CRUD Role-Based Access Control (RBAC)
### Description
Introduce granular permissions to allow Operations Managers to delegate tasks securely. Different users will have distinct capabilities (e.g., Admin vs. Editor vs. Viewer).

### Implementation Goals
1. **Database Schema**: Add a `role` column to `appUsers` or create distinct `tblRoles` and `tblPermissions` tables for more complex mapping.
2. **Middleware Authorization**: Implement a routing middleware that intercepts requests to `nbProtected` routes and verifies the user's role against the required capability.
3. **UI Adjustments**: Update the Navbar and Page Builder to hide restricted elements or tools from users lacking the appropriate permissions.

---

## Idea: Headless Content API (The Headless Pioneer)
### Description
Expose the application's content structure via secure RESTful endpoints, allowing developers to pull structured content directly into external frontends, mobile applications, or static site generators.

### Implementation Goals
1. **API Router**: Create an API-specific route handler (e.g., `/api/v1/*`) that bypasses the standard HTML view rendering and exclusively outputs JSON.
2. **Page Content Endpoint**: Implement an endpoint (e.g., `GET /api/v1/pages/{id}`) that queries `tblPages` and `tblElements` and recursively returns the nested JSON tree representing the requested page.
3. **API Authentication**: Introduce API keys or Bearer token support so that secure or restricted extranet content can be safely queried by authorized external systems without using browser session cookies.

