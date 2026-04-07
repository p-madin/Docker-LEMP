<?php
interface ControllerInterface {
    public function execute(Request $request);
}

$controllerList = [];

// Controllers
include_once(__DIR__ . "/../Controllers/IndexController.php");
include_once(__DIR__ . "/../Controllers/LoginAction.php");
include_once(__DIR__ . "/../Controllers/RegisterAction.php");
include_once(__DIR__ . "/../Controllers/LogoutAction.php");
include_once(__DIR__ . "/../Controllers/UpdateAccountAction.php");
include_once(__DIR__ . "/../Controllers/EditNavbarAction.php");
include_once(__DIR__ . "/../Controllers/EditFormAction.php");
include_once(__DIR__ . "/../Controllers/EditColumnAction.php");
?>
