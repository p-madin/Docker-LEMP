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
     * @return DOMElement The created form element.
     */
    public function appendHyperlinkForm($dom, $parent, $label, $url) {
        // Safety Check: Avoid nested forms
        $currentNode = $parent;
        while ($currentNode && $currentNode->nodeName !== '#document') {
            if ($currentNode->nodeName === 'form') {
                throw new \Exception("configurationException: Hyperlink widget cannot be nested inside another form.");
            }
            $currentNode = $currentNode->parentNode;
        }

        $isAction = (strpos($url, '-action.php') !== false);
        $method = "POST";
        
        $formId = "nav_" . str_replace([' ', '.'], '_', $label);
        $form = new xmlForm($formId, $dom, $parent);
        
        // If it's an action, we POST directly to it. 
        // If it's a page, we POST to self for PRG handling in config.php.
        $target = $isAction ? $url : "";
        $form->prep($target, $method, false); 
        $form->formWrapper->setAttribute("class", "form_hyperlink");

        if (!$isAction) {
            $form->addInput($form->formWrapper, 'nav_target', 'hidden', $url);
        }

        $form->formWrapper->setAttribute('id', $formId);
        $hyperlink_attributes = ['value' => $label, 
                                'class' => 'navbar-link-button'];
        
        $form->addHyperlinkSubmit($form->formWrapper, $formId, $hyperlink_attributes);

        return $form->formWrapper;
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
