# Phase 3: Implement Event Sourcing Mechanism (Source Code Draft)

This document details the architectural shift from traditional CRUD database interactions to an immutable Event Sourcing architecture.

## 1. Implementation Overview

To create a fully verifiable and reversible history of state changes within the application, direct `UPDATE` and `DELETE` SQL operations were deprecated in favor of appending structured event payloads to a central `event_store` table.

## 2. Core Enhancements

### `event_store` Table Schema
Defined in `./conf/common/01_db_ddl.sql`, the table strictly tracks chronological event progression.
- `id`: Auto-incrementing primary key.
- `aggregate_id`: The primary key of the targeted entity.
- `event_type`: The class/action of the event (e.g., `UserRegistered`, `FormDeleted`).
- `payload`: JSON encoded representation of the new state.
- `previous_payload`: JSON encoded representation of the state prior to the event (for Memento/Undo).
- `status`: Tracking state (`pending`, `processed`, `failed`).

### The `EventStore` Class
A centralized service (`./app/Class files/EventStore.php`) used by all application controllers to persist events.
```php
public function append(string $eventType, array $payload, ?int $aggregateId = null, ?int $userId = null, ?array $previousPayload = null, bool $isReversal = false, bool $preserveRedo = false): int {
    $ownsTransaction = false;
    if (!$this->db->inTransaction()) {
        $this->db->beginTransaction();
        $ownsTransaction = true;
    }
    
    try {
        $qb = new QueryBuilder($this->dialect);
        $sql = $qb->table('event_store')->insert([
            'aggregate_id'     => $aggregateId,
            'event_type'       => $eventType,
            'payload'          => json_encode($payload),
            'status'           => 'pending',
            // ...
        ]);
        // ...
        $this->db->commit();
    } catch (Exception $e) {
        $this->db->rollBack();
    }
}
```

## 3. Benefits & Outcomes
- **Auditability**: Complete chronological tracking of all data mutations across the platform.
- **Race Condition Safety**: The `EventStore` operates inside strict transaction boundaries.
- **State Reconstruction**: The entire database state can technically be dropped and re-materialized purely by replaying the event log in order.
