<?php
class FormSchemaDataProvider implements DataProviderInterface {
    protected string $formName;

    public function __construct(string $formName = '') {
        $this->formName = $formName;
    }

    public function setFormName(string $formName) {
        $this->formName = $formName;
        return $this;
    }

    public function getColumns(): array {
        return [];
    }

    public function getData(): array {
        if (!$this->formName) return [];
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('tblForm')
           ->select([
               'tblForm.tfAction',
               'tblColumns.tcName',
               'tblColumns.tcLabel',
               'tblColumns.tcType',
               'tblColumns.tcRules'
           ])
           ->join('tblColumns', 'tblForm.tfPK', '=', 'tblColumns.tcFormFK')
           ->where('tblForm.tfName', '=', $this->formName)
           ->orderBy('tblColumns.tcOrder', 'ASC')
           ->executeFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Form Schema";
    }
}
?>
