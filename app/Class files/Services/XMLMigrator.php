<?php
class XMLMigrator {
    private PDO $db;
    private DatabaseDialect $dialect;

    public function __construct(PDO $db, DatabaseDialect $dialect) {
        $this->db = $db;
        $this->dialect = $dialect;
    }

    public function migrate(string $ddlXmlPath, string $dmlXmlPath): void {
        echo "Starting XML Migration...\n";
        
        if (file_exists($ddlXmlPath)) {
            $this->processDDL($ddlXmlPath);
        } else {
            echo "DDL XML file not found at: {$ddlXmlPath}\n";
        }

        if (file_exists($dmlXmlPath)) {
            $this->processDML($dmlXmlPath);
        } else {
            echo "DML XML file not found at: {$dmlXmlPath}\n";
        }
        
        echo "Migration completed.\n";
    }

    private function processDDL(string $xmlPath): void {
        echo "Processing DDL schema...\n";
        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            throw new Exception("Failed to parse DDL XML.");
        }

        foreach ($xml->table as $tableNode) {
            $tableName = (string)$tableNode['name'];
            $columns = [];
            $foreignKeys = [];
            $indexes = [];

            foreach ($tableNode->column as $colNode) {
                $columns[] = [
                    'name' => (string)$colNode['name'],
                    'type' => (string)$colNode['type'],
                    'length' => isset($colNode['length']) ? (string)$colNode['length'] : null,
                    'autoIncrement' => isset($colNode['autoIncrement']) && (string)$colNode['autoIncrement'] === 'true',
                    'primaryKey' => isset($colNode['primaryKey']) && (string)$colNode['primaryKey'] === 'true',
                    'notNull' => isset($colNode['notNull']) && (string)$colNode['notNull'] === 'true',
                    'default' => isset($colNode['default']) ? (string)$colNode['default'] : null,
                ];
            }

            foreach ($tableNode->foreignKey as $fkNode) {
                $fk = [
                    'column' => (string)$fkNode['column'],
                    'referencesTable' => (string)$fkNode['referencesTable'],
                    'referencesColumn' => (string)$fkNode['referencesColumn']
                ];
                if (isset($fkNode['onDelete'])) {
                    $fk['onDelete'] = (string)$fkNode['onDelete'];
                }
                $foreignKeys[] = $fk;
            }

            foreach ($tableNode->index as $idxNode) {
                $indexes[] = [
                    'name' => (string)$idxNode['name'],
                    'unique' => isset($idxNode['unique']) && (string)$idxNode['unique'] === 'true',
                    'columns' => (string)$idxNode['columns']
                ];
            }

            if (!$this->dialect->tableExists($this->db, $tableName)) {
                echo "Creating table: {$tableName}\n";
                $sql = $this->dialect->compileCreateTable($tableName, $columns, $foreignKeys, $indexes);
                // The compileCreateTable might return multiple statements separated by ; if there are indexes.
                $statements = array_filter(array_map('trim', explode(';', $sql)));
                foreach ($statements as $stmt) {
                    $this->db->exec($stmt);
                }
            } else {
                echo "Table {$tableName} exists, checking for new columns...\n";
                foreach ($columns as $column) {
                    if (!$this->dialect->columnExists($this->db, $tableName, $column['name'])) {
                        echo "Adding column {$column['name']} to {$tableName}\n";
                        $sql = $this->dialect->compileAddColumn($tableName, $column);
                        $this->db->exec($sql);
                    }
                }
            }
        }
    }

    public function processDML(string $xmlPath): void {
        echo "Processing DML seed data...\n";
        $xml = simplexml_load_file($xmlPath);
        if (!$xml) {
            throw new Exception("Failed to parse DML XML.");
        }

        foreach ($xml->table as $tableNode) {
            $tableName = (string)$tableNode['name'];
            if (!$this->dialect->tableExists($this->db, $tableName)) {
                echo "Skipping seed data for {$tableName} (table does not exist).\n";
                continue;
            }

            // Check if table is empty to prevent duplicate seeding.
            // A more robust way would be checking specific PKs, but checking if empty is safer for seed data.
            $stmt = $this->db->query("SELECT COUNT(*) FROM " . $this->dialect->quoteIdentifier($tableName));
            if ($stmt && (int)$stmt->fetchColumn() > 0) {
                echo "Table {$tableName} already has data, skipping seed.\n";
                continue;
            }

            echo "Seeding data for {$tableName}...\n";
            $qb = new QueryBuilder($this->dialect);
            
            // To handle IDENTITY_INSERT in MSSQL
            if ($this->dialect instanceof MSSQLDialect) {
                // Check if any row has an explicit PK to insert
                $hasExplicitPk = false;
                if (isset($tableNode->row[0])) {
                    // Try to guess if first column is identity. If it's a number, maybe it is.
                    // This is naive, but MSSQL requires explicit SET IDENTITY_INSERT ON
                    // For safety, let's just turn it on if there's an ID-like field (usually ends with PK)
                    foreach ($tableNode->row[0]->column as $colNode) {
                        $colName = (string)$colNode['name'];
                        if (stripos($colName, 'PK') !== false) {
                            $hasExplicitPk = true;
                            break;
                        }
                    }
                }
                if ($hasExplicitPk) {
                    $this->db->exec("SET IDENTITY_INSERT " . $this->dialect->quoteIdentifier($tableName) . " ON");
                }
            }

            $this->dialect->beginTransaction($this->db);

            foreach ($tableNode->row as $rowNode) {
                $payload = [];
                foreach ($rowNode->column as $colNode) {
                    $colName = (string)$colNode['name'];
                    if (isset($colNode['null']) && (string)$colNode['null'] === 'true') {
                        $payload[$colName] = null;
                    } else {
                        $payload[$colName] = (string)$colNode;
                    }
                }
                
                $sql = $qb->table($tableName)->insert($payload);
                $stmtInsert = $this->db->prepare($sql);
                $qb->bindTo($stmtInsert);
                $stmtInsert->execute();
            }

            $this->db->commit();

            if ($this->dialect instanceof MSSQLDialect && isset($hasExplicitPk) && $hasExplicitPk) {
                $this->db->exec("SET IDENTITY_INSERT " . $this->dialect->quoteIdentifier($tableName) . " OFF");
            }
        }
    }
}
?>