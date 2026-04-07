<?php
class CsrfMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        $method = $request->getMethod();
        if (in_array($method, ['POST', 'PUT', 'DELETE'])) {
            global $sessionController;
            $token = $request->post['csrf_token'] ?? '';
            if (empty($token) || $sessionController->getPrimary('csrf_token') !== $token) {
                http_response_code(403);
                die("403 Forbidden: Invalid CSRF Token"); 
            }
        }
        return $next($request);
    }
}
?>
