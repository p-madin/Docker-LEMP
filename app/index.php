<?php
include_once("Class files/config.php");

// Autoload or include router components
include_once("Class files/Router/Request.php");
include_once("Class files/Router/MiddlewareInterface.php");
include_once("Class files/Router/Router.php");

// Security Middleware
include_once("Class files/Security/RateLimiter.php");
include_once("Class files/Security/WafMiddleware.php");
include_once("Class files/Security/CsrfMiddleware.php");

$router = new Router();
$request = new Request();

// Global Middlewares
$router->use(new WafMiddleware());
$router->use(new CsrfMiddleware());

// Routes
$router->get('/', new IndexController());
$router->get('/index.php', new IndexController());
$router->post('/login', new LoginAction());
$router->post('/register', new RegisterAction());
$router->post('/logout', new LogoutAction());
$router->post('/editAccount', new UpdateAccountAction());
$router->post('/editColumn', new EditColumnAction());
$router->post('/editForm', new EditFormAction());
$router->post('/editNavbar', new EditNavbarAction());
$router->get('/logout', new LogoutAction());

include_once("Class files/Controllers/EditColumnAction.php");
include_once("Class files/Controllers/EditFormAction.php");
include_once("Class files/Controllers/EditNavbarAction.php");
include_once("Class files/Controllers/UpdateAccountAction.php");

$router->post('/edit_column_action.php', new EditColumnAction());
$router->post('/edit_form_action.php', new EditFormAction());
$router->post('/edit_navbar_action.php', new EditNavbarAction());
$router->post('/update_account_action.php', new UpdateAccountAction());

// Dispatch Request
try {
    $router->dispatch($request);
} catch (Exception $e) {
    http_response_code(500);
    echo "Internal Server Error";
    error_log($e->getMessage());
}
?>

