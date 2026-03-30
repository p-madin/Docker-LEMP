<?php

class PostgresDialect extends ANSIStandardDialect {
    
    /**
     * Postgres specific limit/offset follows ANSI but we'll implementation explicitly 
     * for clarity if needed. Currently standard LIMIT x OFFSET y works in PG too.
     */
    public function compileLimitOffset(array $components): string {
        $sql = "";
        
        if (isset($components['limitParam'])) {
            $sql .= "LIMIT :" . $components['limitParam'];
        }

        if (isset($components['offsetParam'])) {
            $sql .= ($sql === "" ? "" : " ") . "OFFSET :" . $components['offsetParam'];
        }
        
        return $sql;
    }

    // Postgres uses standard double quotes for identifiers, 
    // which is already handled by ANSIStandardDialect::quoteIdentifier().
}
