<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $users */

$dom->fabricateChild($target, "h1", [], "Account Management");

$table = new FlexTableComponent($dom);
$table->setColumns([
    ['key' => 'auPK', 'label' => 'ID', 'isAction' => false],
    ['key' => 'username', 'label' => 'Username', 'isAction' => false],
    ['key' => 'name', 'label' => 'Full Name', 'isAction' => false],
    ['key' => 'verified', 'label' => 'Status', 'isAction' => false, 'renderCallback' => function($dom, $cell, $row) {
        $cell->textContent = $row['verified'] ? 'Verified' : 'Pending';
        if ($row['verified']) $cell->setAttribute('style', 'color:green; font-weight:bold;');
    }],
    ['key' => 'actions', 'label' => 'Actions', 'isAction' => true, 'renderCallback' => function($dom, $cell, $row) {
        $hlink = new Hyperlink();
        $editForm = $hlink->appendHyperlinkForm($dom, $cell, "Edit", "/edit_account?id=" . $row['auPK']);
        $editForm->setAttribute('id', 'edit-user-' . $row['username']);
    }]
]);
$table->setData($users);

$target->append($table->render());
