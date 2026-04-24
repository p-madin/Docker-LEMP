<?php

class FormComponent extends Component {
    protected string $formName;
    protected string $action = '';
    protected string $method = 'POST';
    protected bool $useFlexTable = true;
    protected bool $compact = false;
    protected array $fields = [];
    protected array $flashErrors = [];
    protected array $flashData = [];

    protected ?\DOM\Element $tableWrapper = null;

    public function __construct(xmlDom $xmlDom, string $formName, array $attributes = []) {
        $finalAttributes = array_merge([
            'method' => 'POST',
            'novalidate' => 'novalidate',
            'class' => 'flex-form'
        ], $attributes);
        
        parent::__construct($xmlDom, 'form', $finalAttributes);
        $this->formName = $formName;
        $this->loadFlashData();
        
        // Initialize security strategy (Specific to forms)
        $this->security = new \App\Security\SecurityValidation();
        $this->security->setStrategy(
            new \App\Security\HtmlEscapeDecorator(
                new \App\Security\WhitespaceNormalization(
                    new \App\Security\CleanSanitizer()
                )
            )
        );
    }

    public function setAction(string $action) {
        $this->action = $action;
        $this->root->setAttribute('action', $action);
        return $this;
    }

    public function setMethod(string $method) {
        $this->method = $method;
        $this->root->setAttribute('method', $method);
        return $this;
    }

    public function setUseFlexTable(bool $use) {
        $this->useFlexTable = $use;
        return $this;
    }

    public function setCompact(bool $compact) {
        $this->compact = $compact;
        return $this;
    }

    protected function loadFlashData() {
        global $sessionController;
        if (isset($sessionController)) {
            $allErrors = $sessionController->getPrimary('form_errors') ?? [];
            if (isset($allErrors[$this->formName])) {
                $this->flashErrors = $allErrors[$this->formName];
                unset($allErrors[$this->formName]);
                $sessionController->setPrimary('form_errors', $allErrors);
            }

            $allData = $sessionController->getPrimary('form_data') ?? [];
            if (isset($allData[$this->formName])) {
                $this->flashData = $allData[$this->formName];
                unset($allData[$this->formName]);
                $sessionController->setPrimary('form_data', $allData);
            }
        }
    }

    public function addField(string $name, string $label, string $type, $value = null, $rules = null) {
        $this->fields[] = [
            'type' => 'row',
            'name' => $name,
            'label' => $label,
            'inputType' => $type,
            'value' => $value,
            'rules' => $rules
        ];
        return $this;
    }

    public function addSubmit(string $label = 'Submit', array $attributes = []) {
        $this->fields[] = [
            'type' => 'submit',
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    public function addHyperlinkSubmit(string $label, array $attributes = []) {
        $this->fields[] = [
            'type' => 'hyperlink-submit',
            'label' => $label,
            'attributes' => $attributes
        ];
        return $this;
    }

    public function addMultiSelectGroup($name, $label, $data, $valueKey, $displayKey = null) {
        $this->fields[] = [
            'type' => 'multi-select',
            'name' => $name,
            'label' => $label,
            'data' => $data,
            'valueKey' => $valueKey,
            'displayKey' => $displayKey
        ];
        return $this;
    }

    public function buildFromSchema(string $schemaName, array $globalSchemas, array $overrideValues = []) {
        if (!isset($globalSchemas[$schemaName])) return $this;
        foreach ($globalSchemas[$schemaName] as $field) {
            $value = $overrideValues[$field['name']] ?? null;
            $this->addField($field['name'], $field['label'], $field['type'], $value, $field['rules']);
        }
        return $this;
    }

    protected function build(): void {
        global $sessionController;

        if (isset($sessionController)) {
            $this->fabricateChild($this->root, 'input', [
                'type' => 'hidden',
                'name' => 'csrf_token',
                'value' => $sessionController->getCSRFToken()
            ]);
        }

        if ($this->useFlexTable) {
            $this->tableWrapper = $this->fabricateChild($this->root, 'div', ['class' => 'flex-table']);
        }

        foreach ($this->fields as $field) {
            switch ($field['type']) {
                case 'row': $this->renderRow($field); break;
                case 'submit': $this->renderSubmit($field); break;
                case 'hyperlink-submit': $this->renderHyperlinkSubmit($field); break;
                case 'multi-select': $this->renderMultiSelect($field); break;
            }
        }
    }

    protected function renderRow(array $field) {
        $name = $field['name'];
        $label = $field['label'];
        $type = $field['inputType'];
        $value = $field['value'];
        $rules = $field['rules'];

        if ($type === 'hidden') {
            $this->fabricateChild($this->root, 'input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
            return;
        }

        if ($type === 'file') {
            $this->root->setAttribute('enctype', 'multipart/form-data');
        }

        $my_id = $this->formName . "_" . $name . "_UI";
        $rowClass = 'flex-row';
        $attributes = ['id' => $my_id, 'placeholder' => $label];

        if ($rules) {
            $formattedRules = [];
            $rulesArray = is_array($rules) ? $rules : $this->parseRules($rules);
            
            foreach ($rulesArray as $rName => $val) {
                if ($val === true) {
                    $formattedRules[] = $rName;
                } elseif (is_array($val)) {
                    $formattedRules[] = $rName . ":" . implode(',', $val);
                } else {
                    $formattedRules[] = $rName . ":" . $val;
                }
            }
            $attributes['data-rules'] = implode('|', $formattedRules);
            
            $parsedRules = $this->parseRules($rules);
            if (!empty($parsedRules['required'])) {
                $rowClass .= ' required';
                $attributes['required'] = 'required';
            }
            if (isset($parsedRules['min'])) $attributes['minlength'] = (string)$parsedRules['min'];
            if (isset($parsedRules['max'])) $attributes['maxlength'] = (string)$parsedRules['max'];
            if (!empty($parsedRules['numeric'])) $attributes['type'] = 'number';
        }

        $row = $this->fabricateChild($this->tableWrapper ?? $this->root, 'div', ['class' => $rowClass]);
        $labelCell = $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);
        $this->fabricateChild($labelCell, 'label', ['for' => $my_id], $label . ":");

        $inputCell = $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);

        if (isset($this->flashErrors[$name])) {
            foreach ((array)$this->flashErrors[$name] as $error) {
                $this->fabricateChild($inputCell, 'div', ['class' => 'validation-error'], $error);
            }
        }

        if (is_null($value) && $type !== 'password') {
            $value = $this->flashData[$name] ?? null;
        }

        $inputAttrs = array_merge(['name' => $name, 'type' => $type], $attributes);
        if ($type === 'checkbox') {
            $inputAttrs['value'] = '1';
            if ((int)$value === 1) $inputAttrs['checked'] = 'checked';
        } elseif (!is_null($value)) {
            $inputAttrs['value'] = (string)$value;
        }

        $this->fabricateChild($inputCell, 'input', $inputAttrs);
    }

    protected function renderSubmit(array $field) {
        $row = $this->fabricateChild($this->tableWrapper ?? $this->root, 'div', ['class' => 'flex-row']);
        if ($this->useFlexTable && !$this->compact) {
            $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);
        }
        $cell = $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);
        
