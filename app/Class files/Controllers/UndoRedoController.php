<?php
class UndoRedoController implements ControllerInterface {
    public static string $path = '/undo';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $eventStore;

        $userId = $sessionController->getSystemUserId();

        $mode = $request->post['mode'] ?? 'undo';
        $currentEventId = $sessionController->getPrimary('current_event_id');

        if ($mode === 'undo') {
            if ($currentEventId) {
                $targetEvent = $this->getEventById($currentEventId);
                if ($targetEvent) {
                    // 1. Move playhead to predecessor
                    $sessionController->setPrimary('current_event_id', $targetEvent['predecessor_id']);
                    
                    // 2. Add to redo stack
                    $eventStore->pushToRedoStack($sessionController, (int)$targetEvent['id']);

                    // 3. Apply the undo (append reversal)
                    $newEventId = $this->applyEventState($targetEvent, $userId, true); 
                    if ($newEventId) {
                        $eventStore->waitUntilProcessed($newEventId);
                    }
                    $msg = "Undone: " . $targetEvent['event_type'];
                }
            } else {
                $msg = "No actions to undo";
            }
        } elseif ($mode === 'redo') {
            $targetEventId = $eventStore->popFromRedoStackId($sessionController);
            
            if ($targetEventId) {
                
                $targetEvent = $this->getEventById($targetEventId);
                if ($targetEvent) {
                    // Redo = re-apply original, marked as a normal action but preserving the stack
                    $newEventId = $this->applyEventState($targetEvent, $userId, false, true); 
                    if ($newEventId) {
                        $eventStore->waitUntilProcessed($newEventId);
                    }
                    $msg = "Redone: " . $targetEvent['event_type'];
                }
            } else {
                $msg = "Nothing to redo";
            }
        }

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['success' => true, 'message' => $msg ?? 'Done']);
            exit;
        }

        header("Location: " . ($request->server['HTTP_REFERER'] ?? '/dashboard'));
        exit;
    }

    private function getLatestEventId(int $userId): ?int {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $sql = $qb->table('event_store')
                  ->where('user_id', '=', $userId)
                  ->where('status', '=', 'processed')
                  ->orderBy('id', 'DESC')
                  ->limit(1)
                  ->toSQL();
        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ? (int)$res['id'] : null;
    }

    private function getEventById(int $id): ?array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        $sql = $qb->table('event_store')->where('id', '=', $id)->toSQL();
        $stmt = $db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    private function applyEventState(array $event, int $userId, bool $isUndo, bool $isRedo = false): ?int {
        global $db, $dialect, $sessionController, $eventStore;
        
        $eventType = $event['event_type'];
        $payload = json_decode($event['payload'], true);
        $previousPayload = json_decode($event['previous_payload'] ?? 'null', true);
        $aggregateId = $event['aggregate_id'];

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
            $targetPayload['original_event_id'] = $event['id'];
            // For Redo, we treat it as a normal action (is_reversal=false) but preserve the redo stack
            $isReversal = $isUndo;
            $preserveRedo = $isRedo;
            
            // Critical: If we are redoing an event, we MUST preserve the previous_payload 
            // so that the new event can itself be undone later.
            $targetPreviousPayload = $isUndo ? null : $previousPayload;
            
            return $eventStore->append($targetEventType, $targetPayload, $aggregateId, $userId, $targetPreviousPayload, $isReversal, $preserveRedo);
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
}
?>
