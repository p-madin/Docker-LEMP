<?php
class IndexController implements ControllerInterface {
    public static string $path = '/';
    public bool $isAction = false;

    public function execute(Request $request) {
        global $db, $dialect, $dom, $sessionController, $formSchemas;

        $wrapper = $dom->fabricateChild(parent : $dom->body, tagName : "div");
        /*
        $users_query_builder = new QueryBuilder($dialect);
        $users_query_builder->table('appUsers')->select(['name']);
        $data = $users_query_builder->getFetchAll($db);

        $heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "User list");
        $unordered_list = $dom->fabricateChild(parent : $wrapper, tagName : "ul");

        foreach($data as $key=>$value){
            $list_item = $dom->fabricateChild(parent : $unordered_list, tagName : "li", innerContent : $value['name']);
        }
        */

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
        
        $rawGraphData = $graph_query_builder->getFetchAll($db);
        $graphData = [];
        foreach($rawGraphData as $row){
            $dt = (new DateTime())->setDate($row['y'], $row['m'], $row['d'])->setTime($row['h'], 0);
            $graphData[] = [
                'x' => $dt->format('Y-m-d H:i:s'),
                'y' => $row['c']
            ];
        }
        
        $graph = new DataGraph($graphData);
        $graph_details = $dom->fabricateChild(parent: $wrapper, tagName: "details");
        $heading = $dom->fabricateChild(parent : $graph_details, tagName : "summary", innerContent : "Visits per hour");
        $graph->render($dom, $graph_details);

        $heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "Login form");

        if(!is_null($sessionController->getPrimary('userID'))){
            $heading = $dom->fabricateChild(parent : $wrapper, tagName : "p", attributes: ["id" => "loginWidgetSummary"] , innerContent : "You are already signed in");
            
            $logout_container = $dom->fabricateChild($wrapper, "div");
            $hyperlink = new Hyperlink();
            $hyperlink->appendHyperlinkForm($dom, $logout_container, "Click here to logout", "/logout");
        }else{
            $heading = $dom->fabricateChild(parent : $wrapper, tagName : "p", attributes: ["id" => "loginWidgetSummary"] , innerContent : "Sign in here");
            $login_form = new xmlForm("login", $dom, $wrapper);
            $login_form->prep("/login", "POST");
            $login_form->formWrapper->setAttribute("id", "loginFormComponent");
            $login_form->buildFromSchema('login', $formSchemas);
            $login_form->submitRow();
        }

        $heading = $dom->fabricateChild(parent : $wrapper, tagName : "h1", innerContent : "Register form");

        $register_form = new xmlForm("register", $dom, $wrapper);
        $register_form->prep("/register", "POST");
        $register_form->formWrapper->setAttribute("id", "registerFormComponent");
        $register_form->buildFromSchema('register', $formSchemas);
        $register_form->submitRow();

        echo $dom->dom->saveHTML();
    }
}
?>
