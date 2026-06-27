<?php

class SQLiteDialect extends ANSIStandardDialect {
    
    /**
     * SQLite identifier quoting uses double quotes (standard ANSI) or backticks.
     * We use backticks and prevent lowercasing to preserve native case.
     */
    public function quoteIdentifier(string $identifier, bool $normalize = false): string {
        if ($identifier === '*') {
            return '*';
        }

        if (strpos($identifier, '.') !== false) {
            $parts = explode('.', $identifier);
            return implode('.', array_map(function($part) use ($normalize) {
                return $this->quoteIdentifier($part, $normalize);
            }, $parts));
        }

        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    /**
     * SQLite uses LIMIT ... OFFSET ...
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        $hasLimit = isset($components['limit']);
        $hasOffset = isset($components['offset']);

        if ($hasLimit || $hasOffset) {
            // If we have an offset without a limit, SQLite requires LIMIT -1
            $limit = $hasLimit ? (int)$components['limit'] : -1;
            $sql .= "LIMIT " . $limit;
            
            if ($hasOffset) {
                $sql .= " OFFSET " . (int)$components['offset'];
            }
        }
        
        return $sql;
    }

    public function beginTransaction(PDO $db): bool {
        if ($db->inTransaction()) {
            return false;
        }
        $db->beginTransaction();
        $db->exec('COMMIT');
        $db->exec('BEGIN IMMEDIATE TRANSACTION');
        return true;
    }

    public function extractDatePart(string $part, string $column): string {
        $formatMapping = [
            'YEAR' => '%Y',
            'MONTH' => '%m',
            'DAY' => '%d',
            'HOUR' => '%H',
            'MINUTE' => '%M',
            'SECOND' => '%S',
        ];

        $upperPart = strtoupper($part);
        $format = $formatMapping[$upperPart] ?? '';

        if (!$format) {
            throw new \Exception("SQLiteDialect: Unsupported date part '$part'");
        }

        return "strftime('" . $format . "', " . $this->quoteIdentifier($column) . ")";
    }

    public function compileCreateTable(string $tableName, array $columns, array $foreignKeys = [], array $indexes = []): string {
        foreach ($columns as &$column) {
            if (!empty($column['autoIncrement'])) {
                $column['primaryKey'] = false;
            }
        }
        return parent::compileCreateTable($tableName, $columns, $foreignKeys, $indexes);
    }

    public function compileColumnDefinition(array $column): string {
        if (!empty($column['autoIncrement'])) {
            return $this->quoteIdentifier($column['name']) . " INTEGER PRIMARY KEY AUTOINCREMENT";
        }

        $type = strtoupper($column['type']);
        if ($type === 'TINYINT' || $type === 'INT') {
            $type = 'INTEGER';
        }
        
        $def = $this->quoteIdentifier($column['name']) . " " . $type;
        if (!empty($column['length']) && $type !== 'INTEGER') {
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
        $stmt = $db->prepare("SELECT 1 FROM sqlite_master WHERE type='table' AND name=:name");
        $stmt->execute([':name' => $tableName]);
        return (bool)$stmt->fetch();
    }

    public function columnExists(PDO $db, string $tableName, string $columnName): bool {
        $stmt = $db->prepare("PRAGMA table_info(" . $this->quoteIdentifier($tableName) . ")");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($columns as $col) {
            if (strcasecmp($col['name'], $columnName) === 0) {
                return true;
            }
        }
        return false;
    }
}
