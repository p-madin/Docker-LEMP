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
        
        $form = new xmlForm("nav_" . str_replace([' ', '.'], '_', $label), $dom, $parent);
        
        // If it's an action, we POST directly to it. 
        // If it's a page, we POST to self for PRG handling in config.php.
        $target = $isAction ? $url : "";
        $form->prep($target, $method, false); 

        if (!$isAction) {
            $form->addInput($form->formWrapper, 'nav_target', 'hidden', $url);
        }

        $form->addSubmit($form->formWrapper, "nav_" . str_replace([' ', '.'], '_', $label), ['value' => $label, 'class' => 'navbar-link-button']);
        
        return $form->formWrapper;
    }
}
