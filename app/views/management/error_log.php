<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $rows */
/** @var int $total */
/** @var array $severities */
/** @var string $severity */
/** @var int $limit */

$dom->fabricateChild($target, "h1", [], "PHP Error Log");

// Filter bar
$filterBar = $dom->dom->createElement("div");
$filterBar->setAttribute("style", "margin-bottom:1em; display:flex; gap:1em; align-items:center; flex-wrap:wrap;");

$filterForm = $dom->dom->createElement("form");
$filterForm->setAttribute("method", "GET");
$filterForm->setAttribute("action", "/error_log");
$filterForm->setAttribute("style", "display:flex; gap:.5em; align-items:center;");

$labelSev = $dom->dom->createElement("label");
$labelSev->textContent = "Severity:";
$labelSev->setAttribute("for", "sev");
$filterForm->appendChild($labelSev);

$sel = $dom->dom->createElement("select");
$sel->setAttribute("id", "sev");
$sel->setAttribute("name", "severity");

$optAll = $dom->dom->createElement("option");
$optAll->textContent = "All";
$optAll->setAttribute("value", "");
$sel->appendChild($optAll);

foreach ($severities as $sev) {
    $opt = $dom->dom->createElement("option");
    $opt->textContent = $sev;
    $opt->setAttribute("value", $sev);
    if ($sev === $severity) $opt->setAttribute("selected", "selected");
    $sel->appendChild($opt);
}
$filterForm->appendChild($sel);

$labelLim = $dom->dom->createElement("label");
$labelLim->textContent = "Limit:";
$labelLim->setAttribute("for", "lim");
$filterForm->appendChild($labelLim);

$inputLim = $dom->dom->createElement("input");
$inputLim->setAttribute("id", "lim");
$inputLim->setAttribute("type", "number");
$inputLim->setAttribute("name", "limit");
$inputLim->setAttribute("value", (string)$limit);
$inputLim->setAttribute("min", "1");
$inputLim->setAttribute("max", "500");
$inputLim->setAttribute("style", "width:5em;");
$filterForm->appendChild($inputLim);

$btnFilter = $dom->dom->createElement("button");
$btnFilter->textContent = "Filter";
$btnFilter->setAttribute("type", "submit");
$filterForm->appendChild($btnFilter);

$filterBar->appendChild($filterForm);

$clearForm = $dom->dom->createElement("form");
$clearForm->setAttribute("method", "GET");
$clearForm->setAttribute("action", "/error_log");
$hiddenClear = $dom->dom->createElement("input");
$hiddenClear->setAttribute("type", "hidden");
$hiddenClear->setAttribute("name", "clear");
$hiddenClear->setAttribute("value", "1");
$clearForm->appendChild($hiddenClear);
$btnClear = $dom->dom->createElement("button");
$btnClear->textContent = "Clear All";
$btnClear->setAttribute("type", "submit");
$btnClear->setAttribute("style", "color:red;");
$clearForm->appendChild($btnClear);

$filterBar->appendChild($clearForm);

$target->appendChild($filterBar);

$dom->fabricateChild($target, "p", [], "Showing " . count($rows) . " of $total entries.");

$table = new FlexTableComponent($dom, ['style' => 'font-size:.85em;']);
$table->setColumns([
    ['key' => 'pelPK', 'label' => '#', 'isAction' => false],
    ['key' => 'pelTimestamp', 'label' => 'Timestamp', 'isAction' => false],
    ['key' => 'pelSeverity', 'label' => 'Severity', 'isAction' => false, 'renderCallback' => function($dom, $cell, $row) {
        $sevColour = match(true) {
            str_contains($row['pelSeverity'], 'ERROR')      => "color:red; font-weight:bold;",
            str_contains($row['pelSeverity'], 'WARNING')    => "color:darkorange;",
            str_contains($row['pelSeverity'], 'DEPRECATED') => "color:grey;",
            default                                         => "",
        };
        if ($sevColour) $cell->setAttribute('style', $sevColour);
        $cell->textContent = $row['pelSeverity'];
    }],
    ['key' => 'file_line', 'label' => 'File:Line', 'isAction' => false, 'renderCallback' => function($dom, $cell, $row) {
        $shortFile = basename($row['pelFile']) . ":" . $row['pelLine'];
        $cell->textContent = $shortFile;
        $cell->setAttribute('title', $row['pelFile']);
    }],
    ['key' => 'pelMessage', 'label' => 'Message', 'isAction' => false, 'renderCallback' => function($dom, $cell, $row) {
        $pre = $dom->dom->createElement('pre');
        $pre->setAttribute('class', 'error_message');
        $pre->setAttribute('style', 'white-space: pre-wrap;');
        $pre->textContent = $row['pelMessage']; 
        $cell->appendChild($pre);
    }]
]);
$table->setData($rows);

$target->append($table->render());
