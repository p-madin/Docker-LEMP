<?php
class FormDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [
            ['key' => 'tfPK', 'label' => 'ID'],
            ['key' => 'tfName', 'label' => 'Form Name'],
            ['key' => 'columnCount', 'label' => 'Fields'],
            ['key' => 'tfReadOnly', 'label' => 'Read Only', 'action' => 'status_badge', 'actionConfig' => ['true' => 'Yes', 'false' => 'No', 'trueColor' => 'orange', 'falseColor' => 'green']],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'multi', 'actions' => [
                [
                    'type' => 'button_form', 
                    'config' => ['url' => '/edit_form?id=', 'param' => 'tfPK', 'buttonLabel' => 'Edit', 'idPrefix' => 'edit-form-', 'idSuffixKey' => 'tfPK']
                ],
                [
                    'type' => 'button_form', 
                    'config' => [
                        'url' => '/editForm', 
                        'buttonLabel' => 'Delete', 
                        'params' => ['action' => 'delete', 'tfPK' => 'tfPK'], 
                        'cssClasses' => ['delete'],
                        'disableIf' => ['key' => 'tfReadOnly', 'value' => 1],
                        'idPrefix' => 'delete-form-',
                        'idSuffixKey' => 'tfPK'
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
                    ->groupBy(['tfPK', 'tfName', 'tfReadOnly', 'tfPK'])
                    ->orderBy('tfName', 'ASC')->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Form Definitions";
    }
}
