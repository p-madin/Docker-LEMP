# Roadmap

This document outlines the comprehensive roadmap for the next development milestones of the Docker-LEMP project. Each phase is designed to be a self-contained unit of work that an intelligent agent can implement with minimal supervision.

---

## Phase 1: Schema Dictionary for PostgreSQL & MSSQL Case-Sensitivity
### Description
Address the limitation where PostgreSQL folds unquoted identifiers to lowercase and where ANSIStandardDialect forces columns to lowercase, destroying camelCase name mappings.

### Implementation Goals
1. **XML Parser Integration**: Parse database schema definitions directly from `01_db_ddl.xml` as a case-preserving single source of truth.
2. **Key Mapping Interceptor**: Intercept array keys in `QueryBuilder::executeFetch` and `executeFetchAll` when using PostgreSQL or MSSQL to map lowercase keys back to camelCase.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium
- **Risk Level**: Medium-High
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Related Components
- [QueryBuilder.php](app/Class%20files/QueryBuilder.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Phase 2: Pagination Standardization
### Description
Implement a unified pagination standard for components like `FlexTableComponent` when handling large datasets.

### Implementation Goals
1. **Metadata Structuring**: Standardize pagination metadata (`page`, `limit`, `total_records`, `total_pages`) in the raw data mapper.
2. **Dynamic Configuration Rendering**: Pass pagination configuration objects directly to the component rendering process.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Low
- **Estimated Time**: 2 Days
- **Number of Files**: ~3

### Related Components
- [QueryBuilder.php](app/Class%20files/QueryBuilder.php)
- [FlexTableComponent.php](app/Class%20files/Components/FlexTableComponent.php)

---

## Phase 3: WAF & Rate Limiter Database Performance Optimization
### Description
Optimize middleware performance for `WafMiddleware` queries on database tables, preventing full-table scans and managing log rotation.

### Implementation Goals
1. **Compound Indexes**: Apply compound indexes explicitly in `01_db_ddl.xml` so that they migrate correctly across all database engines.
2. **Pruning Cron Jobs**: Schedule a log pruning task in the background worker (`worker.php`) to prune old request records.

### Metrics (Qualities & Quantities)
- **Complexity**: Low-Medium
- **Risk Level**: Medium
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~3

### Related Components
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)
- [WafMiddleware.php](app/Class%20files/Middleware/WafMiddleware.php)
- [worker.php](app/Cron/worker.php)

---

## Phase 4: Event Sourcing Snapshots & Compaction
### Description
Prevent the `event_store` replay sequence from taking an unacceptably long time as the application ages. To maintain architectural consistency, snapshot state payloads will be serialized in the standard XML structure used for seed data rather than JSON.

### Implementation Goals
1. **XML Snapshots Table**: Create `tblSnapshots` to store snapshot state serialized as XML string payloads conforming to the `<data><table><row>...` schema found in `02_db_dml.xml`.
2. **Reusing DML Parsers**: Reuse the existing XML processing mechanisms (such as `XMLMigrator`) to parse and load the snapshot state directly back into the database table instances.
3. **Selective Event Replay**: Retrieve the latest state snapshot and its last processed event ID, instructing the replayer to read only events with `evsPK > snapshot.last_event_id` to compile the final state.

### Metrics (Qualities & Quantities)
- **Complexity**: High
- **Risk Level**: Medium
- **Estimated Time**: 3-4 Days
- **Number of Files**: ~3

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [worker.php](app/Cron/worker.php)
- [XMLMigrator.php](app/Class%20files/Services/XMLMigrator.php)
- [02_db_dml.xml](conf/common/02_db_dml.xml)

---

## Phase 5: Optimistic Concurrency Control (OCC) for Event Store
### Description
Prevent race conditions or split-brain states where events are applied out-of-order during concurrent operations or background execution, causing database corruption.

### Implementation Goals
1. **Aggregate Version Validation**: Verify that every event appended checks the aggregate root's latest event ID or version count first.
2. **Custom Mismatch Exception**: Throw a descriptive validation exception on version mismatch, allowing the caller controller to catch it and notify the client.

### Metrics (Qualities & Quantities)
- **Complexity**: Medium-High
- **Risk Level**: High
- **Estimated Time**: 2-3 Days
- **Number of Files**: ~3

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [01_db_ddl.xml](conf/common/01_db_ddl.xml)

---

## Phase 6: Action Tracker Event Dictionary
### Description
Generate user-friendly titles and contextual descriptions for events in the UI.

### Implementation Goals
1. **EventDictionary Mapping**: Create a dictionary helper mapping raw event names to template descriptions.
2. **Dynamic Replacements**: Implement placeholder replacement to inject values like username, ID, and actions dynamically into the UI labels.

### Metrics (Qualities & Quantities)
- **Complexity**: Low
- **Risk Level**: Low
- **Estimated Time**: 1-2 Days
- **Number of Files**: ~2

### Related Components
- [EventStore.php](app/Class%20files/EventStore.php)
- [action-tracker.js](app/Static/action-tracker.js)
