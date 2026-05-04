<?php

class TransactionEndMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $sessionController;
        $sessionController->completeTransaction();
        return $next($request);
    }
}
