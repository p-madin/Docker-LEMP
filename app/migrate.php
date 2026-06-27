<?php
require_once __DIR__ . '/Class files/config.php';
require_once __DIR__ . '/Class files/Services/XMLMigrator.php';

echo "Starting Database Migration...\n";

// Bootstrap the database connection without dispatching to any controller.
// DatabaseConfigMiddleware populates the $db, $dialect globals.
$request = new Request();
$dbMiddleware = new DatabaseConfigMiddleware();
$dbMiddleware->handle($request, function() {});

global $db, $dialect;

$migrator = new XMLMigrator($db, $dialect);
$ddlPath = realpath(__DIR__ . '/../conf/common/01_db_ddl.xml') ?: '/home/ubuntu/Workspace/conf/common/01_db_ddl.xml';
$dmlPath = realpath(__DIR__ . '/../conf/common/02_db_dml.xml') ?: '/home/ubuntu/Workspace/conf/common/02_db_dml.xml';

$targetEnv = getenv('TARGET_ENV') ?: 'dev';
$envDmlPath = realpath(__DIR__ . "/../conf/{$targetEnv}/03_db_dml.xml") ?: "/home/ubuntu/Workspace/conf/{$targetEnv}/03_db_dml.xml";

if (!file_exists($ddlPath) || !file_exists($dmlPath)) {
    echo "Warning: XML schema files not found at $ddlPath or $dmlPath. Are you missing /conf/common/*.xml?\n";
}

$migrator->migrate($ddlPath, $dmlPath);

if (file_exists($envDmlPath)) {
    echo "Processing environment-specific DML: $envDmlPath\n";
    $migrator->processDML($envDmlPath);
} else {
    echo "No environment-specific DML found at $envDmlPath, skipping.\n";
}

$tenantAdminPath = '/etc/tenant_admin.xml';
if (file_exists($tenantAdminPath)) {
    echo "Processing tenant admin DML: $tenantAdminPath\n";
    $migrator->processDML($tenantAdminPath);
}

echo "Database Migration Completed.\n";
