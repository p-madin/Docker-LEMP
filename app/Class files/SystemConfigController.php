<?php

class SystemConfigController {
    
    private $db;
    private $dialect;

    public function __construct(\PDO $db, DatabaseDialect $dialect) {
        $this->db = $db;
        $this->dialect = $dialect;
    }

    /**
     * Retrieves the global system configurations from the sysConfig table
     * @return array key-value pairs of configuration values
     */
    public function getSysConfig(): array {
        $qb = new QueryBuilder($this->dialect);
        $data = $qb->table('sysConfig')->select(['scName', 'scValue'])->executeFetchAll($this->db);

        $scvRows = [];
        foreach($data as $value) {
            $scvRows[$value['scName']] = $value['scValue'];
        }

        return $scvRows;
    }

    /**
     * Retrieves the dynamic navbar items from the tblNavBar table
     * @return array list of associative arrays matching the navbar expectations
     */
    public function getNavbarItems(): array {
        $qb = new QueryBuilder($this->dialect);
        $data = $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPageFK', 'nbPath', 'nbControllerClass', 'nbProtected', 'nbOrder', 'nbParentFK'])
                   ->orderBy('nbOrder', 'ASC')
                   ->executeFetchAll($this->db);

        $items = [];
        foreach($data as $row) {
            $items[] = [
                'id' => $row['nbPK'],
                'label' => $row['nbText'],
                'discriminator' => $row['nbDiscriminator'],
                'pageFK' => $row['nbPageFK'],
                'url' => $row['nbPath'],
                'controller' => $row['nbControllerClass'],
                'protected' => (bool)$row['nbProtected'],
                'parentFK' => $row['nbParentFK']
            ];
        }

        return $items;
    }
}
