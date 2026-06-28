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
- [errorHandler.php](app/Class files/errorHandler.php)
- [ErrorLogController.php](app/Class files/Controllers/ErrorLogController.php)
- [error_log.php](app/views/management/error_log.php)

---

## Idea: ERP Unified Database Schema & EERD Design
### Description
Before implementing the individual ERP modules, conduct a comprehensive data-modeling phase to produce an Enhanced Entity-Relationship Diagram (EERD). This mapping ensures all many-to-many associations (e.g., Products to multiple Vendors, Companies to multiple addresses/roles, and General Ledger double-entries) are structurally aligned, preventing schema drift and database migration conflicts in `01_db_ddl.xml`.

### Ground Running tips
- Model the primary many-to-many pivot tables:
  - `tblProductVendors` (handling vendor-specific SKU overrides, purchasing unit costs, and lead times).
  - `tblCompanyAddresses` (handling shipping, billing, and warehouse locations mapped to a single company).
  - `tblPurchaseOrderItems` (mapping POs to Products with ordered vs. received quantities).
- Create a visual database schema representation (using Mermaid syntax or an SVG layout) and store it in the documentation directory to guide all subsequent migrations.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium (Heavy design, minimal initial code)
- **Risk Level**: Low (High mitigation value for schema regression)
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~1 (Documentation / Diagram file)

### Prerequisites
- Understanding of the XML migration syntax ([01_db_ddl.xml](conf/common/01_db_ddl.xml)).

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Idea: ERP Product Management (Catalog & Inventory)
### Description
Add basic product management to support a catalog with dynamic attributes, stock tracking, and price lists. This will allow the platform to serve e-commerce or inventory management use cases, and prepares the data model for procurement processes.

### Ground Running tips
- Define `tblProducts` and `tblProductInventory` in the migration schema, utilizing the Event Store to track inventory changes (stock additions, audits, adjustments) as transactional events.
- Implement a dynamic attributes schema using the Entity-Attribute-Value (EAV) pattern (e.g., `tblProductAttributes` and `tblProductAttributeValues`) to support varying product configurations database-agnostically without rigid table schema alterations.
- Include a `pdDefaultVendorID` foreign key column linking products to companies (`tblCompanies`) to enable automated and manual purchase ordering paths.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium-High
- **Risk Level**: Low-Medium
- **Estimated Time**: 3-4 Days
- **Number of Files**: ~4

### Prerequisites
- Relational database schema migrations configuration ([01_db_ddl.xml](conf/common/01_db_ddl.xml)).
- Event Store for transactional auditing of stock levels ([EventStore.php](app/Class files/EventStore.php)).

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [EventStore.php](app/Class files/EventStore.php)
- [QueryBuilder.php](app/Class files/QueryBuilder.php)

---

## Idea: ERP Partner & Customer Management (The Party Pattern)
### Description
Introduce a unified business relationship/party management framework. Instead of segregating customers and vendors into isolated schemas, implement a consolidated companies table that supports dual-role partners (e.g., entities that are both suppliers and customers). This prepares the database schema for purchase orders, sales billing, and client/vendor portals.

### Ground Running tips
- Implement a central `tblCompanies` table containing common attributes (name, email, shipping/billing addresses, tax ID).
- Add boolean flags `coIsCustomer` and `coIsVendor` (or a `tblCompanyRoles` join table) along with a flexible JSON metadata column for role-specific settings (like credit limits for customers, or payment terms for vendors).
- Enforce security boundaries in `ExtranetMiddleware` to restrict customer logins from accessing vendor dashboards or core database administrations.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium (Involves user security and profile separation)
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~4

### Prerequisites
- User authentication and role boundaries ([ExtranetMiddleware.php](app/Class files/Middleware/ExtranetMiddleware.php)).
- Standardized listing layouts ([FlexTableComponent.php](app/Class files/Components/FlexTableComponent.php)).

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [ExtranetMiddleware.php](app/Class files/Middleware/ExtranetMiddleware.php)
- [FlexTableComponent.php](app/Class files/Components/FlexTableComponent.php)

---

## Idea: ERP Vendor Purchase Orders (PO)
### Description
Implement a procurement subsystem enabling administrators to issue Purchase Orders (POs) to suppliers (vendors). This tracks orders, updates product inventory when goods are received, and links directly to accounts payable.

### Ground Running tips
- Create `tblPurchaseOrders` and `tblPurchaseOrderItems` referencing `tblCompanies` (filtered for `coIsVendor = 1`) and `tblProducts`.
- Hook purchase receipt events into the Event Store (e.g., `GoodsReceivedEvent`) to trigger automatic stock level increments in `tblProductInventory` and log transaction history.
- Implement a simple approval workflow state (Draft -> Pending -> Approved -> Received).

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Low-Medium
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Prerequisites
- Partner database schema (`tblCompanies`) from the Party Pattern idea.
- Product inventory database schema (`tblProductInventory`).
- Event Store workflow support ([EventStore.php](app/Class files/EventStore.php)).

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [EventStore.php](app/Class files/EventStore.php)
- [QueryBuilder.php](app/Class files/QueryBuilder.php)

---

## Idea: ERP Double-Entry Accounting General Ledger (GL)
### Description
Introduce a double-entry accounting General Ledger (GL) to record financial transactions. This provides a single source of truth for the platform's financial health, compiling journals from customer sales invoices and vendor purchase orders into ledger entries.

### Ground Running tips
- Establish a Chart of Accounts (`tblGLAccounts`) containing Asset, Liability, Equity, Revenue, and Expense accounts.
- Implement a transaction journal table (`tblGLJournalEntries`) and a split lines table (`tblGLJournalLines`). Ensure that every entry enforces double-entry rules (total debits must exactly equal total credits).
- Generate ledger records automatically by subscribing background worker events to invoice creation, payment capture, and purchase receipts.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: High (Involves strict financial accuracy and auditing)
- **Estimated Time**: 4-5 Days
- **Number of Files**: ~5

### Prerequisites
- Standardized database migrations ([01_db_ddl.xml](conf/common/01_db_ddl.xml)).
- Event Store auditing and background execution flow ([EventStore.php](app/Class files/EventStore.php), [worker.php](app/Cron/worker.php)).

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [EventStore.php](app/Class files/EventStore.php)
- [worker.php](app/Cron/worker.php)
