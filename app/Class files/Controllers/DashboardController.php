<?php
class DashboardController implements ControllerInterface {
    public static string $path = '/dashboard';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;

        // Graph Section - Continuous Hourly Mapping
        $range_query_builder = new QueryBuilder($dialect);

        $range_query_builder->table('httpAction')->select([
            $range_query_builder->raw('MIN(haDate) as min'),
            $range_query_builder->raw('MAX(haDate) as max')
        ]);
        $stmt_range = $db->prepare($range_query_builder->toSQL());
        $range_query_builder->bindTo($stmt_range);
        $stmt_range->execute();
        $range = $stmt_range->fetch();

        if (!$range || is_null($range['min'])) {
            $start = (new DateTime())->modify("-24 hours");
            $end = new DateTime();
        } else {
            $start = (new DateTime($range['min']))->setTime((int)(new DateTime($range['min']))->format('H'), 0);
            $end = (new DateTime($range['max']))->setTime((int)(new DateTime($range['max']))->format('H'), 0);
        }

        $graph_query_builder = new QueryBuilder($dialect);

        $graph_query_builder->table('httpAction')->select([
            $graph_query_builder->raw('EXTRACT(YEAR FROM haDate) y'),
            $graph_query_builder->raw('EXTRACT(MONTH FROM haDate) m'),
            $graph_query_builder->raw('EXTRACT(DAY FROM haDate) d'),
            $graph_query_builder->raw('EXTRACT(HOUR FROM haDate) h'),
            $graph_query_builder->raw('COUNT(*) c')
        ])->groupBy([
            $graph_query_builder->raw('EXTRACT(YEAR FROM haDate)'),
            $graph_query_builder->raw('EXTRACT(MONTH FROM haDate)'),
            $graph_query_builder->raw('EXTRACT(DAY FROM haDate)'),
            $graph_query_builder->raw('EXTRACT(HOUR FROM haDate)')
        ]);
        $stmt_graph = $db->prepare($graph_query_builder->toSQL());
        $graph_query_builder->bindTo($stmt_graph);
        $stmt_graph->execute();
        $lookup = [];
        foreach($stmt_graph as $row) {
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

        $wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div");

        $graph = new DataGraph($graphData);
        $graph_details = $dom->fabricateChild(parent: $wrapper, tagName: "details");
        $heading = $dom->fabricateChild(parent : $graph_details, tagName : "summary", innerContent : "Visits per hour");
        $graph->render($dom, $graph_details);

        // Filter Form using new xmlForm mechanism
        $filter_heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "Filter Data");
        $filter_form = new xmlForm("dashboardFilter", $dom, $wrapper);
        $filter_form->prep("/dashboard", "POST");

        // Data Fetching for filters
        $ip_query_builder = new QueryBuilder($dialect);

        $ip_query_builder->table('httpAction')
            ->select(['haIP', $ip_query_builder->raw('count(*) as count')])
            ->groupBy(['haIP'])->orderBy($ip_query_builder->raw('count'), 'DESC');
        $stmt_ip = $db->prepare($ip_query_builder->toSQL());
        $ip_query_builder->bindTo($stmt_ip);
        $stmt_ip->execute();
        $ipData = $stmt_ip->fetchAll();

        $user_query_builder = new QueryBuilder($dialect);

        $user_query_builder->table('httpAction')
            ->select(['haUserFK', $user_query_builder->raw('count(*) as count')])
            ->groupBy(['haUserFK'])->orderBy($user_query_builder->raw('count'), 'DESC');
        $stmt_user = $db->prepare($user_query_builder->toSQL());
        $user_query_builder->bindTo($stmt_user);
        $stmt_user->execute();
        $userData = $stmt_user->fetchAll();

        $ua_query_builder = new QueryBuilder($dialect);

        $ua_query_builder->table('httpAction')
            ->select(['haUserAgent', $ua_query_builder->raw('count(*) as count')])
            ->groupBy(['haUserAgent'])->orderBy($ua_query_builder->raw('count'), 'DESC');
        $stmt_ua = $db->prepare($ua_query_builder->toSQL());
        $ua_query_builder->bindTo($stmt_ua);
        $stmt_ua->execute();
        $uaData = $stmt_ua->fetchAll();

        $filter_form->addMultiSelectGroup("ip", "IP Addresses", $ipData, "haIP");
        $filter_form->addMultiSelectGroup("user", "User FKs", $userData, "haUserFK");
        $filter_form->addMultiSelectGroup("ua", "User Agents", $uaData, "haUserAgent");

        $filter_form->submitRow();

        // Link JS
        $dom->fabricateChild(parent : $dom->body, tagName : "script", attributes : ["src" => "Static/dashboard.js"]);

        echo $dom->dom->saveHTML();
    }
}
?>
