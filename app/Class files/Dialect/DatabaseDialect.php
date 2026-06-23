<?php

interface DatabaseDialect {
    /* Compiles a structured array of AST components into a SELECT query. */
    public function compileSelect(array $components): string;

    /* Compiles an INSERT query. */
    public function compileInsert(string $table, array $params): string;

    /* Compiles an UPDATE query. */
    public function compileUpdate(string $table, array $params, array $wheres): string;

    /* Compiles a DELETE query. */
    public function compileDelete(string $table, array $wheres): string;

    /* Quotes an identifier (table or column name) safely. */
    public function quoteIdentifier(string $identifier, bool $normalize = true): string;

    /* Compiles the pagination/limit syntax for the specific DBMS. */
    public function compileLimitOffset(array $components): string;

    /* Extracts a date part from a column. */
    public function extractDatePart(string $part, string $column): string;

    /* Compiles raw SQL strings, translating standard SQL functions to dialect-specific equivalents if needed. */
    public function compileRaw(string $raw): string;

    /* Begins a database transaction appropriately for the dialect. Returns true if the transaction was started (owned), false otherwise. */
    public function beginTransaction(PDO $db): bool;
}
