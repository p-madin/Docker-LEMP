<?php

class xmlForm{

    public $nameDependency;
    public $dom;
    public $parent;

    public $formWrapper;
    public $tableWrapper;

    public function __construct($nameDependency ,$dom, $parent){
        $this->nameDependency = $nameDependency;
        $this->dom = $dom;
        $this->parent = $parent;
    }

    public function prep($action, $method){
        $this->formWrapper = $this->dom->appendChild($this->parent, "form", ["method"=>$method, "action"=>$action]);
        $this->tableWrapper = $this->dom->appendChild($this->formWrapper, "div", ["class"=>'flex-table']);
    }

    public function addRow($nameDependency, $label, $inputType){
        $my_id = $this->nameDependency."_".$nameDependency.'_UI';

        $row = $this->dom->appendChild($this->tableWrapper, "div", ['class'=>'flex-row']);
        $row_label_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $row_label_element = $this->dom->appendChild($row_label_cell, "label", ['for'=>$my_id], $label);

        $row_input_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $row_input_element = $this->dom->appendChild($row_input_cell, "input", ['id'=>$my_id,
                                                                                'type'=>$inputType,
                                                                                'name'=>$nameDependency]);
    }

    public function submitRow(){
        $row = $this->dom->appendChild($this->tableWrapper, "div", ['class'=>'flex-row']);
        $empty_label_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_cell = $this->dom->appendChild($row, "div", ['class'=>'flex-cell']);
        $submit_input_element = $this->dom->appendChild($submit_input_cell, "input", ['id'=>'submit_'.$this->nameDependency.'_UI',
                                                                                      'type'=>'submit']);
    }
}

?>