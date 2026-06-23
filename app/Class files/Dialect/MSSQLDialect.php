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
}
