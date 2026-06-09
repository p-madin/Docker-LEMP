# Roadmap

This document outlines the comprehensive roadmap for the next development milestones of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Review configuration variables
### Description
Establish a single source of truth for all application configuration variables and standardize the application's external network footprint.

### Implementation Goals
1. **Centralized Configuration Manager**: Consolidate scattered environment variables and hardcoded settings into a strict, typed `Config` class or standardized `config.php` registry.
2. **Port Normalization**: Transition the web application port from `8443` back to the standard HTTPS port `443`. This requires updating `docker-compose` files, Nginx configurations (`nginx.conf`), and replacing hardcoded references in the test suite (`xmlDomTest.php`, `test-suite.xml`).

---

## Phase 2: Implement tenant health check configuration user interface and cron task
### Description
Ensure high availability and visibility of provisioned SaaS tenants by actively monitoring their operational status and exposing these metrics to administrators.

### Implementation Goals
1. **Health Check Daemon**: Create a background cron task (e.g., `health_check.php`) that periodically iterates over active tenants in `absChildServices`, pings their endpoints or queries the Docker socket, and records their latency/status.
2. **Configuration Interface**: Build a management UI (via the Page Builder/Forms engine) to allow administrators to define health check intervals, timeout thresholds, and alert policies.
3. **Dashboard Integration**: Update the `ChildServiceDataProvider` to display real-time visual badges (Healthy/Degraded/Offline) based on the latest cron execution.
---

## Phase 3: Action tracker Labels and description
### Description
Enhance the user auditability of the Event Sourcing architecture by generating human-readable labels and descriptions for events in the UI.

### Implementation Goals
1. **Event Dictionary**: Create a localized dictionary mapping raw `event_type`s (e.g., `FormCreated`) to user-friendly titles (e.g., "Created a Form").
2. **Payload Parsing**: Dynamically parse the event JSON payload to generate contextual descriptions (e.g., "User 'john_doe' was banned").

---

## Phase 4: Enhance test suite - fill forms with random values
### Description
Make integration testing more robust by generating non-colliding entity names.

### Implementation Goals
1. **Random Token Injection**: Enhance `xmlDomTest.php` to parse `{{RANDOM_STR}}` or `{{UUID}}` tokens inside `<parameter>` values.
2. **State Tracking**: Automatically link the generated random strings to the `<saveSuiteVariable>` output, ensuring subsequent assertions look for the correct dynamically generated text.

---

## Phase 5: Change UI media queries for mobile
### Description
Improve the responsive layout of the management suite, specifically targeting flex-tables and block editor canvas constraints on smaller screens.

### Implementation Goals
1. **Table Collapse**: Convert `FlexTableComponent` rendering to a stacked card layout on widths < 768px.
2. **Sidebar Toggles**: Hide the Page Builder component sidebar behind a hamburger toggle on mobile devices.

---

## Phase 6: Federate naming convention for event store
### Description
Standardize the naming convention of the event store's database table and field names across the application.

### Implementation Goals
1. **Table Renaming**: Update the `event_store` table name to follow the application's standard format (e.g. `tblEventStore`).
2. **Column Renaming**: Update the field names inside the event store table to follow the application's column prefix convention (e.g. `evsPK`, `evsAggregateFK`, `evsEventType`, `evsPayload`, `evsStatus`).


## Phase 7: DBMS Vendor Support
### Description
Abstract database interactions to support multiple DBMS engines.
- **SQLite** - AUTOINCREMENT or INTEGER PRIMARY KEY, Boolean = INTEGER (0/1), strftime('%H', haDate)
- **MS SQL** - auPK INT NOT NULL IDENTITY(1,1), Boolean = BIT, SELECT TOP(), IDENTITY(1,1), DATEPART(datepart, date)

---

## Phase 8: Infrastructure & Advanced Blocks
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

## Phase 9: Update ./conf/*.sql scripts
### Description
The SQL scripts (`./conf/*.sql`) currently define the base schema and initial state for a tenant and the running database statically.

### Implementation Goals
Refactor these scripts to be dynamically generated and maintained:
1. **Event Architecture Integration**: Derive the database state definitions directly from the established event sourcing architecture, ensuring the schema and seed data remain synchronized with the core domain events.
2. **Automated Execution Task**: Create an automated execution task (e.g., a build script or CLI command) that compiles the latest event-derived states into the final `.sql` scripts, preventing manual drift and ensuring new tenants are always provisioned with the latest schema baseline.
