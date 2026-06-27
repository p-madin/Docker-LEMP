<?php

class DatabaseConfigMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db_controller, $db, $dialect, $systemConfigController, $scvRows, $router, $eventStore;

        // 1. Database Connection
        $vendor = strtolower(getenv('DB_VENDOR'));
        $host = getenv('DB_HOST');
        $dbname = getenv('TENANT_DB_NAME');
        $username = getenv('TENANT_DB_USER');
        $password = getenv('TENANT_DB_PASS');
        $charset = 'utf8mb4';

        if ($vendor === 'mssql') {
            $dsn = "dblib:host=$host;dbname=$dbname";
        } elseif ($vendor === 'sqlite') {
            $dsn = "sqlite:/var/sqlite/stackDB.sqlite";
        } else {
            $dsn = "$vendor:host=$host;dbname=$dbname" . ($vendor === 'mysql' ? ";charset=$charset" : "");
        }
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        if ($vendor === 'sqlite') {
            $options[PDO::ATTR_TIMEOUT] = 600;
        }

        $db_controller = new db_connect_controller($dsn, $username, $password, $options);
        $db = $db_controller->connect();
        
        if ($vendor === 'sqlite') {
            $db->exec('PRAGMA journal_mode=WAL;');
            $db->exec('PRAGMA synchronous=NORMAL;');
            $db->exec('PRAGMA busy_timeout=10000;');
        }
        
        $dialect = $db_controller->getDialect();

        // Initialize Global Event Store
        $eventStore = new EventStore($db, $dialect);

        // 2. Error Handler
        registerErrorHandler($db, $dialect);

        // 3. System Config
        $systemConfigController = new SystemConfigController($db, $dialect);
        try {
            $scvRows = $systemConfigController->getSysConfig();
        } catch (Exception $e) {
            // Ignore during initial migration/setup where the table may not exist yet
            $scvRows = [];
        }

        // 4. Initialize Router from Database (Lazy Resolution)
        // Since the router registry depends on the DB, we do it here.
        // Guard: $router may not exist in CLI contexts (e.g., cron worker).
        if (isset($router)) {
            try {
                $router->initializeFromDatabase($systemConfigController);
            } catch (Exception $e) {
                // Ignore during initial migration/setup where the table may not exist yet
            }
        }

        return $next($request);
    }
}
