# Phase 6: Federate naming convention for event store

## Description
Standardize the naming convention of the event store's database table and field names across the application. The goal was to align the `event_store` with the `tbl[Name]` and `[prefix][Column]` convention used natively across the app.

## Implementation Goals
1. **Table Renaming**: Update the `event_store` table name to follow the application's standard format (e.g. `tblEventStore`).
2. **Column Renaming**: Update the field names inside the event store table to follow the application's column prefix convention (e.g. `evsPK`, `evsAggregateFK`, `evsEventType`, `evsPayload`, `evsStatus`).

## Phase Round Up (Completed)
This phase successfully synchronized the event sourcing mechanism with the global database schema format. The following changes were implemented:
- **Database Schemas**: Updated `conf\common\01_db_ddl.sql` and `postgres conf\common\01_db_ddl.sql` to formally recreate `event_store` as `tblEventStore` with strict `evs`-prefixed column names.
- **Core Domain Handlers**: Updated `EventStore.php` query builders and array mappings to correctly serialize payload data into `evsPayload`, `evsAggregateFK`, and fetch identifiers via `evsPK`.
- **Background Worker Processing**: Re-aligned the raw SQL statements in the background `worker.php` daemon to pull directly from `tblEventStore` using the newly named columns.
- **Session & Recovery Interfaces**: Adjusted the session playhead tracker to use `evsPK`, and updated the `PlatformRecoveryController`, `UndoRedoController`, and DataProviders so the user interface accurately renders `evsEventType` text strings.

*Phase 6 is officially complete.*
