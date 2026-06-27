<?php

class MSSQLDialect extends ANSIStandardDialect {
    public function extractDatePart(string $part, string $column): string {
        // MSSQL uses DATEPART(part, column) instead of EXTRACT(part FROM column)
        return "DATEPART(" . strtoupper($part) . ", " . $this->quoteIdentifier($column) . ")";
    }

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

        if ($normalize) {
            $identifier = strtolower($identifier);
        }

        return '[' . str_replace(']', ']]', $identifier) . ']';
    }

    public function compileRaw(string $raw): string {
        // Translate ANSI/MySQL specific functions to MS SQL
        $raw = preg_replace('/\bNOW\(\)/i', 'GETDATE()', $raw);
        return $raw;
    }

    public function compileColumnDefinition(array $column): string {
        $type = strtoupper($column['type']);
        $def = $this->quoteIdentifier($column['name']) . " " . $type;
        
        $noLengthTypes = ['TINYINT', 'SMALLINT', 'INT', 'BIGINT', 'TEXT', 'DATETIME', 'DATE', 'TIME', 'BOOLEAN'];
        if (!empty($column['length']) && !in_array($type, $noLengthTypes)) {
            $def .= "(" . $column['length'] . ")";
        }
        
        if (!empty($column['autoIncrement'])) {
            $def .= " IDENTITY(1,1)";
        }
        
        if (!empty($column['notNull'])) {
            $def .= " NOT NULL";
        } else {
            $def .= " NULL";
        }
        
        if (isset($column['default']) && $column['default'] !== '') {
            if (strtoupper($column['default']) === 'CURRENT_TIMESTAMP') {
                $def .= " DEFAULT GETDATE()";
            } elseif (strtoupper($column['default']) === 'NULL') {
                $def .= " DEFAULT NULL";
            } else {
                $def .= " DEFAULT " . (is_numeric($column['default']) ? $column['default'] : "'" . str_replace("'", "''", $column['default']) . "'");
            }
        }
        
        return $def;
    }

    public function compileCreateTable(string $tableName, array $columns, array $foreignKeys = [], array $indexes = []): string {
        $hasSelfReference = false;
        foreach ($foreignKeys as $fk) {
            if (strtolower($fk['referencesTable']) === strtolower($tableName)) {
                $hasSelfReference = true;
                break;
            }
        }

        foreach ($foreignKeys as $key => $fk) {
            // MSSQL is extremely strict about multiple cascade paths.
            // If a table has a self-referencing foreign key, it cannot have ANY cascading deletes (CASCADE/SET NULL),
            // neither from itself, nor from other tables cascading into it.
            if ($hasSelfReference && isset($fk['onDelete'])) {
                unset($foreignKeys[$key]['onDelete']);
            }
        }
        return parent::compileCreateTable($tableName, $columns, $foreignKeys, $indexes);
    }

    public function tableExists(PDO $db, string $tableName): bool {
        $stmt = $db->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = :name");
        $stmt->execute([':name' => $tableName]);
        return (bool)$stmt->fetch();
    }

    public function columnExists(PDO $db, string $tableName, string $columnName): bool {
        $stmt = $db->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = :tname AND COLUMN_NAME = :cname");
        $stmt->execute([':tname' => $tableName, ':cname' => $columnName]);
        return (bool)$stmt->fetch();
    }
}
