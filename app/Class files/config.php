<?php

include_once(__DIR__ . "/db.php");
include_once(__DIR__ . "/Security/SecurityValidation.php");
include_once(__DIR__ . "/session.php");
include_once(__DIR__ . "/dataGraph.php");

$host = getenv('DB_HOST') ?: 'db';
$dbname = getenv('DB_NAME') ?: 'stackDB';
$username = getenv('DB_USER') ?: 'docker_user_lemp';
$password = getenv('DB_PASS') ?: 'docker_user_lemp';

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

// PRG Redirect Handler
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nav_target'])) {
    if ($sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $security = new \App\Security\SecurityValidation();
        $security->setStrategy(new \App\Security\RootRelativePathDecorator(new \App\Security\CleanSanitizer()));
        $target = $security->process($_POST['nav_target'] ?? '/');
        
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => $target]);
            exit;
        }

        header("Location: " . $target);
        exit;
    } else {
        $sessionController->destroySession();
        $target = "/index.php?error=csrf";
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => $target]);
            exit;
        }
        header("Location: " . $target);
        exit;
    }
}

include_once(__DIR__ . "/xmlDom.php");
include_once(__DIR__ . "/xmlForm.php");
include_once(__DIR__ . "/forms.php");
include_once(__DIR__ . "/formValidation.php");
include_once(__DIR__ . "/navbar.php");
include_once(__DIR__ . "/hyperlink.php");

$dom = new xmlDom();
$navbar = new Navbar();

$dom->decorate_javascript();
$dom->decorate_cascade();
$dom->decorate_navbar($navbar, $sessionController);