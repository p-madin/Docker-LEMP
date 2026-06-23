<?php
class EventStore {
    private PDO $db;
    private DatabaseDialect $dialect;

    public function __construct(PDO $db, DatabaseDialect $dialect) {
        $this->db = $db;
        $this->dialect = $dialect;
    }

    public function append(string $eventType, array $payload, ?int $aggregateId = null, ?int $userId = null, ?array $previousPayload = null, bool $isReversal = false, bool $preserveRedo = false): int {
        global $sessionController;

        $ownsTransaction = $this->dialect->beginTransaction($this->db);

        try {
            $predecessorId = isset($sessionController) ? $sessionController->getPrimary('current_event_id') : null;

            $qb = new QueryBuilder($this->dialect);
            $sql = $qb->table('tblEventStore')->insert([
                'evsAggregateFK'     => $aggregateId,
                'evsEventType'       => $eventType,
                'evsPayload'         => json_encode($payload),
                'evsPreviousPayload' => $previousPayload ? json_encode($previousPayload) : null,
                'evsStatus'          => 'pending',
                'evsUserFK'          => $userId,
                'evsIsReversal'      => $isReversal ? 1 : 0,
                'evsPredecessorFK'   => $predecessorId
            ]);

            $stmt = $this->db->prepare($sql);
            $qb->bindTo($stmt);
            $stmt->execute();
            
            $eventId = (int)$this->db->lastInsertId();

            // Track this event in the session
            if (isset($sessionController)) {
                $sessionController->setPrimary('pending_event_id', $eventId);
                
                // Only update the logical playhead and clear the redo stack if this is a fresh action
                if (!$isReversal) {
                    $sessionController->setPrimary('current_event_id', $eventId);
                    if (!$preserveRedo) {
                        $this->clearRedoStack($sessionController);
                    }
                }
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }

            return $eventId;
        } catch (Exception $e) {
            if ($ownsTransaction) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function pushToRedoStack($sessionController, int $eventId): void {
        $stack = $sessionController->getPrimary('redo_stack') ?: [];
        if (!is_array($stack)) $stack = [];
        $stack[] = $eventId;
        $sessionController->setPrimary('redo_stack', $stack);
    }

    public function popFromRedoStackId($sessionController): ?int {
        $stack = $sessionController->getPrimary('redo_stack') ?: [];
        if (!is_array($stack) || empty($stack)) return null;
        $eventId = array_pop($stack);
        $sessionController->setPrimary('redo_stack', $stack);
        return (int)$eventId;
    }

    public function clearRedoStack($sessionController): void {
        $sessionController->setPrimary('redo_stack', []);
    }

    /**
     * Polls the database until the specified event is no longer pending.
     * Used to bridge the gap between async worker processing and synchronous UI redirects.
     */
    public function waitUntilProcessed(int $eventId, int $timeoutSeconds = 7): bool {
        $start = time();
        while (time() - $start < $timeoutSeconds) {
            $qb = new QueryBuilder($this->dialect);
            // We use a fresh query to avoid PDO caching issues if any
            $event = $qb->table('tblEventStore')->select(['evsStatus'])->where('evsPK', '=', $eventId)->executeFetch($this->db);

            if ($event && ($event['evsStatus'] === 'processed' || $event['evsStatus'] === 'failed')) {
                return $event['evsStatus'] === 'processed';
            }
            // Sleep for 200ms before polling again
            usleep(200000);
        }
        return false;
    }

    /**
     * Returns the list of original (non-reversal) processed events in the active branch.
     */
    public function getUndoableEvents($sessionController): array {
        $userId = $sessionController->getSystemUserId();
        $playheadId = $sessionController->getPrimary('current_event_id');
        $qb = new QueryBuilder($this->dialect);
        $allEvents = $qb->table('tblEventStore')
                  ->where('evsUserFK', '=', $userId)
                  ->where('evsStatus', '=', 'processed')
                  ->executeFetchAll($this->db);

        if (empty($allEvents) || !$playheadId) return [];

        // Build a lookup table for fast traversal
        $lookup = [];
        foreach ($allEvents as $e) {
            $lookup[(int)$e['evsPK']] = $e;
        }

        $path = [];
        $currentId = $playheadId;

        // Traverse backwards along the predecessor chain
        while ($currentId && isset($lookup[$currentId])) {
            $e = $lookup[$currentId];
            if ((int)$e['evsIsReversal'] === 0) {
                $path[] = $e;
            }
            $currentId = $e['evsPredecessorFK'] ? (int)$e['evsPredecessorFK'] : null;
        }

        return $path;
    }

    public function getRedoableEvents($sessionController): array {
        $userId = $sessionController->getSystemUserId();
        $redoStack = $sessionController->getPrimary('redo_stack') ?: [];
        if (empty($redoStack)) return [];

        $qb = new QueryBuilder($this->dialect);
        $allEvents = $qb->table('tblEventStore')
                    ->whereIn('evsPK', $redoStack)
                    ->orderBy('evsPK', 'DESC')
                  ->executeFetchAll($this->db);
        return $allEvents;
    }

    public function getPendingEvents(int $limit = 1): array {
        $qb = new QueryBuilder($this->dialect);
        return $qb->table('tblEventStore')
                  ->where('evsStatus', '=', 'pending')
                  ->orderBy('evsPK', 'ASC')
                  ->limit($limit)
                  ->executeFetchAll($this->db, \PDO::FETCH_BOTH);
    }

    public function createReversalEvent(array $event, int $userId, bool $isUndo, bool $isRedo = false): ?int {
        $eventType = $event['evsEventType'];
        $payload = json_decode($event['evsPayload'], true);
        $previousPayload = json_decode($event['evsPreviousPayload'] ?? 'null', true);
        $aggregateId = $event['evsAggregateFK'];

        $targetEventType = $eventType;
        $targetPayload = [];

        if ($isUndo) {
            if (strpos($eventType, 'Updated') !== false) {
                $targetPayload = $previousPayload;
            } elseif (strpos($eventType, 'Created') !== false) {
                $targetEventType = str_replace('Created', 'Deleted', $eventType);
                $targetPayload = $payload;
                $pkField = $this->getPkFieldForEvent($eventType);
                if ($pkField) $targetPayload[$pkField] = $aggregateId;
            } elseif (strpos($eventType, 'Deleted') !== false) {
                $targetEventType = str_replace('Deleted', 'Created', $eventType);
                $targetPayload = $payload;
            }
        } else {
            // Redo = Re-apply original payload
            if (strpos($eventType, 'Deleted') !== false) {
                $targetEventType = $eventType; // A Redo of a Delete IS a Delete
            } elseif (strpos($eventType, 'Created') !== false) {
                $targetEventType = $eventType; 
                // Restore original ID if we have it
                $pkField = $this->getPkFieldForEvent($eventType);
                if ($pkField && $aggregateId) {
                    $targetPayload[$pkField] = $aggregateId;
                }
            }
            $targetPayload = array_merge($targetPayload, $payload);
        }

        if ($targetPayload) {
            $targetPayload['original_event_id'] = $event['evsPK'];
            // For Redo, we treat it as a normal action (is_reversal=false) but preserve the redo stack
            $isReversal = $isUndo;
            $preserveRedo = $isRedo;
            
            // Critical: If we are redoing an event, we MUST preserve the previous_payload 
            // so that the new event can itself be undone later.
            $targetPreviousPayload = $isUndo ? null : $previousPayload;
            
            return $this->append($targetEventType, $targetPayload, $aggregateId, $userId, $targetPreviousPayload, $isReversal, $preserveRedo);
        }
        return null;
    }

    private function getPkFieldForEvent(string $eventType): ?string {
        $map = [
            'NavbarItemCreated' => 'nbPK',
            'ColumnCreated'     => 'tcPK',
            'FormCreated'       => 'tfPK',
            'UserCreated'       => 'auPK',
        ];
        return $map[$eventType] ?? null;
    }

    /**
     * Retrieves the aggregate_id assigned to an event after it has been processed.
     */
    public function getAggregateId(int $eventId): ?int {
        $qb = new QueryBuilder($this->dialect);
        $event = $qb->table('tblEventStore')->select(['evsAggregateFK'])->where('evsPK', '=', $eventId)->executeFetch($this->db);
        return ($event && isset($event['evsAggregateFK'])) ? (int)$event['evsAggregateFK'] : null;
    }
}
?>
