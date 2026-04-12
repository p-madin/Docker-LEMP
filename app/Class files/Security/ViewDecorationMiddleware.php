<?php

class ViewDecorationMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db, $dialect, $systemConfigController, $sessionController, $dom, $formSchemas;

        // 1. Generate Global Form Schemas
        $formSchemas = DatabaseForm::generateGlobalSchemas($db, $dialect);

        // 2. Initialize Navbar
        $navbar = new Navbar($systemConfigController);

        // 3. Decorate DOM
        $dom->decorate_javascript();
        $dom->decorate_cascade();
        $dom->decorate_navbar($navbar, $sessionController);

        return $next($request);
    }
}
