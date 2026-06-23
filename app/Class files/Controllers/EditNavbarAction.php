<?php
class EditNavbarAction implements ControllerInterface {
    public static string $path = '/editNavbar';
    public static string $manage_URI = '/navbar_management';
    public static string $object_URI = '/edit_navbar';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

        $authorId = $sessionController->getSystemUserId();

        // Handle pure delete request
        if (isset($request->post['action']) && $request->post['action'] === 'delete') {
            $pk = (int)($request->post['nbPK'] ?? 0);
            if ($pk > 0) {
                // Fetch current data for undo support
                $qb_data = new QueryBuilder($dialect);
                $itemData = $qb_data->table('tblNavBar')->where('nbPK', '=', $pk)->executeFetch($db);
                
                if ($itemData) {
                    $eventId = $eventStore->append('NavbarItemDeleted', $itemData, $pk, $authorId);
                    if ($eventId) {
                        $eventStore->waitUntilProcessed($eventId);
                    }
                }
            }
            Hyperlink::redirection(self::$manage_URI);
        }

        $cleanData = FormValidation::processAndValidate('navbar', $request->post, $formSchemas, $sessionController, function($clean) {
            $id = $clean['nbPK'] ?? 0;
            return self::$object_URI . ($id ? "?id=".$id : "");
        });

        $isProtected = (isset($request->post['nbProtected']) && $request->post['nbProtected'] === '1') ? 1 : 0;
        $parentFK = (!empty($cleanData['nbParentFK'])) ? (int)$cleanData['nbParentFK'] : null;
        $pageFK = (!empty($cleanData['nbPageFK'])) ? (int)$cleanData['nbPageFK'] : null;

        $data = [
            'nbText'          => $cleanData['nbText'],
            'nbDiscriminator' => $cleanData['nbDiscriminator'],
            'nbPath'          => $cleanData['nbPath'],
            'nbOrder'         => (int)$cleanData['nbOrder'],
            'nbProtected'     => $isProtected,
            'nbParentFK'      => $parentFK,
            'nbPageFK'        => $pageFK
        ];

        $pk = (int)$cleanData['nbPK'];

        if ($pk > 0) {
            // Fetch current state for Memento support
            $qb_old = new QueryBuilder($dialect);
            $oldData = $qb_old->table('tblNavBar')->where('nbPK', '=', $pk)->executeFetch($db);
            $previousPayload = is_array($oldData) ? $oldData : null;
            $eventId = $eventStore->append('NavbarItemUpdated', array_merge(['nbPK' => $pk], $data), $pk, $authorId, $previousPayload);
        } else {
            $eventId = $eventStore->append('NavbarItemCreated', $data, null, $authorId);
        }

        $eventStore->waitUntilProcessed($eventId);
        $newId = $eventStore->getAggregateId($eventId);
        $dependency = $newId ? (int)$newId : $pk;

        Hyperlink::redirection(self::$object_URI . "?id=" . $dependency, $dependency);
    }

    public static function getEventHandlers(): array {
        return [
            'NavbarItemCreated' => function($payload, $db, $dialect) {
                unset($payload['original_event_id']);
                $hasExplicitId = isset($payload['nbPK']) && (int)$payload['nbPK'] > 0;
                
                if ($hasExplicitId && $dialect instanceof MSSQLDialect) {
                    $db->exec("SET IDENTITY_INSERT [tblNavBar] ON");
                }
                
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->insert($payload);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                
                if ($hasExplicitId && $dialect instanceof MSSQLDialect) {
                    $db->exec("SET IDENTITY_INSERT [tblNavBar] OFF");
                }
                
                return $hasExplicitId ? (int)$payload['nbPK'] : (int)$db->lastInsertId();
            },
            'NavbarItemUpdated' => function($payload, $db, $dialect) {
                $pk = (int)$payload['nbPK'];
                unset($payload['nbPK'], $payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->where('nbPK', '=', $pk)->update($payload);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            },
            'NavbarItemDeleted' => function($payload, $db, $dialect) {
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->where('nbPK', '=', (int)$payload['nbPK'])->delete();
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
            },
        ];
    }
}
?>
