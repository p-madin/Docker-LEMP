<?php

class SessionMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db, $dialect, $sessionController;

        // 1. Session Initialization
        $sessionController = new SessionController($db, $dialect);
        $sessionController->seed();

        // 2. PRG Redirect Handler
        // Hyperlink depends on SessionController
        Hyperlink::handleRedirect($sessionController);

        return $next($request);
    }
}
