<?php

class DatabaseForm {
    /**
     * Queries the underlying database schema and compiles it 
     * exactly into the legacy $formSchemas array format.
     */
    public static function generateGlobalSchemas(\PDO $db, DatabaseDialect $dialect): array {
        
        $qb = new \QueryBuilder($dialect);
        $qb->table('tblForm')
           ->select([
               'tblForm.tfName',
               'tblColumns.tcName',
               'tblColumns.tcLabel',
               'tblColumns.tcType',
               'tblColumns.tcRules'
           ])
           ->join('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
           ->orderBy('tblForm.tfName', 'ASC')
           ->orderBy('tblColumns.tcOrder', 'ASC');

        $rows = $qb->getFetchAll($db);

        $schemas = [];
        
        if (!$rows) {
            return $schemas;
        }

        foreach ($rows as $row) {
            $formName = $row['tfName'] ?? $row['tfname'] ?? $row['TFNAME']; // PGSQL lowercase protection alias catch
            if (!$formName) continue;
            
            if (!isset($schemas[$formName])) {
                $schemas[$formName] = [];
            }
            
            // Decodes from MySQL or PgSQL text/json type
            $rulesRaw = $row['tcRules'] ?? $row['tcrules'] ?? $row['TCRULES'];
            $rules = json_decode($rulesRaw, true);
            if (!is_array($rules)) {
                $rules = [];
            }

            $schemas[$formName][] = [
                'name' => $row['tcName'] ?? $row['tcname'] ?? $row['TCNAME'],
                'label' => $row['tcLabel'] ?? $row['tclabel'] ?? $row['TCLABEL'],
                'type' => $row['tcType'] ?? $row['tctype'] ?? $row['TCTYPE'],
                'rules' => $rules
            ];
        }

        return $schemas;
    }
}
?>
