<?php
class IndexController implements ControllerInterface {
    public static string $path = '/';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController, $formSchemas;

        $graph_query_builder = new QueryBuilder($dialect);
        $graph_query_builder->table('httpAction')->select([
            $graph_query_builder->raw($dialect->extractDatePart('YEAR', 'haDate') . ' y'),
            $graph_query_builder->raw($dialect->extractDatePart('MONTH', 'haDate') . ' m'),
            $graph_query_builder->raw($dialect->extractDatePart('DAY', 'haDate') . ' d'),
            $graph_query_builder->raw($dialect->extractDatePart('HOUR', 'haDate') . ' h'),
            $graph_query_builder->raw('COUNT(*) c')
        ])->groupBy([
            $graph_query_builder->raw($dialect->extractDatePart('YEAR', 'haDate')),
            $graph_query_builder->raw($dialect->extractDatePart('MONTH', 'haDate')),
            $graph_query_builder->raw($dialect->extractDatePart('DAY', 'haDate')),
            $graph_query_builder->raw($dialect->extractDatePart('HOUR', 'haDate'))
        ]);
        
        $rawGraphData = $graph_query_builder->executeFetchAll($db);
        $graphData = [];
        foreach($rawGraphData as $row){
            $dt = (new DateTime())->setDate($row['y'], $row['m'], $row['d'])->setTime($row['h'], 0);
            $graphData[] = [
                'x' => $dt->format('Y-m-d H:i:s'),
                'y' => $row['c']
            ];
        }

        return View::render('index/index', [
            'graphData' => $graphData,
            'isLoggedIn' => !is_null($sessionController->getPrimary('userID')),
            'formSchemas' => $formSchemas
        ]);
    }
}
?>
