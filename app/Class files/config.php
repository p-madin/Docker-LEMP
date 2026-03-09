<?php

include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/session.php");
include_once(__DIR__ . "/dataGraph.php");

$host = '172.20.1.200';
$dbname = 'stackDB';
$username = 'docker_user_lemp';
$password = 'docker_user_lemp';

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$db_controller = new db_connect_controller($dsn, $username, $password, $options);
$db = $db_controller->connect();

$data = $db->query("SELECT scName, scValue FROM sysConfig");

$scvRows = array();

foreach($data as $key=>$value){
    $scvRows[$value['scName']] = $value['scValue'];
}
$sessionController = new SessionController($db);
$sessionController->seed();

include_once(__DIR__ . "/preinclude.php");
include_once(__DIR__ . "/navbar.php");
include_once(__DIR__ . "/hyperlink.php");

// PRG Redirect Handler
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nav_target'])) {
    if ($sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        header("Location: " . $_POST['nav_target']);
        exit;
    } else {
        $sessionController->destroySession();
        header("Location: /index.php?error=csrf");
        exit;
    }
}

include_once(__DIR__ . "/xmlDom.php");
include_once(__DIR__ . "/xmlForm.php");

$dom = new xmlDom();
$navbar = new Navbar();

$dom->decorate_javascript();
$dom->decorate_cascade();
$dom->decorate_navbar($navbar, $sessionController);