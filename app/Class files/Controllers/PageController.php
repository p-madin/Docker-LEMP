<?php
class PageController implements ControllerInterface {
    public static string $path = '/page_editor';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dom, $dialect, $assetManager;
        $assetManager->registerCss('/Static/editor-styles.css');
        $assetManager->registerJs('/Static/block-editor.js', ['type' => 'module']);

        $pageId = (int)($request->get['id'] ?? 0);
        $pageData = null;
        $elements = [];

        if ($pageId > 0) {
            $qb_page = new QueryBuilder($dialect);
            $pageData = $qb_page->table('tblPages')->where('pagPK', '=', $pageId)->getFetch($db);
            
            if ($pageData) {
                $qb_elements = new QueryBuilder($dialect);
                $elements = $qb_elements->table('tblElements')
                    ->join('brgPageElements', 'tblElements.elePK', '=', 'brgPageElements.pelElementFK')
                    ->where('brgPageElements.pelPageFK', '=', $pageId)
                    ->orderBy('brgPageElements.pelOrder', 'ASC')
                    ->getFetchAll($db);
            }
        }

        return View::render('management/page', [
            'pageId' => $pageId,
            'pageData' => $pageData,
            'elements' => $elements
        ]);
    }
}
?>
