<?php

class xmlForm{

    public $nameDependency;
    public $dom;
    public $parent;

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
        $this->formWrapper = $this->dom->appendChild($this->parent, "form", ["method"=>$method, "action"=>$action]);
        
        // Auto-inject CSRF token
        if (isset($sessionController)) {
            $this->addInput($this->formWrapper, 'csrf_token', 'hidden', $sessionController->getCSRFToken());
        }

        if($useFlexTable){
            $this->tableWrapper = $this->dom->appendChild($this->formWrapper, "div", ["class"=>'flex-table']);
        }
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

        return $this->dom->appendChild($parent, "input", $finalAttributes);
    }

    public function addSubmit($parent, $name, $attributes = []){
        $finalAttributes = array_merge(['type'=>'submit', 'id'=>'submit_'.$name.'_UI'], $attributes);
        return $this->dom->appendChild($parent, "input", $finalAttributes);
    }

    public function addRow($nameDependency, $label, $inputType, $value = null){
        if($inputType=='file'){
            $this->formWrapper->setAttribute("enctype", "multipart/form-data");
        }
        $my_id = $this->nameDependency."_".$nameDependency.'_UI';

        if($inputType == 'hidden'){
            $this->addInput($this->formWrapper, $nameDependency, 'hidden', $value);
            return;
        }

        $row = $this->dom->appendChild($this->tableWrapper, "div", ['class'=>'flex-row']);
        $row_label_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $row_label_element = $this->dom->appendChild($row_label_cell, "label", ['for'=>$my_id], $label);

        $row_input_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $attributes = ['id'=>$my_id];
        
        $row_input_element = $this->addInput($row_input_cell, $nameDependency, $inputType, $value, $attributes);
    }

    public function submitRow(){
        $row = $this->dom->appendChild($this->tableWrapper, "div", ['class'=>'flex-row']);
        $empty_label_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_element = $this->addSubmit($submit_input_cell, $this->nameDependency);
    }

    public function addMultiSelectGroup($name, $label, $data, $valueKey, $displayKey = null) {
        $section = $this->dom->appendChild($this->tableWrapper, "div", ["class" => "filter-group-section"]);
        $this->dom->appendChild($section, "h2", [], $label);
        
        // Controls
        $controls = $this->dom->appendChild($section, "div", ["class" => "filter-controls"]);
        $this->dom->appendChild($controls, "button", [
            "type" => "button", 
            "class" => "btn-select-all", 
            "data-target" => $name
        ], "Select All");
        
        $this->dom->appendChild($controls, "button", [
            "type" => "button", 
            "class" => "btn-unselect-all", 
            "data-target" => $name
        ], "Unselect All");
        
        // Scrollable container
        $scroll = $this->dom->appendChild($section, "div", [
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
            
            $item = $this->dom->appendChild($scroll, "div", ["class" => "filter-item"]);
            $this->dom->appendChild($item, "input", [
                "type" => "checkbox", 
                "id" => $id, 
                "name" => $name . "[]", 
                "value" => $val,
                "class" => "filter-checkbox-" . $name
            ]);
            $this->dom->appendChild($item, "label", ["for" => $id], $displayText);
        }
    }
}

?>