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
        $qb->table('sysConfig')->select(['scName', 'scValue']);
        $stmt = $this->db->prepare($qb->toSQL());
        $qb->bindTo($stmt);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $qb->table('tblNavBar')->select(['nbPK', 'nbText', 'nbDiscriminator', 'nbPath', 'nbControllerClass', 'nbProtected', 'nbOrder', 'nbParentFK']);
        
        $sql = $qb->toSQL();
        $sql .= " ORDER BY nbOrder ASC";
        
        $stmt = $this->db->prepare($sql);
        $qb->bindTo($stmt);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach($data as $row) {
            $items[] = [
                'id' => $row['nbPK'],
                'label' => $row['nbText'],
                'discriminator' => $row['nbDiscriminator'],
                'url' => $row['nbPath'],
                'controller' => $row['nbControllerClass'],
                'protected' => (bool)$row['nbProtected'],
                'parentFK' => $row['nbParentFK']
            ];
        }

        return $items;
    }
}
