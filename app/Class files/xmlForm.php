<?php

class xmlForm{

    public $nameDependency;
    public $dom;
    public $parent;
    protected $security;
    protected $rules = [];
    protected $flashErrors = [];
    protected $flashData = [];

    public $formWrapper;
    public $tableWrapper;
    //enctype="multipart/form-data"

    public function __construct($nameDependency ,$dom, $parent){
        $this->nameDependency = $nameDependency;
        $this->dom = $dom;
        $this->parent = $parent;
    }

    public function prep($action, $method, $useFlexTable = true){
        global $sessionController;
        $this->formWrapper = $this->dom->fabricateChild($this->parent, "form", ["method"=>$method, "action"=>$action, "novalidate"=>"novalidate", "class"=>"flex-form"]);
        
        // Flash Logic: Retrieve and Clear from Session immediately
        if (isset($sessionController)) {
            $allErrors = $sessionController->getPrimary('form_errors') ?? [];
            if (isset($allErrors[$this->nameDependency])) {
                $this->flashErrors = $allErrors[$this->nameDependency];
                unset($allErrors[$this->nameDependency]);
                $sessionController->setPrimary('form_errors', $allErrors);
            }

            $allData = $sessionController->getPrimary('form_data') ?? [];
            if (isset($allData[$this->nameDependency])) {
                $this->flashData = $allData[$this->nameDependency];
                unset($allData[$this->nameDependency]);
                $sessionController->setPrimary('form_data', $allData);
            }
        }

        // Auto-inject CSRF token
        if (isset($sessionController)) {
            $this->addInput($this->formWrapper, 'csrf_token', 'hidden', $sessionController->getCSRFToken());
        }

        if($useFlexTable){
            $this->tableWrapper = $this->dom->fabricateChild($this->formWrapper, "div", ["class"=>'flex-table']);
        }

        // Initialize security strategy
        $this->security = new \App\Security\SecurityValidation();
        $this->security->setStrategy(
            new \App\Security\HtmlEscapeDecorator(
                new \App\Security\WhitespaceNormalization(
                    new \App\Security\CleanSanitizer()
                )
            )
        );
    }

    public function setSecurityStrategy(\App\Security\SanitizationStrategy $strategy) {
        $this->security->setStrategy($strategy);
    }

    public function addInput($parent, $name, $type, $value = null, $attributes = []){
        $finalAttributes = array_merge(['name'=>$name, 'type'=>$type], $attributes);
        
        if($type == 'checkbox'){
            $finalAttributes['value'] = '1';
            if((int)$value === 1){
                $finalAttributes['checked'] = 'checked';
            }
        } else {
            if(!is_null($value)) $finalAttributes['value'] = $value;
        }

        return $this->dom->fabricateChild($parent, "input", $finalAttributes);
    }

    public function addSubmit($parent, $name, $attributes = []){
        $finalAttributes = array_merge(['type'=>'submit', 'id'=>'submit_'.$name.'_UI'], $attributes);
        return $this->dom->fabricateChild($parent, "input", $finalAttributes);
    }

