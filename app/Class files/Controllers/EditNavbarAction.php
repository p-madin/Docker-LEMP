<?php
class EditNavbarAction implements ControllerInterface {
    public static string $path = '/editNavbar';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController, $formSchemas, $dialect, $db, $eventStore;

        Hyperlink::handleAction($sessionController);

        if($request->getMethod() === 'POST') {
            $authorId = $sessionController->getSystemUserId();

            // Handle pure delete request
            if (isset($request->post['action']) && $request->post['action'] === 'delete') {
                $pk = (int)($request->post['nbPK'] ?? 0);
                if ($pk > 0) {
                    // Fetch current data for undo support
                    $qb_data = new QueryBuilder($dialect);
                    $itemData = $qb_data->table('tblNavBar')->where('nbPK', '=', $pk)->getFetch($db);
                    
                    if ($itemData) {
                        $eventStore->append('NavbarItemDeleted', $itemData, $pk, $authorId);
                    }
                }
                header("Location: /navbar_management");
                exit;
            }

            $cleanData = FormValidation::processAndValidate('navbar', $request->post, $formSchemas, $sessionController, function($clean) {
                $id = $clean['nbPK'] ?? 0;
                return "/edit_navbar" . ($id ? "?id=".$id : "");
            });

            $isProtected = (isset($request->post['nbProtected']) && $request->post['nbProtected'] === '1') ? 1 : 0;
            $parentFK = (!empty($cleanData['nbParentFK'])) ? (int)$cleanData['nbParentFK'] : null;

            $data = [
                'nbText'          => $cleanData['nbText'],
                'nbDiscriminator' => $cleanData['nbDiscriminator'],
                'nbPath'          => $cleanData['nbPath'],
                'nbOrder'         => (int)$cleanData['nbOrder'],
                'nbProtected'     => $isProtected,
                'nbParentFK'      => $parentFK
            ];

            $pk = (int)$cleanData['nbPK'];

            if ($pk > 0) {
                // Fetch current state for Memento support
                $qb_old = new QueryBuilder($dialect);
                $oldData = $qb_old->table('tblNavBar')->where('nbPK', '=', $pk)->getFetch($db);
                $previousPayload = is_array($oldData) ? $oldData : null;
                $eventId = $eventStore->append('NavbarItemUpdated', array_merge(['nbPK' => $pk], $data), $pk, $authorId, $previousPayload);
            } else {
                $eventId = $eventStore->append('NavbarItemCreated', $data, null, $authorId);
            }

            $eventStore->waitUntilProcessed($eventId);

            $redirectUrl = "/navbar_management";
            if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                echo json_encode(['redirect' => $redirectUrl]);
                exit;
            }

            header("Location: " . $redirectUrl);
            exit;
        }

        header("Location: /navbar_management");
        exit;
    }

    public static function getEventHandlers(): array {
        return [
            'NavbarItemCreated' => function($payload, $db, $dialect) {
                unset($payload['original_event_id']);
                $qb = new QueryBuilder($dialect);
                $sql = $qb->table('tblNavBar')->insert($payload);
                $stmt = $db->prepare($sql);
                $qb->bindTo($stmt);
                $stmt->execute();
                return (int)$db->lastInsertId();
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
