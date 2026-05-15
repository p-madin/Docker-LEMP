<?php
// app/views/management/page_list.php

$dom->fabricateChild($target, 'h1', [], 'Page Management');

$hlink = new Hyperlink();
$createLinkWrapper = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Page", "/page_editor");
$target->appendChild($createLinkWrapper);

// Mount the table

$target->append($table->render());
//$target->appendChild($table->getRoot());
?>
