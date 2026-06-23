<?php
class DashboardController implements ControllerInterface {
    public static string $path = '/dashboard';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController;



        // Data Fetching for filters
        $ip_query_builder = new QueryBuilder($dialect);
        $ip_query_builder->table('httpAction')
            ->select(['haIP', $ip_query_builder->raw('count(*) as count')])
            ->groupBy(['haIP'])->orderBy($ip_query_builder->raw('count'), 'DESC');
        $ipData = $ip_query_builder->executeFetchAll($db);

        $user_query_builder = new QueryBuilder($dialect);
        $user_query_builder->table('httpAction')
            ->select(['haUserFK', $user_query_builder->raw('count(*) as count')])
            ->groupBy(['haUserFK'])->orderBy($user_query_builder->raw('count'), 'DESC');
        $userData = $user_query_builder->executeFetchAll($db);

        $ua_query_builder = new QueryBuilder($dialect);
        $ua_query_builder->table('httpAction')
            ->select(['haUserAgent', $ua_query_builder->raw('count(*) as count')])
            ->groupBy(['haUserAgent'])->orderBy($ua_query_builder->raw('count'), 'DESC');
        $uaData = $ua_query_builder->executeFetchAll($db);

        $script = $dom->dom->createElement('script');
        $script->setAttribute('src', 'Static/dashboard.js');
        $dom->head->appendChild($script);

        return View::render('dashboard/index', [
            
            'ipData' => $ipData,
            'userData' => $userData,
            'uaData' => $uaData
        ]);
    }
}
?>
