<?php

class CspMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $assetManager;

        if (isset($assetManager)) {
            $nonce = $assetManager->getNonce();
            $policy = "default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;";
            header("Content-Security-Policy: " . $policy);
        }

        return $next($request);
    }
}
?>
