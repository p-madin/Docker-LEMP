# Phase 6: Platform Recovery User Interface

This document describes the administrative interface used to inspect the raw Event Sourcing logs and execute point-in-time database restorations.

## 1. Implementation Overview

To capitalise on the auditability of the Event Sourcing architecture, a new management view was constructed using internal DOM components. This interface allows system administrators to monitor real-time event ingestion and safely trigger full platform replays.

## 2. Core Enhancements

### Event Log Interface
The `PlatformRecoveryController.php` utilizes `FlexTableComponent` to render the immutable ledger of system state changes directly to the UI.

```php
$flexTable = new FlexTableComponent($dom);
$flexTable->setColumns([
    ['key' => 'id', 'label' => 'Event ID', 'isAction' => false],
    ['key' => 'event_type', 'label' => 'Event Type', 'isAction' => false],
    ['key' => 'aggregate_id', 'label' => 'Aggregate PK', 'isAction' => false],
    ['key' => 'status', 'label' => 'Worker Status', 'isAction' => false]
]);
```

### Point-In-Time Replay Triggers
Administrators are provided with an input form to select a target `datetime`. Upon submission, a special `PlatformRecoveryReplay` event is appended to the `event_store`.

```php
$eventStore->append('PlatformRecoveryReplay', ['target_time' => $targetTime], null, $authorId);
```

When `worker.php` encounters a `PlatformRecoveryReplay` event, it executes a specialized handler:
1. **Truncates** all material relational tables (`tblForm`, `appUsers`, etc.).
2. **Replays** the chronological `event_store` from ID 1 up to the exact timestamp specified in the payload.

## 3. Benefits & Outcomes
- **Transparency**: Administrators have full visibility into the background asynchronous workers and the raw system state.
- **Disaster Recovery**: Catastrophic database corruption or malicious manipulation can be instantly reverted to a known good state without relying on external SQL dump backups.
