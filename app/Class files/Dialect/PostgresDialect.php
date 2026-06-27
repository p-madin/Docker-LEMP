<?php

class PostgresDialect extends ANSIStandardDialect {
    
    /**
     * Postgres specific limit/offset follows ANSI but we'll implementation explicitly 
     * for clarity if needed. Currently standard LIMIT x OFFSET y works in PG too.
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        if (isset($components['limit'])) {
            $sql .= "LIMIT " . (int)$components['limit'];
        }

        if (isset($components['offset'])) {
            $sql .= ($sql === "" ? "" : " ") . "OFFSET " . (int)$components['offset'];
        }
        
        return $sql;
    }

    // Postgres uses standard double quotes for identifiers, 
    // which is already handled by ANSIStandardDialect::quoteIdentifier().

    public function compileColumnDefinition(array $column): string {
        $type = strtoupper($column['type']);
        if (!empty($column['autoIncrement'])) {
            if ($type === 'INT' || $type === 'INTEGER') {
                $type = 'SERIAL';
            } elseif ($type === 'BIGINT') {
                $type = 'BIGSERIAL';
            }
        }
        
        $def = $this->quoteIdentifier($column['name']) . " " . $type;
        if (!empty($column['length']) && $type !== 'SERIAL' && $type !== 'BIGSERIAL') {
            $def .= "(" . $column['length'] . ")";
        }
        
        if (!empty($column['notNull'])) {
            $def .= " NOT NULL";
        }
        
        if (isset($column['default']) && $column['default'] !== '') {
            if (strtoupper($column['default']) === 'CURRENT_TIMESTAMP') {
                $def .= " DEFAULT CURRENT_TIMESTAMP";
            } else {
                $def .= " DEFAULT " . (is_numeric($column['default']) ? $column['default'] : "'" . $column['default'] . "'");
            }
        }
        
        return $def;
    }

    public function tableExists(PDO $db, string $tableName): bool {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = :name");
        $stmt->execute([':name' => $tableName]);
        return (bool)$stmt->fetch();
    }

    public function columnExists(PDO $db, string $tableName, string $columnName): bool {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = :tname AND column_name = :cname");
        $stmt->execute([':tname' => $tableName, ':cname' => $columnName]);
        return (bool)$stmt->fetch();
    }
}
