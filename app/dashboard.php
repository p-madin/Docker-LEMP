<?php

include_once("Class files/config.php");
include_once("Class files/extranet.php");

// Graph Section - Continuous Hourly Mapping
$rangeQuery = $db->query("SELECT MIN(haDate) as min, MAX(haDate) as max FROM httpAction");
$range = $rangeQuery->fetch();

if (!$range || is_null($range['min'])) {
    $start = (new DateTime())->modify("-24 hours");
    $end = new DateTime();
} else {
    $start = (new DateTime($range['min']))->setTime((int)(new DateTime($range['min']))->format('H'), 0);
    $end = (new DateTime($range['max']))->setTime((int)(new DateTime($range['max']))->format('H'), 0);
}

$graphQuery = $db->query("SELECT year(haDate) y, month(haDate) m, day(haDate) d, hour(haDate) h, count(*) c 
                          FROM httpAction GROUP BY y, m, d, h");
$lookup = [];
foreach($graphQuery as $row) {
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

$wrapper = $dom->appendChild(parent : $dom->body, tagName : "div");

$graph = new DataGraph($graphData);
$graph_details = $dom->appendChild(parent: $wrapper, tagName: "details");
$heading = $dom->appendChild(parent : $graph_details, tagName : "summary", innerContent : "Visits per hour");
$graph->render($dom, $graph_details);

// Filter Form using new xmlForm mechanism
$filter_heading = $dom->appendChild(parent : $wrapper, tagName : "h1", innerContent : "Filter Data");
$filter_form = new xmlForm("dashboardFilter", $dom, $wrapper);
$filter_form->prep("dashboard.php", "POST");

// Data Fetching for filters
$ipData = $db->query("SELECT haIP, count(*) as count FROM httpAction GROUP BY haIP ORDER BY count DESC")->fetchAll();
$userData = $db->query("SELECT haUserFK, count(*) as count FROM httpAction GROUP BY haUserFK ORDER BY count DESC")->fetchAll();
$uaData = $db->query("SELECT haUserAgent, count(*) as count FROM httpAction GROUP BY haUserAgent ORDER BY count DESC")->fetchAll();

$filter_form->addMultiSelectGroup("ip", "IP Addresses", $ipData, "haIP");
$filter_form->addMultiSelectGroup("user", "User FKs", $userData, "haUserFK");
$filter_form->addMultiSelectGroup("ua", "User Agents", $uaData, "haUserAgent");

$filter_form->submitRow();

// Link JS
$dom->appendChild(parent : $dom->body, tagName : "script", attributes : ["src" => "Static/dashboard.js"]);

echo $dom->dom->saveHTML();
?>