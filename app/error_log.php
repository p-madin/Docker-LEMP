<?php
include_once("Class files/config.php");
include_once("Class files/extranet.php");

$wrapper = $dom->fabricateChild($dom->body, "div", ["class" => "container"]);
$dom->fabricateChild($wrapper, "h1", [], "PHP Error Log");

// --- Controls: clear log ---
if (isset($_GET['clear'])) {
    $qb_clear = new QueryBuilder($dialect);
    $qb_clear->table('phpErrorLog');
    $db->prepare($qb_clear->delete())->execute();
    // PRG
    header("Location: error_log.php");
    exit;
}

// --- Filters ---
$severity  = $_GET['severity'] ?? '';
$limit     = max(1, min(500, (int)($_GET['limit'] ?? 50)));

$qb_rows = new QueryBuilder($dialect);
$qb_rows->table('phpErrorLog')
    ->select(['pelPK', 'pelTimestamp', 'pelSeverity', 'pelMessage', 'pelFile', 'pelLine'])
    ->orderBy('pelPK', 'DESC')
    ->limit($limit);

if ($severity) {
    $qb_rows->where('pelSeverity', '=', $severity);
}

$stmt_rows = $db->prepare($qb_rows->toSQL());
$qb_rows->bindTo($stmt_rows);
$stmt_rows->execute();
$rows = $stmt_rows->fetchAll();

$qb_total = new QueryBuilder($dialect);
$qb_total->table('phpErrorLog')->select([$qb_total->raw('COUNT(*)')]);
if ($severity) {
    $qb_total->where('pelSeverity', '=', $severity);
}
$stmt_total = $db->prepare($qb_total->toSQL());
$qb_total->bindTo($stmt_total);
$stmt_total->execute();
$total = $stmt_total->fetchColumn();

// --- Filter bar ---
$qb_sev = new QueryBuilder($dialect);
$qb_sev->table('phpErrorLog')
    ->select([$qb_sev->raw('DISTINCT pelSeverity')])
    ->orderBy('pelSeverity', 'ASC');
$stmt_sev = $db->prepare($qb_sev->toSQL());
$qb_sev->bindTo($stmt_sev);
$stmt_sev->execute();
$severities = $stmt_sev->fetchAll(PDO::FETCH_COLUMN);

$filterBar = $dom->fabricateChild($wrapper, "div", ["style" => "margin-bottom:1em; display:flex; gap:1em; align-items:center; flex-wrap:wrap;"]);

// Severity filter (plain <select> with GET submit, no xmlForm needed)
$filterForm = $dom->fabricateChild($filterBar, "form", ["method" => "GET", "action" => "error_log.php", "style" => "display:flex; gap:.5em; align-items:center;"]);
$dom->fabricateChild($filterForm, "label", ["for" => "sev"], "Severity:");
$sel = $dom->fabricateChild($filterForm, "select", ["id" => "sev", "name" => "severity"]);
$dom->fabricateChild($sel, "option", ["value" => ""], "All");
foreach ($severities as $sev) {
    $attrs = ["value" => $sev];
    if ($sev === $severity) $attrs["selected"] = "selected";
    $dom->fabricateChild($sel, "option", $attrs, $sev);
}

$dom->fabricateChild($filterForm, "label", ["for" => "lim"], "Limit:");
$dom->fabricateChild($filterForm, "input", ["id" => "lim", "type" => "number", "name" => "limit", "value" => (string)$limit, "min" => "1", "max" => "500", "style" => "width:5em;"]);
$dom->fabricateChild($filterForm, "button", ["type" => "submit"], "Filter");

// Clear button
$clearForm = $dom->fabricateChild($filterBar, "form", ["method" => "GET", "action" => "error_log.php"]);
$dom->fabricateChild($clearForm, "input", ["type" => "hidden", "name" => "clear", "value" => "1"]);
$dom->fabricateChild($clearForm, "button", ["type" => "submit", "style" => "color:red;"], "Clear All");

$dom->fabricateChild($wrapper, "p", [], "Showing " . count($rows) . " of $total entries.");

// --- Table ---
if (empty($rows)) {
    $dom->fabricateChild($wrapper, "p", [], "No errors logged.");
} else {
    $table = $dom->fabricateChild($wrapper, "div", ["class" => "flex-table", "style" => "font-size:.85em;"]);

    $hdr = $dom->fabricateChild($table, "div", ["class" => "flex-row", "style" => "font-weight:bold; background:#eee;"]);
    foreach (["#", "Timestamp", "Severity", "File:Line", "Message"] as $col) {
        $dom->fabricateChild($hdr, "div", ["class" => "flex-cell"], $col);
    }

    foreach ($rows as $row) {
        $r = $dom->fabricateChild($table, "div", ["class" => "flex-row"]);
        $dom->fabricateChild($r, "div", ["class" => "flex-cell"], (string)$row['pelPK']);
        $dom->fabricateChild($r, "div", ["class" => "flex-cell"], $row['pelTimestamp']);

        $sevColour = match(true) {
            str_contains($row['pelSeverity'], 'ERROR')      => "color:red; font-weight:bold;",
            str_contains($row['pelSeverity'], 'WARNING')    => "color:darkorange;",
            str_contains($row['pelSeverity'], 'DEPRECATED') => "color:grey;",
            default                                         => "",
        };
        $dom->fabricateChild($r, "div", ["class" => "flex-cell", "style" => $sevColour], $row['pelSeverity']);

        $shortFile = basename($row['pelFile']) . ":" . $row['pelLine'];
        $dom->fabricateChild($r, "div", ["class" => "flex-cell", "title" => $row['pelFile']], $shortFile);
        $error_container = $dom->fabricateChild($r, "div", ["class" => "flex-cell"]);
        $error_message = $dom->fabricateChild($error_container, "pre", ["class" => "error_message", "style" => "white-space: pre-wrap;"], $row['pelMessage']);
    }
}

echo $dom->dom->saveHTML();
?>
