<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $items */

$dom->fabricateChild($target, "h1", [], "Banned IP Management");

$table = new FlexTableComponent($dom);
$table->setColumns([
    ['key' => 'biPK', 'label' => 'ID', 'isAction' => false],
    ['key' => 'biIP', 'label' => 'IP Address', 'isAction' => false],
    ['key' => 'biReason', 'label' => 'Reason', 'isAction' => false],
    ['key' => 'biBannedAt', 'label' => 'Banned At', 'isAction' => false],
    ['key' => 'biExpiresAt', 'label' => 'Expires At', 'isAction' => false],
    ['key' => 'actions', 'label' => 'Actions', 'isAction' => true, 'renderCallback' => function($dom, $cell, $row) {
        $hlink = new Hyperlink();
        $deleteForm = $hlink->appendHyperlinkForm($dom, $cell, "Unban", "/editBannedIp", 
                                    ['action' => 'delete', 'biPK' => $row['biPK']], ['delete']);
        $deleteForm->setAttribute('id', 'unban-ip-' . str_replace('.', '-', $row['biIP']));
    }]
]);
$table->setData($items);

$target->append($table->render());
