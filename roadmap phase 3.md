# Phase 3: ISO SQL QueryBuilder (Dialect Pattern)

## Goal
Develop a `QueryBuilder` that enables Plug-and-Play (PnP) database substitution. The system must support MySQL, MariaDB, PostgreSQL, Oracle, and MS SQL by abstracting vendor-specific SQL variations into a `DatabaseDialect` pattern.

**Scope Restriction**: This phase is strictly limited to a functional union of common OLTP grammar:
*   `SELECT` (with basic `WHERE`, `ORDER BY`, `LIMIT`/`OFFSET`)
*   `INSERT` (single row)
*   `UPDATE` (with basic `WHERE`)
*   `DELETE` (with basic `WHERE`)

Advanced features (CTEs, subqueries, schema definitions, window functions) are intentionally excluded to minimize complexity.

## Architecture

### 1. Configuration (`DB_VENDOR`)
The application is now aware of its database vendor via the `DB_VENDOR` environment variable (default: `mysql`), configured in `compose.yaml` and parsed in `config.php`.

### 2. The `DatabaseDialect` Contract
A core interface that defines how standard SQL components are compiled into strings.

```php
interface DatabaseDialect {
    public function compileSelect(array $components, array &$bindings): string;
    public function compileInsert(string $table, array $params, array &$bindings): string;
    public function compileUpdate(string $table, array $params, array $wheres, array &$bindings): string;
    public function compileDelete(string $table, array $wheres, array &$bindings): string;
    
    // Vendor specific identifier quoting (e.g., `user` vs "user")
    public function quoteIdentifier(string $identifier): string;
    
    // Vendor specific pagination
    public function compileLimitOffset(array $components, array &$bindings): string;
}
```

### 3. Concrete Dialects
*   **`ANSIStandardDialect`**: A base class implementing standard SQL:2008 syntax (using `OFFSET x ROWS FETCH NEXT y ROWS ONLY`). Uses string concatenation instead of `sprintf()`.
*   **`MySQLDialect`**: Overrides specific methods, most notably identifier quoting (using backticks) and pagination (using `LIMIT y OFFSET x`).
*   **`PostgresDialect`**: Inherits from ANSI, overriding specific quoting rules (using double quotes).

## 4. Usage Details
The `QueryBuilder` is a fluent accumulator for query state. It uses the `bindTo()` pattern for explicit PDO parameter binding.

```php
// SELECT Example
$qb = new QueryBuilder($db_controller->getDialect());
$sql = $qb->table('users')
          ->select(['id', 'username'])
          ->where('status', '=', 'active')
          ->limit(10)
          ->toSQL();

$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();
$results = $stmt->fetchAll();

// UPDATE Example
$qb = new QueryBuilder($db_controller->getDialect());
$sql = $qb->table('users')
          ->where('id', '=', 1)
          ->update(['username' => 'new_name']);

$stmt = $db->prepare($sql);
$qb->bindTo($stmt);
$stmt->execute();
```

## 5. Implementation Details
*   **Named Parameters**: The builder generates unique `:i_n` placeholders (e.g. `:i_0`, `:i_1`). This ensures that multiple clauses referencing the same column (or repeated pagination) never collide and are clearly human-readable.
*   **Multi-DBMS Support**: The application natively supports **MySQL**, **MariaDB**, and **PostgreSQL** backends. The `DB_VENDOR` environment variable dynamically switches the `QueryBuilder`'s dialect to handle vendor-specific quoting, pagination, and date functions.
*   **Binding Coordination**: The `QueryBuilder` maintains an internal `bindings` associative array and a `paramCount`. The `Dialect` simply appends to the provided SQL structure using the placeholder names coordinated by the builder.
*   **Namespace-Free**: To align with simpler integration and consistent autoloading in the current project structure, all database components reside in the global namespace.

## Status: COMPLETED
Implementation is fully verified across all 8 test suites. Phase 3 is hardened and ready for production.
