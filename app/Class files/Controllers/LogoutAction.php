<?php
class LogoutAction implements ControllerInterface {
    public static string $path = '/logout';
    public bool $isAction = true;

    public function execute(Request $request) {
        global $sessionController;

        Hyperlink::handleAction($sessionController);

        $sessionController->destroySession();

        if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
            echo json_encode(['redirect' => '/']);
            exit;
        }

        header("location:/");
        exit;
    }
}
?>
