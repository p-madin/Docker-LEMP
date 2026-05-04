<?php

class Hyperlink {
    /**
     * Renders a "hyperlink" using an HTML form to support either the PRG pattern or direct action POSTs.
     * Throws an exception if nested inside another form.
     * 
     * @param object $dom The xmlDom instance.
     * @param DOMNode $parent The parent node to append the form to.
     * @param string $label The text to display on the "link".
     * @param string $url The target URL to redirect to.
     * @param array $post_params Additional hidden inputs to include in the POST payload.
     * @param array $css_decorators Array of CSS classes (e.g., 'edit', 'delete', 'disabled', 'ajax').
     * @return DOMElement The created form element.
     */
    public function appendHyperlinkForm($dom, $parent, $label, $url, array $post_params = [], array $css_decorators = []) {
        // Safety Check: Avoid nested forms
        global $router;
        $currentNode = $parent;
        while ($currentNode && $currentNode->nodeName !== '#document') {
            if ($currentNode->nodeName === 'form') {
                throw new \Exception("configurationException: Hyperlink widget cannot be nested inside another form.");
            }
            $currentNode = $currentNode->parentNode;
        }

        $isAction = $router->isAction($url);
        $method = "POST";
        
        $formId = "nav_" . str_replace([' ', '.'], '_', $label);
        $form = new xmlForm($formId, $dom);
        
        // If it's an action, we POST directly to it. 
        // If it's a page, we POST to self for PRG handling in config.php.
        $target = $isAction ? $url : "";
        $form->prep($target, $method, true); 
        $form->setCompact(true);
        $formClass = "form_hyperlink";
        if (!empty($css_decorators)) {
            $formClass .= ' ' . implode(' ', $css_decorators);
        }
        $form->formWrapper->setAttribute("class", $formClass);

        if (!$isAction) {
            $form->addField('nav_target', '', 'hidden', $url);
        }

        foreach ($post_params as $key => $value) {
            $form->addField($key, '', 'hidden', $value);
        }

        $form->formWrapper->setAttribute('id', $formId);
        $classString = 'navbar-link-button';
        if (!empty($css_decorators)) {
            $classString .= ' ' . implode(' ', $css_decorators);
        }

        $hyperlink_attributes = [
            'value' => $label, 
            'class' => $classString
        ];
        
        // Onclick confirms are handled dynamically in validator.js using the class selector
        if (in_array('disabled', $css_decorators)) {
            $hyperlink_attributes['disabled'] = 'disabled';
        }
        
        $form->addHyperlinkSubmit($formId, $hyperlink_attributes);

        $element = $form->render();
        $parent->append($element);
        return $element;
    }

    /**
     * Handles POST requests for navigation targets (PRG pattern).
     * 
     * @param object $sessionController The modern SessionController instance.
     * @return void
     */
    public static function handleRedirect($sessionController) {
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nav_target'])) {
            if ($sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $security = new \App\Security\SecurityValidation();
                $security->setStrategy(new \App\Security\RootRelativePathDecorator(new \App\Security\CleanSanitizer()));
                $target = $security->process($_POST['nav_target'] ?? '/');
                
                if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
                    echo json_encode(['redirect' => $target]);
                    exit;
                }
                $sessionController->completeTransaction();

                header("Location: " . $target);
                exit;
            } else {
                http_response_code(400);
                $sessionController->destroySession();
                echo "400 Bad Request: CSRF token validation failed.";
                exit;
            }
        }
    }

    /**
     * Handles POST requests for direct action targets.
     * 
     * @param object $sessionController
     * @return void
     */
    public static function handleAction($sessionController) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
             if (!$sessionController->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                http_response_code(400);
                $sessionController->destroySession();
                echo "400 Bad Request: CSRF token validation failed.";
                exit;
            }
        }
    }
}
