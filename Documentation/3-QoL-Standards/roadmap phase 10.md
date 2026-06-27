# Roadmap Phase 10: XML-Based Dynamic Database Migrations

## Overview
Phase 10 successfully transitions the Docker-LEMP application from static SQL initialization scripts to a dynamic, vendor-agnostic XML migration system. This guarantees compatibility across all supported database dialects (MySQL, PostgreSQL, MSSQL, SQLite) and enables safe, non-destructive schema and data seeding.

## Accomplishments

### 1. XML Schema Definitions
The foundational structure of the database was successfully migrated out of `01_db_ddl.sql` and `02_db_dml.sql` into equivalent `01_db_ddl.xml` and `02_db_dml.xml` definitions. The XML structure allows columns to be dynamically interpreted without being strictly bound to dialect-specific datatypes or constraints.

### 2. Dialect Extensions for Schema Operations
The `DatabaseDialect` interface was significantly extended to provide DDL generation. Operations added include:
* `compileCreateTable`: Generates database-specific `CREATE TABLE` syntax, handling identity columns, auto-increments, and specific constraint limitations (such as MSSQL's restrictions on multiple cascade paths).
* `compileAddColumn`: Generates non-destructive `ALTER TABLE` statements.
* `tableExists` & `columnExists`: Provides robust validation mechanisms to ensure operations are strictly non-destructive.

### 3. XML Migrator Service
The `XMLMigrator` class was authored to traverse the `01_db_ddl.xml` definitions. It intelligently detects if tables exist and gracefully attempts to map standard attributes into the target dialect.
For seed data (DML), `XMLMigrator` compares existing rows to `02_db_dml.xml` definitions, ensuring rows are correctly populated on first boot without duplicating entries or tripping unique constraints.

### 4. CLI Migration Pipeline (`migrate.php`)
The `migrate.php` CLI script was refactored. Rather than blindly executing standard SQL files via raw PDO operations, it injects the `DatabaseConfigMiddleware` connection securely into `XMLMigrator` and parses the `common` and environment-specific XML files. 

### 5. Docker Initialization Workflow
To prevent file-ownership or permission inconsistencies between database engines (particularly embedded SQLite vs remote daemon connections like MySQL or MSSQL), the application container's `startup.sh` script executes `migrate.php` as a blocking initialization step before bootstrapping the web server (Nginx/PHP-FPM). Additionally, permission safeguards (`chown www-data:www-data`) were integrated into the `startup.sh` flow specifically for SQLite implementations to seamlessly bridge `root`-created `.sqlite` files to the `www-data` daemon.

## Next Steps
The completion of Phase 10 natively unlocks the capability for automated deployments and environment scaling (like child service tenants) using any of the major RDBMS systems seamlessly, without manually re-writing the schema configurations.
