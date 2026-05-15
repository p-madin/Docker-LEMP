<?php

class FlexTableComponent extends Component {
    protected array $columns = [];
    protected array $data = [];
    protected ?string $nestedKey = null;
    protected ?string $dataSource = null;
    protected array $dataConfig = [];
    protected ?string $dataProviderClass = null;

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

    public function setDataSource(?string $source) {
        $this->dataSource = $source;
        return $this;
    }

    public function setDataConfig(?array $config) {
        $this->dataConfig = $config ?? [];
        return $this;
    }

    public function setDataProvider(?string $className) {
        $this->dataProviderClass = $className;
        return $this;
    }

    protected function build(): void {
        if ($this->dataProviderClass && class_exists($this->dataProviderClass)) {
            $provider = new $this->dataProviderClass();
            if ($provider instanceof DataProviderInterface) {
                $this->columns = $provider->getColumns();
                $this->data = $provider->getData();
                $this->nestedKey = $provider->getNestedKey();
            }
        } else if ($this->dataSource) {
            global $db, $dialect;
            $qb = new QueryBuilder($dialect);
            $query = $qb->table($this->dataSource);
            if ($this->dataSource === 'httpAction') {
                $query->limit(10);
            }
            $rawData = $query->getFetchAll($db);
            $mapper = new \Services\GenericDataMapper();
            $this->data = $mapper->map($rawData, $this->dataConfig['mapping'] ?? []);
            $this->columns = $this->dataConfig['columns'] ?? [];
        }

        // Build Header
        $header = $this->fabricateChild($this->root, 'div', ['class' => 'flex-row flex-header', 'data-slot' => 'header']);
        foreach ($this->columns as $column) {
            $class = 'flex-cell' . (($column['isAction'] ?? false) ? ' actions-cell' : '');
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
                $details = $this->fabricateChild($container, 'details', ['class' => 'flex-row-group']);
                $summary = $this->fabricateChild($details, 'summary', ['class' => 'flex-row-summary']);
                $target = $summary;
            }

            $flexRow = $this->fabricateChild($target, 'div', [
                'class' => 'flex-row' . ($depth > 0 ? ' nested-row' : ''),
                'style' => $depth > 0 ? "padding-left: " . ($depth * 20) . "px; background: rgba(0,0,0,0.02);" : ""
            ]);

            if (isset($rowData['is_full_width']) && $rowData['is_full_width']) {
                $cell = $this->fabricateChild($flexRow, 'div', ['class' => 'flex-cell flex-wide']);
                $this->fabricateChild($cell, 'strong', [], 'Payload Data:');
                $this->fabricateChild($cell, 'pre', [], $rowData['content'] ?? '');
            } else {
                foreach ($this->columns as $col) {
                    $class = 'flex-cell' . (($col['isAction'] ?? false) ? ' actions-cell' : '');
                    if (!empty($col['cssClass'])) {
                        $class .= ' ' . $col['cssClass'];
                    }
                    $cell = $this->fabricateChild($flexRow, 'div', ['class' => $class]);
                    
                    $val = $rowData[$col['key']] ?? '';
                    
                    if (isset($col['renderCallback']) && is_callable($col['renderCallback'])) {
                        $col['renderCallback']($this->xmlDom, $cell, $rowData);
                    } else if (!$this->resolveAction($cell, $rowData, $col)) {
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
    protected function resolveAction($cell, $rowData, $col) {
        $action = $col['action'] ?? null;
        if (!$action) return false;

        if ($action === 'multi') {
            $actions = $col['actions'] ?? [];
            foreach ($actions as $actConfig) {
                $this->executeSingleAction($cell, $rowData, $actConfig['type'], $actConfig['config'] ?? [], $col['key']);
            }
            return true;
        }

        return $this->executeSingleAction($cell, $rowData, $action, $col['actionConfig'] ?? [], $col['key']);
    }

    protected function executeSingleAction($cell, $rowData, $type, $config, $colKey) {
        switch ($type) {
            case 'status_badge':
                $val = $rowData[$colKey] ?? false;
                $label = $val ? ($config['true'] ?? 'Active') : ($config['false'] ?? 'Inactive');
                $color = $val ? ($config['trueColor'] ?? 'green') : ($config['falseColor'] ?? 'red');
                $badge = $this->fabricateChild($cell, 'span', [
                    'style' => "color:$color; font-weight:bold; padding: 2px 6px; border-radius: 4px; background: rgba(0,0,0,0.05);"
                ], $label);
                return true;

            case 'link':
                $baseUrl = $config['url'] ?? '#';
                $paramKey = $config['param'] ?? $colKey;
                $val = $rowData[$paramKey] ?? '';
                $this->fabricateChild($cell, 'a', [
                    'href' => $baseUrl . $val,
                    'class' => 'table-link'
                ], $config['label'] ?? ($rowData[$colKey] ?? 'View'));
                return true;

            case 'button_form':
                $url = $config['url'] ?? '';
                $paramKey = $config['param'] ?? $colKey;
                $val = $rowData[$paramKey] ?? '';
                
                $params = [];
                if (!empty($config['params'])) {
                    foreach ($config['params'] as $pKey => $pValue) {
                        $params[$pKey] = $rowData[$pValue] ?? $pValue;
                    }
                }

                $cssClasses = $config['cssClasses'] ?? [];
                if (!empty($config['disableIf'])) {
                    $cKey = $config['disableIf']['key'] ?? null;
                    $cVal = $config['disableIf']['value'] ?? null;
                    if ($cKey && isset($rowData[$cKey]) && $rowData[$cKey] == $cVal) {
                        $cssClasses[] = 'disabled';
                    }
                }

                $hlink = new Hyperlink();
                $hlink->appendHyperlinkForm($this->xmlDom, $cell, $config['buttonLabel'] ?? 'Submit', $url . $val, $params, $cssClasses);
                return true;
        }
        return false;
    }
}
