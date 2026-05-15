<?php
class PageManagementController implements ControllerInterface, DataProviderInterface {
    public static string $path = '/page_management';
    public bool $isAction = false;

    public function getColumns(): array {
        return [
            ['key' => 'pagPK', 'label' => 'ID'],
            ['key' => 'pagTitle', 'label' => 'Title'],
            ['key' => 'pagSlug', 'label' => 'Slug'],
            ['key' => 'pagCreated', 'label' => 'Created'],
            ['key' => 'actions', 'label' => 'Actions', 'action' => 'link', 'actionConfig' => ['url' => '/page_editor?id=', 'param' => 'pagPK', 'label' => 'Edit Builder']]
        ];
    }

    public function getData(): array {
        global $db, $dialect;
        $qb = new QueryBuilder($dialect);
        return $qb->table('tblPages')
            ->where('pagDeleted', 'IS', null)
            ->orderBy('pagCreated', 'DESC')
            ->getFetchAll($db);
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "CMS Pages";
    }

    public function execute(Request $request) {
        global $dom;
        
        $table = new FlexTableComponent($dom);
        $table->setDataProvider(get_class($this));

        return View::render('management/page_list', [
            'table' => $table
        ]);
    }
}
?>
