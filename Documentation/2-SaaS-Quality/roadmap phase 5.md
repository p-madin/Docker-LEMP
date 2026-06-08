# Phase 5: Event Sourcing Command & Memento Pattern (Source Code Draft)

This document describes the implementation of the Undo/Redo architecture, leveraging the immutable Event Sourcing logs to seamlessly step back and forward through application state.

## 1. Implementation Overview

Because every data mutation was refactored in Phase 3 to emit an Event rather than applying destructive SQL updates, the application gains the ability to "rewind" state. This is achieved by appending inverse "compensating events" to the Event Store via the `UndoRedoController`.

## 2. Core Enhancements

### Memento State Capture
When an entity is updated or deleted, the Action Controller fetches the *current* database state prior to appending the new event. This old state is stored in the `previous_payload` column of the `event_store` table.

```php
// In EditFormAction.php
$oldData = $qb_old->table('tblForm')->where('tfPK', '=', $pk)->getFetch($db);
$previousPayload = is_array($oldData) ? $oldData : null;

$eventId = $eventStore->append('FormUpdated', $newData, $pk, $authorId, $previousPayload);
```

### Undo and Redo Playheads
The `SessionController` tracks the user's chronological location within their event stream using two session variables:
1. `current_event_id`: The ID of the last event the user executed.
2. `redo_stack`: An array of event IDs that the user has Undone, making them eligible for Redo.

### Compensating Events
When the `UndoRedoController` is invoked, it looks up the `current_event_id` and dispatches a compensating event to the `EventStore`.

```php
// Example: Reversing an update
if ($lastEvent['event_type'] === 'FormUpdated') {
    // We use the previous_payload as the new active payload
    $compensatingPayload = json_decode($lastEvent['previous_payload'], true);
    
    // An explicit 'is_reversal' flag marks this as an undo action
    $eventStore->append('FormUpdated', $compensatingPayload, ..., true);
}
```

## 3. Benefits & Outcomes
- **Zero-Data Loss**: Users can freely undo destructive actions (like accidental deletions) with a single click.
- **Stateless Reversal**: Because compensating events are explicitly appended to the end of the `event_store` log (rather than deleting rows from the log), the system maintains a perfect, immutable audit trail of the user's mistakes and corrections.
