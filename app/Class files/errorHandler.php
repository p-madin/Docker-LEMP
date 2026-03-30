<?php

/**
 * Registers a custom PHP error handler that writes errors to the phpErrorLog
 * database table as well as passing them through to PHP's built-in handler.
 *
 * Must be called after $db is initialised.
 */
function registerErrorHandler(PDO $db, DatabaseDialect $dialect): void {

    $severityMap = [
        E_ERROR             => 'E_ERROR',
        E_WARNING           => 'E_WARNING',
        E_PARSE             => 'E_PARSE',
        E_NOTICE            => 'E_NOTICE',
        E_CORE_ERROR        => 'E_CORE_ERROR',
        E_CORE_WARNING      => 'E_CORE_WARNING',
        E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
        E_USER_ERROR        => 'E_USER_ERROR',
        E_USER_WARNING      => 'E_USER_WARNING',
        E_USER_NOTICE       => 'E_USER_NOTICE',
        E_STRICT            => 'E_STRICT',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED        => 'E_DEPRECATED',
        E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
    ];

    $writeToDb = function(string $severity, string $message, string $file, int $line) use ($db, $dialect): void {
        try {
            $qb = new QueryBuilder($dialect);
            $qb->table('phpErrorLog');
            $sql = $qb->insert([
                'pelSeverity' => $severity,
                'pelMessage'  => $message,
                'pelFile'     => $file,
                'pelLine'     => $line
            ]);
            $stmt = $db->prepare($sql);
            $qb->bindTo($stmt);
            $stmt->execute();
        } catch (Throwable $e) {
            // Silently fail — avoid infinite error loops
            error_log("phpErrorLog DB write failed: " . $e->getMessage());
        }
    };

    // Handles E_WARNING, E_NOTICE, E_USER_*, E_DEPRECATED, etc.
    set_error_handler(
        function(int $errno, string $errstr, string $errfile, int $errline)
            use ($db, $severityMap, $writeToDb): bool
        {
            $severity = $severityMap[$errno] ?? "E_UNKNOWN($errno)";
            $writeToDb($severity, $errstr, $errfile, $errline);
            // Return false so PHP's built-in handler also runs (keeps stdout logging)
            return false;
        }
    );

    // Catches fatal errors (E_ERROR, E_PARSE, etc.) that set_error_handler cannot
    register_shutdown_function(function() use ($writeToDb): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            $writeToDb("E_FATAL({$error['type']})", $error['message'], $error['file'], $error['line']);
        }
    });
}
