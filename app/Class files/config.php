<?php

include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/QueryBuilder.php");
include_once(__DIR__ . "/Security/SecurityValidation.php");
include_once(__DIR__ . "/session.php");
include_once(__DIR__ . "/dataGraph.php");

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

include_once(__DIR__ . "/errorHandler.php");
registerErrorHandler($db, $dialect);

$qb = new QueryBuilder($dialect);
$qb->table('sysConfig')->select(['scName', 'scValue']);
$stmt = $db->prepare($qb->toSQL());
$qb->bindTo($stmt);
$stmt->execute();
$data = $stmt->fetchAll();

$scvRows = array();

foreach($data as $key=>$value){
    $scvRows[$value['scName']] = $value['scValue'];
}
$sessionController = new SessionController($db, $dialect);
$sessionController->seed();

include_once(__DIR__ . "/preinclude.php");
include_once(__DIR__ . "/hyperlink.php");

// PRG Redirect Handler
Hyperlink::handleRedirect($sessionController);

include_once(__DIR__ . "/xmlDom.php");
include_once(__DIR__ . "/xmlForm.php");
include_once(__DIR__ . "/forms.php");
include_once(__DIR__ . "/formValidation.php");
include_once(__DIR__ . "/navbar.php");

$dom = new xmlDom();
$navbar = new Navbar();

$dom->decorate_javascript();
$dom->decorate_cascade();
$dom->decorate_navbar($navbar, $sessionController);