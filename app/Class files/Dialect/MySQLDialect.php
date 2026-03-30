<?php

class MySQLDialect extends ANSIStandardDialect {
    
    /**
     * MySQL uses 'LIMIT x OFFSET y' instead of the ANSI standard 'FETCH NEXT'.
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        if (isset($components['limitParam'])) {
            $sql .= "LIMIT :" . $components['limitParam'];
        }

        if (isset($components['offsetParam'])) {
            $sql .= ($sql === "" ? "LIMIT 18446744073709551615 " : " ") . "OFFSET :" . $components['offsetParam'];
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
}
