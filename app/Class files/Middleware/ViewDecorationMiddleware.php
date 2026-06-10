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
        $assetManager->registerJs('/Static/action-tracker.js');
        $assetManager->registerJs('/Static/behaviour.js');
        $assetManager->registerCss('/Static/action-tracker.css');
        $assetManager->registerCss('/Static/styles.css');
        $assetManager->registerCss('/Static/dataGraphStyles.css');

        // 4. Execute Controller/Pipeline
        $result = $next($request);
        
        // 5. Decorate DOM
        $meta = $dom->dom->createElement('meta');
        $meta->setAttribute('name', 'csrf-token');
        $meta->setAttribute('content', $sessionController->getCSRFToken());
        $dom->head->appendChild($meta);

        $metaUserId = $dom->dom->createElement('meta');
        $metaUserId->setAttribute('name', 'session-user-id');
        $metaUserId->setAttribute('content', $sessionController->getSystemUserId() ?: '');
        $dom->head->appendChild($metaUserId);

        $dom->injectAssets($assetManager);
        $dom->decorate_navbar($navbar, $sessionController, $assetManager);
        
        return $result;
    }
}
