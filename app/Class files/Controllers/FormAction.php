<?php
class FormAction implements ControllerInterface {
    public static string $path = '/formAction';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $db, $dialect;

        $qb = new QueryBuilder($dialect);
        $forms = $qb->table('tblForm')->select(['tfPK', 'tfName'])->orderBy('tfName', 'ASC')->getFetchAll($db);
            
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'forms' => $forms]);
    }
}
