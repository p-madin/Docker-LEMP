<?php
class PageRendererController implements ControllerInterface {
    public static string $path = '/'; 
    public bool $isAction = false;
    private $pageId;

    public function setPageId(int $pageId) {
        $this->pageId = $pageId;
    }

    public function execute(Request $request) {
        global $db, $dialect;

        $pageId = $this->pageId ?: (int)($request->get['id'] ?? 0);
        if ($pageId <= 0) {
            Hyperlink::redirection("/");
        }

        $qb_page = new QueryBuilder($dialect);
        $pageData = $qb_page->table('tblPages')
            ->select(['pagPK', 'pagTitle'])
            ->where('pagPK', '=', $pageId)
            ->executeFetch($db);
        
        if (!$pageData) {
            Hyperlink::redirection("/");
        }

        $qb_elements = new QueryBuilder($dialect);
        $elements = $qb_elements->table('tblElements')
            ->select(['elePK', 'eleParentFK', 'eleType', 'eleContent'])
            ->join('brgPageElements', 'tblElements.elePK', '=', 'brgPageElements.pelElementFK')
            ->where('brgPageElements.pelPageFK', '=', $pageId)
            ->orderBy('brgPageElements.pelOrder', 'ASC')
            ->executeFetchAll($db);

        // Build hierarchical tree
        $tree = $this->buildTree($elements);

        return View::render('management/preview', [
            'pageData' => $pageData,
            'tree' => $tree,
            
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
