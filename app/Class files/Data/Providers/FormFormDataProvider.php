<?php
class FormFormDataProvider implements DataProviderInterface {
    public function getColumns(): array { return []; }
    public function getNestedKey(): ?string { return null; }
    public function getDataSourceName(): string { return "Form Form Data"; }
    
    public function getData(): array {
        global $db, $dialect;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly'])->where('tfPK', '=', $id);
            $raw = $qb->executeFetch($db);
            
            if ($raw) {
                return [[
                    'tfPK' => $raw['tfPK'] ?? $raw['tfpk'] ?? $raw['TFPK'],
                    'tfName' => $raw['tfName'] ?? $raw['tfname'] ?? $raw['TFNAME'],
                    'tfReadOnly' => (int)($raw['tfReadOnly'] ?? $raw['tfreadonly'] ?? $raw['TFREADONLY'] ?? 0)
                ]];
            }
        }
        return [['tfPK' => '', 'tfName' => '']];
    }
}
?>
