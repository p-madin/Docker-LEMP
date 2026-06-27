<?php

class MySQLDialect extends ANSIStandardDialect {
    
    /**
     * MySQL uses 'LIMIT x OFFSET y' instead of the ANSI standard 'FETCH NEXT'.
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        if (isset($components['limit'])) {
            $sql .= "LIMIT " . (int)$components['limit'];
        }

        if (isset($components['offset'])) {
            $sql .= ($sql === "" ? "LIMIT 18446744073709551615 " : " ") . "OFFSET " . (int)$components['offset'];
        }
        
        return $sql;
    }

    /**
     * MySQL uses backticks (`) for identifiers.
     */
    public function quoteIdentifier(string $identifier, bool $normalize = true): string {
        if ($identifier === '*') {
            return '*';
        }

        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(function($part) use ($normalize) {
                return $this->quoteIdentifier($part, $normalize);
            }, $parts));
        }

        // Normalize to lowercase for consistency and cross-vendor flexibility
        if ($normalize) {
            $identifier = $identifier;
        }        

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    public function tableExists(PDO $db, string $tableName): bool {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :name");
        $stmt->execute([':name' => $tableName]);
        return (bool)$stmt->fetch();
    }

    public function columnExists(PDO $db, string $tableName, string $columnName): bool {
        $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :tname AND column_name = :cname");
        $stmt->execute([':tname' => $tableName, ':cname' => $columnName]);
        return (bool)$stmt->fetch();
    }
}
