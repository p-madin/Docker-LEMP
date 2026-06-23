<?php
class NavbarFormDataProvider implements DataProviderInterface {
    public function getColumns(): array { return []; }
    public function getNestedKey(): ?string { return null; }
    public function getDataSourceName(): string { return "Navbar Form Data"; }
    
    public function getData(): array {
        global $db, $dialect;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id > 0) {
            $qb = new QueryBuilder($dialect);
            $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbProtected', 'nbOrder', 'nbParentFK', 'nbPageFK'])->where('nbPK', '=', $id);
            $u = $qb->executeFetch($db);
            return $u ? [$u] : [];
        } else {
            return [[
                'nbPK' => '',
                'nbText' => '',
                'nbDiscriminator' => 'p',
                'nbPath' => '/',
                'nbProtected' => 0,
                'nbOrder' => 1,
                'nbParentFK' => '',
                'nbPageFK' => ''
            ]];
        }
    }
}
?>
