<?php
class ExtranetMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $router, $sessionController;

        $path = $request->getPath();

        if ($router->isProtected($path)) {
            if (!$sessionController->isLoggedIn()) {
                // Determine if we should redirect or respond with JSON
                if (isset($request->server['HTTP_ACCEPT']) && strpos($request->server['HTTP_ACCEPT'], 'application/json') !== false) {
                    http_response_code(401);
                    echo json_encode(['error' => 'Unauthorized', 'redirect' => '/']);
                    exit;
                }

                header("Location: /");
                exit;
            }
        }

        return $next($request);
    }
}
?>
