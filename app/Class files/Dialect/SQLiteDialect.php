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

    /**
     * Extracts a date part from a column using SQLite's strftime.
     */
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
}
