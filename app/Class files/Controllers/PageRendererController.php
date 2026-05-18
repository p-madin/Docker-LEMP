<?php
class PageRendererController implements ControllerInterface {
    public static string $path = '/'; 
    public bool $isAction = false;
    private $pageId;

    public function setPageId(int $pageId) {
        $this->pageId = $pageId;
    }

    public function execute(Request $request) {
        global $db, $dialect, $formSchemas;

        $pageId = $this->pageId ?: (int)($request->get['id'] ?? 0);
        if ($pageId <= 0) {
             header("Location: /");
             exit;
        }

        $qb_page = new QueryBuilder($dialect);
        $pageData = $qb_page->table('tblPages')->where('pagPK', '=', $pageId)->getFetch($db);
        
        if (!$pageData) {
             header("Location: /");
             exit;
        }

        $qb_elements = new QueryBuilder($dialect);
        $elements = $qb_elements->table('tblElements')
            ->join('brgPageElements', 'tblElements.elePK', '=', 'brgPageElements.pelElementFK')
            ->where('brgPageElements.pelPageFK', '=', $pageId)
            ->orderBy('brgPageElements.pelOrder', 'ASC')
            ->getFetchAll($db);

        // Build hierarchical tree
        $tree = $this->buildTree($elements);

        return View::render('management/preview', [
            'pageData' => $pageData,
            'tree' => $tree,
            'formSchemas' => $formSchemas
        ]);
    }

    private function buildTree($elements) {
        $map = [];
        $tree = [];
        foreach ($elements as $el) {
            $el['children'] = [];
            $map[$el['elePK']] = $el;
        }

        foreach ($map as $id => &$el) {
            $parentId = $el['eleParentFK'];
            if ($parentId && isset($map[$parentId])) {
                $map[$parentId]['children'][] = &$el;
            } else {
                $tree[] = &$el;
            }
        }
        return $tree;
    }
}
