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
