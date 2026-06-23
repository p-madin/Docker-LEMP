#!/bin/bash

# Start SQL Server in the background
/opt/mssql/bin/sqlservr &
PID=$!

if [ ! -f /var/opt/mssql/.initialized ]; then
    echo "Waiting for SQL Server to start..."
    for i in {1..50}; do
        /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$MSSQL_SA_PASSWORD" -C -Q "SELECT 1" > /dev/null 2>&1
        if [ $? -eq 0 ]; then
            echo "SQL Server is up. Running initialization scripts..."
            break
        fi
        sleep 2
    done

    /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$MSSQL_SA_PASSWORD" -C -i /docker-entrypoint-initdb.d/01_db_ddl.sql
    /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$MSSQL_SA_PASSWORD" -C -i /docker-entrypoint-initdb.d/02_db_dml.sql
    if [ -f /docker-entrypoint-initdb.d/03_db_dml.sql ]; then
        /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "$MSSQL_SA_PASSWORD" -C -i /docker-entrypoint-initdb.d/03_db_dml.sql
    fi

    touch /var/opt/mssql/.initialized
    echo "Initialization finished."
fi

# Keep the container running by waiting on the SQL Server process
wait $PID
