# Phase 4: Implement worker.php

This document describes the background job processor responsible for materializing events from the Event Store into relational database tables.

## 1. Implementation Overview

With Phase 3 introducing the `event_store` where controllers append `pending` events, an asynchronous consumer was required to read these events and apply their payloads to the underlying standard tables (`tblForm`, `appUsers`, etc.). This is handled by a background cron process: `worker.php`.

## 2. Core Enhancements

### Safe Concurrency via Locks
To allow multiple web requests and background workers to operate concurrently without data corruption, `worker.php` utilizes `FOR UPDATE SKIP LOCKED`.

```php
// In worker.php
$stmt = $db->prepare("
    SELECT id, payload, event_type 
    FROM event_store 
    WHERE status = 'pending' 
    ORDER BY id ASC 
    LIMIT 1 
    FOR UPDATE SKIP LOCKED
");
```
This safely reserves the top-most unhandled event. If another worker thread attempts to fetch an event simultaneously, it skips the locked row and grabs the subsequent one.

### Modular Event Handlers
Controllers explicitly register Event Handlers which dictate exactly how an event payload should manifest in the database.

```php
// Example Handler from EditAccountAction.php
'UserVerified' => function($payload, $db, $dialect) {
    $pk = (int)$payload['auPK'];
    $qb = new QueryBuilder($dialect);
    $sql = $qb->table('appUsers')->where('auPK', '=', $pk)->update(['verified' => 1]);
    $qb->doExecute($db, $sql);
}
```

### Docker Cron Integration
The worker is instantiated inside the core application container using standard linux `cron`, defined via `./conf/crontab` and integrated via the `Dockerfile`.

## 3. Benefits & Outcomes
- **Non-Blocking UI**: Expensive database mutation logic is deferred to the background.
- **Resilience**: If a handler fails, the event status is marked as `failed`, leaving a clear trace without disrupting the active web request.
- **Scalability**: The `SKIP LOCKED` architecture allows the background worker pool to be horizontally scaled if event throughput increases.
