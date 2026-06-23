<?php
class ColumnFormDataProvider implements DataProviderInterface {
    public function getColumns(): array { return []; }
    public function getNestedKey(): ?string { return null; }
    public function getDataSourceName(): string { return "Column Form Data"; }
    
    public function getData(): array {
        global $db, $dialect;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $form_id = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;

        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblColumns')->select(['tcPK', 'tcFormFK', 'tcName', 'tcLabel', 'tcType', 'tcRules', 'tcOrder'])->where('tcPK', '=', $id);
            $raw = $qb->executeFetch($db);
            return $raw ? [$raw] : [];
        } else if ($form_id > 0) {
            return [[
                'tcPK' => '', 
                'tcFormFK' => $form_id, 
                'tcName' => '', 
                'tcLabel' => '', 
                'tcType' => 'text', 
                'tcRules' => '{}', 
                'tcOrder' => 1
            ]];
        }
        return [];
    }
}
?>
