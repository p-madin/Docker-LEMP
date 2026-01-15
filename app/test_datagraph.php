<?php
require_once __DIR__ . '/Class files/dataGraph.php';

$xmlData = '
<series>
    <item><x>2026-01-01</x><y>10</y></item>
    <item><x>2026-01-05</x><y>50</y></item>
    <item><x>2026-01-10</x><y>30</y></item>
    <item><x>2026-01-15</x><y>80</y></item>
</series>';

$graph = DataGraph::fromXML($xmlData);
$svg = $graph->render();

echo $svg;
?>
