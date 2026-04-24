<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $graphData */
/** @var array $ipData */
/** @var array $userData */
/** @var array $uaData */

// 1. Render Graph
$graph = new DataGraph($graphData);
$details = $dom->dom->createElement('details');
$summary = $dom->dom->createElement('summary');
$summary->textContent = "Visits per hour";
$details->appendChild($summary);
$graph->render($dom, $details);
$target->append($details);

// 2. Render Filter Form
$dom->fabricateChild($target, "h1", [], "Filter Data");
$filter_form = new xmlForm("dashboardFilter", $dom);
$filter_form->prep("/dashboard", "POST");
$filter_form->addMultiSelectGroup("ip", "IP Addresses", $ipData, "haIP");
$filter_form->addMultiSelectGroup("user", "User FKs", $userData, "haUserFK");
$filter_form->addMultiSelectGroup("ua", "User Agents", $uaData, "haUserAgent");
$filter_form->submitRow();

$target->append($filter_form->render());