    public function addRow($nameDependency, $label, $inputType, $value = null, $rules = null){
        if($rules){
            $this->rules[$nameDependency] = $rules;
        }

        if($inputType=='file'){
            $this->formWrapper->setAttribute("enctype", "multipart/form-data");
        }
        $my_id = $this->nameDependency."_".$nameDependency.'_UI';

        if($inputType == 'hidden'){
            $this->addInput($this->formWrapper, $nameDependency, 'hidden', $value);
            return;
        }

        $rowClass = 'flex-row';
        if ($rules) {
            $ruleArray = is_array($rules) ? $rules : explode('|', $rules);
            if (in_array('required', $ruleArray)) {
                $rowClass .= ' required';
            }
        }

        $row = $this->dom->fabricateChild($this->tableWrapper, "div", ['class'=>$rowClass]);
        $row_label_cell = $this->dom->fabricateChild($row, "div", ['class'=>'flex-cell']);
        $row_label_element = $this->dom->fabricateChild($row_label_cell, "label", ['for'=>$my_id], $label);

        $row_input_cell = $this->dom->fabricateChild($row, "div", ['class'=>'flex-cell']);
        $attributes = ['id'=>$my_id];

        // 1. Graceful Degradation: Map rules to HTML5 attributes
        if ($rules) {
            foreach ($ruleArray as $rule) {
                if ($rule === 'required') {
                    $attributes['required'] = 'required';
                } elseif (strpos($rule, 'min:') === 0) {
                    $attributes['minlength'] = substr($rule, 4);
                } elseif (strpos($rule, 'max:') === 0) {
                    $attributes['maxlength'] = substr($rule, 4);
                } elseif ($rule === 'numeric') {
                    $attributes['type'] = 'number';
                }
            }
        }
        
        // 2. Data Retention & Isolation (Flash Cache)
        if (isset($this->flashErrors[$nameDependency])) {
            $field_errors = is_array($this->flashErrors[$nameDependency]) ? $this->flashErrors[$nameDependency] : [$this->flashErrors[$nameDependency]];
            foreach ($field_errors as $error) {
                $this->dom->fabricateChild($row_input_cell, "div", ["class" => "validation-error"], $error);
            }
        }

        // Prepopulate value if it's missing (and not a password)
        if (is_null($value) && $inputType !== 'password') {
            $value = $this->flashData[$nameDependency] ?? null;
        }

        $row_input_element = $this->addInput($row_input_cell, $nameDependency, $inputType, $value, $attributes);
    }

    public function submitRow(){
        $row = $this->dom->fabricateChild($this->tableWrapper, "div", ['class'=>'flex-row']);
        $empty_label_cell = $this->dom->fabricateChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_cell = $this->dom->fabricateChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_element = $this->addSubmit($submit_input_cell, $this->nameDependency);
    }

    public function buildFromSchema($schemaName, $globalSchemas, $overrideValues = []) {
        if (!isset($globalSchemas[$schemaName])) {
            return;
        }

        foreach ($globalSchemas[$schemaName] as $field) {
            $value = $overrideValues[$field['name']] ?? null;
            $this->addRow($field['name'], $field['label'], $field['type'], $value, $field['rules']);
        }
    }

    public function addMultiSelectGroup($name, $label, $data, $valueKey, $displayKey = null) {
        $section = $this->dom->fabricateChild($this->tableWrapper, "div", ["class" => "filter-group-section"]);
        $this->dom->fabricateChild($section, "h2", [], $label);
        
        // Controls
        $controls = $this->dom->fabricateChild($section, "div", ["class" => "filter-controls"]);
        $this->dom->fabricateChild($controls, "button", [
            "type" => "button", 
            "class" => "btn-select-all", 
            "data-target" => $name
        ], "Select All");
        
        $this->dom->fabricateChild($controls, "button", [
            "type" => "button", 
            "class" => "btn-unselect-all", 
            "data-target" => $name
        ], "Unselect All");
        
        // Scrollable container
        $scroll = $this->dom->fabricateChild($section, "div", [
            "class" => "filter-scroll-container",
            "style" => "max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-top: 5px;"
        ]);
        
        foreach($data as $row) {
            $val = $row[$valueKey] ?? "NULL";
            $text = is_null($displayKey) ? $val : ($row[$displayKey] ?? $val);
            $displayText = ($val === "" ? "[Empty]" : $text);
            if (isset($row['count'])) {
                $displayText .= " (" . $row['count'] . ")";
            }

            $id = $this->nameDependency . "_" . $name . "_" . md5($val);
            
            $item = $this->dom->fabricateChild($scroll, "div", ["class" => "filter-item"]);
            $this->dom->fabricateChild($item, "input", [
                "type" => "checkbox", 
                "id" => $id, 
                "name" => $name . "[]", 
                "value" => $val,
                "class" => "filter-checkbox-" . $name
            ]);
            $this->dom->fabricateChild($item, "label", ["for" => $id], $displayText);
        }
    }
}

?>