        $label = $field['attributes']['value'] ?? $field['label'];
        $attrs = array_merge([
            'type' => 'submit',
            'value' => $label,
            'id' => 'submit_' . $this->formName . '_UI'
        ], $field['attributes'] ?? []);
        
        return $this->fabricateChild($cell, 'input', $attrs);
    }

    protected function renderHyperlinkSubmit(array $field) {
        $row = $this->fabricateChild($this->tableWrapper ?? $this->root, 'div', ['class' => 'flex-row']);
        if ($this->useFlexTable && !$this->compact) {
            $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);
        }
        $cell = $this->fabricateChild($row, 'div', ['class' => 'flex-cell']);

        $label = $field['attributes']['value'] ?? $field['label'];
        $attrs = array_merge([
            'id' => 'submit_' . $this->formName . '_UI',
            'href' => '#'
        ], $field['attributes'] ?? []);
        unset($attrs['value']);

        return $this->fabricateChild($cell, 'a', $attrs, $label);
    }

    protected function renderMultiSelect(array $field) {
        $name = $field['name'];
        $label = $field['label'];
        $data = $field['data'];
        $valueKey = $field['valueKey'];
        $displayKey = $field['displayKey'];

        $section = $this->fabricateChild($this->tableWrapper ?? $this->root, 'div', ['class' => 'filter-group-section']);
        $this->fabricateChild($section, 'h2', [], $label);
        
        $controls = $this->fabricateChild($section, 'div', ['class' => 'filter-controls']);
        $this->fabricateChild($controls, 'button', ['type' => 'button', 'class' => 'btn-select-all', 'data-target' => $name], 'Select All');
        $this->fabricateChild($controls, 'button', ['type' => 'button', 'class' => 'btn-unselect-all', 'data-target' => $name], 'Unselect All');
        
        $scroll = $this->fabricateChild($section, 'div', [
            'class' => 'filter-scroll-container',
            'style' => 'max-height: 200px; overflow-y: auto; border: 1px solid #ccc; padding: 10px; margin-top: 5px;'
        ]);
        
        foreach ($data as $row) {
            $val = $row[$valueKey] ?? 'NULL';
            $text = is_null($displayKey) ? $val : ($row[$displayKey] ?? $val);
            $displayText = ($val === '' ? '[Empty]' : $text);
            if (isset($row['count'])) $displayText .= " (" . $row['count'] . ")";

            $id = $this->formName . "_" . $name . "_" . md5($val);
            $item = $this->fabricateChild($scroll, 'div', ['class' => 'filter-item']);
            $this->fabricateChild($item, 'input', [
                'type' => 'checkbox', 'id' => $id, 'name' => $name . "[]", 'value' => $val, 'class' => 'filter-checkbox-' . $name
            ]);
            $this->fabricateChild($item, 'label', ['for' => $id], $displayText);
        }
    }

    protected function parseRules($rules) {
        if (is_array($rules)) return $rules;
        $ruleArray = [];
        $parts = explode('|', $rules);
        foreach ($parts as $part) {
            if (strpos($part, ':') !== false) {
                list($r, $p) = explode(':', $part, 2);
                $ruleArray[$r] = $p;
            } else {
                $ruleArray[$part] = true;
            }
        }
        return $ruleArray;
    }
}
