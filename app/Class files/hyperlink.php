<?php

class Hyperlink {
    /**
     * Renders a "hyperlink" using an HTML form to support the PRG (Post-Redirect-Get) pattern.
     * 
     * @param object $dom The xmlDom instance.
     * @param DOMNode $parent The parent node to append the form to.
     * @param string $label The text to display on the "link".
     * @param string $url The target URL to redirect to.
     * @return DOMElement The created form element.
     */
    public function render($dom, $parent, $label, $url) {
        $form = new xmlForm("nav_" . str_replace(' ', '_', $label), $dom, $parent);
        $form->prep("", "POST", false); // PRG: POST to self, no flex-table

        // Reuse xmlForm's input generation
        $form->addInput($form->formWrapper, 'nav_target', 'hidden', $url);

        // Reuse xmlForm's submit generation with custom styling
        $form->addSubmit($form->formWrapper, "nav_" . str_replace(' ', '_', $label), ['value' => $label,'class' => 'navbar-link-button']);
        
        return $form->formWrapper;
    }
}
