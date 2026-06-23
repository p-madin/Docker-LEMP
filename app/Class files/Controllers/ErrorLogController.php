<?php
class ErrorLogController implements ControllerInterface {
    public static string $path = '/error_log';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom;

        // --- Controls: clear log ---
        if (isset($request->get['clear'])) {
            $qb_clear = new QueryBuilder($dialect);
            $qb_clear->table('phpErrorLog');
            $db->prepare($qb_clear->delete())->execute();
            Hyperlink::redirection("/error_log");
        }

        // --- Filters ---
        $severity  = $request->get['severity'] ?? '';
        $limit     = max(1, min(500, (int)($request->get['limit'] ?? 50)));

        $qb_rows = new QueryBuilder($dialect);
        $qb_rows->table('phpErrorLog')
            ->select(['pelPK', 'pelTimestamp', 'pelSeverity', 'pelMessage', 'pelFile', 'pelLine'])
            ->orderBy('pelPK', 'DESC')
            ->limit($limit);

        if ($severity) {
            $qb_rows->where('pelSeverity', '=', $severity);
        }

        $rows = $qb_rows->executeFetchAll($db);

        $qb_total = new QueryBuilder($dialect);
        $qb_total->table('phpErrorLog')->select([$qb_total->raw('COUNT(*)')]);
        if ($severity) {
            $qb_total->where('pelSeverity', '=', $severity);
        }
        $total = $qb_total->executeFetchColumn($db, 0);

        // --- Filter bar ---
        $qb_sev = new QueryBuilder($dialect);
        $qb_sev->table('phpErrorLog')
            ->select([$qb_sev->raw('DISTINCT pelSeverity')])
            ->orderBy('pelSeverity', 'ASC');
        $severities = $qb_sev->executeFetchAll($db, PDO::FETCH_COLUMN);

        return View::render('management/error_log', [
            'rows' => $rows,
            'total' => $total,
            'severities' => $severities,
            'severity' => $severity,
            'limit' => $limit
        ]);
    }
}
?>
