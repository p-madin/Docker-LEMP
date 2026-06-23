#!/bin/sh
set -e

DB_DIR="/var/sqlite"
DB_FILE="$DB_DIR/stackDB.sqlite"

# Ensure the directory exists and has wide permissions for the app container
mkdir -p "$DB_DIR"
chmod 777 "$DB_DIR"

if [ ! -s "$DB_FILE" ]; then
    echo "Initializing SQLite database..."
    
    if [ -f "/docker-entrypoint-initdb.d/01_db_ddl.sql" ]; then
        sqlite3 "$DB_FILE" < "/docker-entrypoint-initdb.d/01_db_ddl.sql"
    fi
    
    if [ -f "/docker-entrypoint-initdb.d/02_db_dml.sql" ]; then
        sqlite3 "$DB_FILE" < "/docker-entrypoint-initdb.d/02_db_dml.sql"
    fi
    
    if [ -f "/docker-entrypoint-initdb.d/03_db_dml.sql" ]; then
        sqlite3 "$DB_FILE" < "/docker-entrypoint-initdb.d/03_db_dml.sql"
    fi
    
    # Change ownership to match www-data in Ubuntu (uid 33)
    chown 33:33 "$DB_FILE" || true
    chmod 666 "$DB_FILE" || true
    echo "Initialization complete."
else
    echo "Database already initialized."
fi

# Keep the container running
echo "SQLite DB container running..."
tail -f /dev/null
