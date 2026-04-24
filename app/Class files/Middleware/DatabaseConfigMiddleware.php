<?php

class DatabaseConfigMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db_controller, $db, $dialect, $systemConfigController, $scvRows, $router;

        // 1. Database Connection
        $vendor = getenv('DB_VENDOR') ?: 'mysql';
        $host = getenv('DB_HOST') ?: 'db';
        $dbname = getenv('DB_NAME') ?: 'stackDB';
        $username = getenv('DB_USER') ?: 'docker_user_lemp';
        $password = getenv('DB_PASS') ?: 'docker_user_lemp';
        $charset = 'utf8mb4';

        $dsn = "$vendor:host=$host;dbname=$dbname" . ($vendor === 'mysql' ? ";charset=$charset" : "");
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $db_controller = new db_connect_controller($dsn, $username, $password, $options);
        $db = $db_controller->connect();
        $dialect = $db_controller->getDialect();

        // 2. Error Handler
        registerErrorHandler($db, $dialect);

        // 3. System Config
        $systemConfigController = new SystemConfigController($db, $dialect);
        $scvRows = $systemConfigController->getSysConfig();

        // 4. Initialize Router from Database (Lazy Resolution)
        // Since the router registry depends on the DB, we do it here.
        $router->initializeFromDatabase($systemConfigController);

        return $next($request);
    }
}
