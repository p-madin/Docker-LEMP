<?php

class xmlForm extends FormComponent {
    public \DOM\Element $formWrapper;

    public function __construct($nameDependency, xmlDom $xmlDom, $parent = null) {
        parent::__construct($xmlDom, $nameDependency);
        $this->formWrapper = $this->root; // Compatibility
    }

    public function prep($action, $method, $useFlexTable = true) {
        $this->setAction($action);
        $this->setMethod($method);
        $this->setUseFlexTable($useFlexTable);
    }

    public function addRow($name, $label, $type, $value = null, $rules = null) {
        $this->addField($name, $label, $type, $value, $rules);
    }

    public function submitRow() {
        // Default submit row
        $this->addSubmit('Submit');
    }

    public function addSubmit(string $label = 'Submit', array $attributes = []) {
        return parent::addSubmit($label, $attributes);
    }

    public function addHyperlinkSubmit(string $label, array $attributes = []) {
        return parent::addHyperlinkSubmit($label, $attributes);
    }
}

?>