<?php
class FormManagementController implements ControllerInterface, DataProviderInterface {
    public static string $path = '/form_management';
    public bool $isAction = false;

    public function getColumns(): array {
        return [
            ['key' => 'tfPK', 'label' => 'ID'],
            ['key' => 'tfName', 'label' => 'Form Name'],
            ['key' => 'columnCount', 'label' => 'Fields'],
            ['key' => 'tfReadOnly', 'label' => 'Read Only', 'action' => 'status_badge', 'actionConfig' => ['true' => 'Yes', 'false' => 'No', 'trueColor' => 'orange', 'falseColor' => 'green']],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                [
                    'type' => 'button_form', 
                    'config' => ['url' => '/edit_form?id=', 'param' => 'tfPK', 'buttonLabel' => 'Edit']
                ],
                [
                    'type' => 'button_form', 
                    'config' => [
                        'url' => '/editForm', 
                        'buttonLabel' => 'Delete', 
                        'params' => ['action' => 'delete', 'tfPK' => 'tfPK'], 
                        'cssClasses' => ['delete'],
                        'disableIf' => ['key' => 'tfReadOnly', 'value' => 1]
                    ]
                ]
            ]]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('tblForm')->select(['tfPK', 'tfName', 'tfReadOnly', $qb->raw('COUNT(tcPK) AS columnCount')])
                    ->leftJoin('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
                    ->groupBy(['tfPK', 'tfName', 'tfReadOnly', 'tfPK']) // Grouping by PK too to satisfy some dialects
                    ->orderBy('tfName', 'ASC')->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Form Definitions";
    }

    public function execute(Request $request) {
        $items = $this->getData();

        return View::render('management/forms', [
            'items' => $items
        ]);
    }
}
?>
