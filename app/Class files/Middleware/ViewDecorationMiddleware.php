<?php

class ViewDecorationMiddleware implements MiddlewareInterface {
    public function handle(Request $request, Closure $next) {
        global $db, $dialect, $systemConfigController, $sessionController, $dom, $formSchemas, $assetManager;

        // 1. Generate Global Form Schemas
        $formSchemas = DatabaseForm::generateGlobalSchemas($db, $dialect);

        // 2. Initialize Navbar
        $navbar = new Navbar($systemConfigController);

        // 3. Register Assets
        $assetManager->registerJs('/Static/exceptions.js');
        $assetManager->registerJs('/Static/validator.js');
        $assetManager->registerCss('/Static/styles.css');
        $assetManager->registerCss('/Static/dataGraphStyles.css');

        // 4. Decorate DOM
        $dom->injectAssets($assetManager);
        $dom->decorate_navbar($navbar, $sessionController, $assetManager);

        return $next($request);
    }
}
