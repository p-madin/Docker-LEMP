<?php

class FlexTableComponent extends Component {
    protected array $columns = [];
    protected array $data = [];
    protected ?string $nestedKey = null;

    public function __construct(xmlDom $xmlDom, array $attributes = []) {
        $finalAttributes = array_merge(['class' => 'flex-table'], $attributes);
        parent::__construct($xmlDom, 'div', $finalAttributes);
    }

    public function setColumns(array $columns) {
        $this->columns = $columns;
        return $this;
    }

    public function setData(array $data) {
        $this->data = $data;
        return $this;
    }

    public function setNestedKey(?string $key) {
        $this->nestedKey = $key;
        return $this;
    }

    protected function build(): void {
        // Build Header
        $header = $this->fabricateChild($this->root, 'div', ['class' => 'flex-row flex-header', 'data-slot' => 'header']);
        foreach ($this->columns as $column) {
            $class = 'flex-cell' . ($column['isAction'] ? ' actions-cell' : '');
            if (!empty($column['cssClass'])) {
                $class .= ' ' . $column['cssClass'];
            }
            $this->fabricateChild($header, 'div', ['class' => $class], $column['label']);
        }

        // Build Body Rows
        $body = $this->fabricateChild($this->root, 'div', ['data-slot' => 'body', 'style' => 'width: 100%']);
        if (empty($this->data)) {
            $row = $this->fabricateChild($body, 'div', ['class' => 'flex-row']);
            $this->fabricateChild($row, 'div', ['class' => 'flex-cell', 'style' => 'text-align: center;'], 'No records found.');
        } else {
            $this->renderRows($body, $this->data);
        }
    }

    protected function renderRows($container, array $data, $depth = 0): void {
        foreach ($data as $rowData) {
            $hasChildren = ($this->nestedKey && !empty($rowData[$this->nestedKey]));
            
            $target = $container;
            if ($hasChildren) {
                $details = $this->fabricateChild($container, 'details', [
                    'class' => 'flex-row-group',
                    'style' => 'width: 100%; border-bottom: 1px solid #eee;'
                ]);
                $summary = $this->fabricateChild($details, 'summary', ['class' => 'flex-row-summary', 'style' => 'list-style: none; outline: none; cursor: pointer;']);
                $target = $summary;
            }

            $flexRow = $this->fabricateChild($target, 'div', [
                'class' => 'flex-row' . ($depth > 0 ? ' nested-row' : ''),
                'style' => $depth > 0 ? "padding-left: " . ($depth * 20) . "px; background: rgba(0,0,0,0.02);" : ""
            ]);

            if (isset($rowData['is_full_width']) && $rowData['is_full_width']) {
                $cell = $this->fabricateChild($flexRow, 'div', [
                    'class' => 'flex-cell flex-wide',
                    'style' => 'width: 100%; border-top: 1px dashed #ccc; padding: 15px; background: #fff;'
                ]);
                $this->fabricateChild($cell, 'strong', [], 'Payload Data:');
                $this->fabricateChild($cell, 'pre', [
                    'style' => 'background: #f4f4f4; padding: 10px; border-radius: 4px; overflow: auto; margin-top: 5px;white-space: pre-wrap'
                ], $rowData['content'] ?? '');
            } else {
                foreach ($this->columns as $col) {
                    $class = 'flex-cell' . ($col['isAction'] ? ' actions-cell' : '');
                    if (!empty($col['cssClass'])) {
                        $class .= ' ' . $col['cssClass'];
                    }
                    $cell = $this->fabricateChild($flexRow, 'div', ['class' => $class]);
                    
                    $val = $rowData[$col['key']] ?? '';
                    
                    if (isset($col['renderCallback']) && is_callable($col['renderCallback'])) {
                        $col['renderCallback']($this->xmlDom, $cell, $rowData);
                    } else {
                        $cell->textContent = $this->security->process((string)$val);
                    }
                }
            }

            if ($hasChildren) {
                $childrenContainer = $this->fabricateChild($details, 'div', ['class' => 'flex-nested-container']);
                $this->renderRows($childrenContainer, $rowData[$this->nestedKey], $depth + 1);
            }
        }
    }
}
