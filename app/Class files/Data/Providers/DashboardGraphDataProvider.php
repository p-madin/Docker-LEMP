<?php
class DashboardGraphDataProvider implements DataProviderInterface {
    public function getColumns(): array {
        return [];
    }

    public function getData(): array {
        global $db, $dialect;
        $range_query_builder = new QueryBuilder($dialect);

        $range_query_builder->table('httpAction')->select([
            $range_query_builder->raw('MIN(haDate) as min'),
            $range_query_builder->raw('MAX(haDate) as max')
        ]);
        $range = $range_query_builder->executeFetch($db);

        if (!$range || is_null($range['min'])) {
            $start = (new DateTime())->modify("-24 hours");
            $end = new DateTime();
        } else {
            $start = (new DateTime($range['min']))->setTime((int)(new DateTime($range['min']))->format('H'), 0);
            $end = (new DateTime($range['max']))->setTime((int)(new DateTime($range['max']))->format('H'), 0);
        }

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
        $lookup = [];
        foreach($rawGraphData as $row) {
            $key = sprintf("%04d-%02d-%02d %02d:00:00", $row['y'], $row['m'], $row['d'], $row['h']);
            $lookup[$key] = $row['c'];
        }

        $graphData = [];
        $iter = clone $start;
        while ($iter <= $end) {
            $key = $iter->format('Y-m-d H:i:s');
            $graphData[] = [
                'x' => $key,
                'y' => $lookup[$key] ?? 0
            ];
            $iter->modify("+1 hour");
        }

        return $graphData;
    }

    public function getNestedKey(): ?string {
        return null;
    }

    public function getDataSourceName(): string {
        return "Dashboard Traffic Graph";
    }
}
?>
