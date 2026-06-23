<?php
class FormAction implements ControllerInterface {
    public static string $path = '/formAction';
    public static string $manage_URI = '/';
    public static string $object_URI = '/';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect;

        $qb = new QueryBuilder($dialect);
        $forms = $qb->table('tblForm')->select(['tfPK', 'tfName'])->orderBy('tfName', 'ASC')->executeFetchAll($db);
            
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'forms' => $forms]);
    }
}
