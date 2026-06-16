# Roadmap

This document outlines the comprehensive roadmap for the next development milestones of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Review configuration variables [COMPLETED]
### Description
Establish a single source of truth for all application configuration variables and standardize the application's external network footprint.

### Implementation Goals
1. **Centralized Configuration Manager**: Consolidate scattered environment variables and hardcoded settings into a strict, typed `Config` class or standardized `config.php` registry.
2. **Port Normalization**: Transition the web application port from `8443` back to the standard HTTPS port `443`. This requires updating `docker-compose` files, Nginx configurations (`nginx.conf`), and replacing hardcoded references in the test suite (`xmlDomTest.php`, `test-suite.xml`).

---

## Phase 2: Implement tenant health check cron task [COMPLETED]
### Description
Ensure high availability and visibility of provisioned SaaS tenants by actively monitoring their operational status.

### Implementation Goals
1. **Health Check Daemon**: Create a background cron task (e.g., `health_check.php`) that periodically iterates over active tenants in `absChildServices`, pings their endpoints or queries the Docker socket, and records their latency/status.
2. **Dashboard Integration**: Update the `ChildServiceDataProvider` to display real-time visual badges (Healthy/Degraded/Offline) based on the latest cron execution.
---

## Phase 3: Action tracker Labels and description [COMPLETED]
### Description
Enhance the user auditability of the Event Sourcing architecture by generating human-readable labels and descriptions for events in the UI, logging full HTTP requests and JSON payloads into IndexedDB.

### Implementation Goals
1. **Reset History**: Clear the IndexedDB automatically when a user logs in or out.
2. **Label Enhancement**: Include the exact `[Date] <HTTP Method>: <HTTP path> <result>` in the history log, alongside the GoTo link.
3. **Payload Inspector**: Add an 'Inspect' link that opens an HTML `<dialog>` displaying the raw JSON payload and HTTP response.
4. **Anonymous Hidden**: Hide the action tracker component entirely for unauthenticated anonymous users.

---

## Phase 4: Enhance test suite - fill forms with random values [COMPLETED]
### Description
Make integration testing more robust by generating non-colliding entity names.

### Implementation Goals
1. **Random Token Injection**: Enhance `xmlDomTest.php` to parse `{{RANDOM_STR}}` or `{{UUID}}` tokens inside `<parameter>` values.
2. **State Tracking**: Automatically link the generated random strings to the `<saveSuiteVariable>` output, ensuring subsequent assertions look for the correct dynamically generated text.

---

## Phase 5: Change UI media queries for mobile [COMPLETED]
### Description
Improve the responsive layout of the management suite, specifically targeting flex-tables and block editor canvas constraints on smaller screens.

### Implementation Goals
1. **Centralized Media Queries**: All responsive CSS rules must strictly reside in `app/Static/styles.css` between lines 458-479, ensuring a clean separation of concerns and single source of truth for breakpoints.
2. **Sticky Hamburger Navigation**: At widths < 768px, the primary application menu (stage) should collapse behind a hamburger icon that remains fixed/sticky at the top of the viewport.
3. **Responsive Action Tracker**: At widths < 768px, the Action Tracker component should transform into a full-width, sticky footer, replacing its standard desktop layout.
4. **Sidebar Toggles**: Hide the Page Builder component sidebar behind a hamburger toggle on mobile devices.

---

## Phase 6: Federate naming convention for event store [COMPLETED]
### Description
Standardize the naming convention of the event store's database table and field names across the application.

### Implementation Goals
1. **Table Renaming**: Update the `event_store` table name to follow the application's standard format (e.g. `tblEventStore`).
2. **Column Renaming**: Update the field names inside the event store table to follow the application's column prefix convention (e.g. `evsPK`, `evsAggregateFK`, `evsEventType`, `evsPayload`, `evsStatus`).

## Phase 7: Implement tblNavBar and tblPage configuration fluidity [COMPLETED]
### Description
The integration between the Nav Bar CMS and the page block editor has been fully established.
- `pagSlug` was surgically removed from `tblPages` and the WYSIWYG block editor, embracing a solid OO approach.
- `tblNavBar` acts as the undisputed central Route Registry. Pages are now strictly content payloads.
- Routes are exclusively defined via the Navbar Management UI using the `nbPageFK` field to bind URLs to existing pages.

## Phase 8: DBMS Vendor Support
### Description
Abstract database interactions to support multiple DBMS engines.
- **SQLite** - AUTOINCREMENT or INTEGER PRIMARY KEY, Boolean = INTEGER (0/1), strftime('%H', haDate)
- **MS SQL** - auPK INT NOT NULL IDENTITY(1,1), Boolean = BIT, SELECT TOP(), IDENTITY(1,1), DATEPART(datepart, date)

---

## Phase 9: Infrastructure & Advanced Blocks
### Description
This phase is dedicated to building the necessary data abstraction and reusable presentation components that power advanced blocks like Forms, Tables, and Charts, ensuring these blocks are data-aware rather than just display-aware.

**Key Deliverable**: Implementation of the `GenericDataMapper` service and associated component scaffolding.
> [!IMPORTANT]
> **Ground Running**: The structure of `FlexTableComponent` and `FormComponent` must be updated to depend on data provided by a Mapper Service, which consumes raw DB results and returns a standardized array.

### Core Components to Implement
1.  **GenericDataMapper Service**: Abstract the difference between raw database result sets and structured key-value arrays.
2.  **Advanced Block Support**: 
    - **Table**: Pass data through Mapper before rendering.
    - **Form**: Convert database-ready structures into form field definitions.
    - **Chart**: Aggregate and pivot raw data into time-series/statistical formats.

---

## Phase 10: Update ./conf/*.sql scripts
### Description
The SQL scripts (`./conf/*.sql`) currently define the base schema and initial state for a tenant and the running database statically.

### Implementation Goals
Refactor these scripts to be dynamically generated and maintained:
1. **Event Architecture Integration**: Derive the database state definitions directly from the established event sourcing architecture, ensuring the schema and seed data remain synchronized with the core domain events.
2. **Automated Execution Task**: Create an automated execution task (e.g., a build script or CLI command) that compiles the latest event-derived states into the final `.sql` scripts, preventing manual drift and ensuring new tenants are always provisioned with the latest schema baseline.
