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
}
