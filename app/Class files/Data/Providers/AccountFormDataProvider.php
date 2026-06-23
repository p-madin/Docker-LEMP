<?php
class AccountFormDataProvider implements DataProviderInterface {
    public function getColumns(): array { return []; }
    public function getNestedKey(): ?string { return null; }
    public function getDataSourceName(): string { return "Account Form Data"; }
    
    public function getData(): array {
        global $db, $dialect;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) return [];
        $qb = new QueryBuilder($dialect);
        $qb->table('appUsers')->select(['auPK', 'username', 'name', 'age', 'city', 'email', 'verified'])->where('auPK', '=', $id);
        $user = $qb->executeFetch($db);
        return $user ? [$user] : [];
    }
}
?>
