<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $items */

$dom->fabricateChild($target, "h1", [], "Form Management");

$hlink = new Hyperlink();
$createLinkWrapper = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Form", "/edit_form");
$target->appendChild($createLinkWrapper);

$table = new FlexTableComponent($dom);
$table->setColumns([
    ['key' => 'tfPK', 'label' => 'ID', 'isAction' => false],
    ['key' => 'tfName', 'label' => 'Form Name', 'isAction' => false],
    ['key' => 'columnCount', 'label' => 'Form Size', 'isAction' => false],
    ['key' => 'actions', 'label' => 'Actions', 'isAction' => true, 'renderCallback' => function($dom, $cell, $row) {
        $hlink = new Hyperlink();
        $editForm = $hlink->appendHyperlinkForm($dom, $cell, "Edit", "/edit_form?id=" . $row['tfPK']);
        $editForm->setAttribute('id', 'edit-form-' . $row['tfName']);
        
        $readOnly = (int)($row['tfReadOnly']);
        $delStyle = ['delete'];
        if ($readOnly === 1) {
            $delStyle[] = 'disabled';
        }

        $deleteForm = $hlink->appendHyperlinkForm($dom, $cell, "Delete", "/editForm", ['action' => 'delete', 'tfPK' => $row['tfPK']], $delStyle);
        $deleteForm->setAttribute('id', 'delete-form-' . $row['tfName']);
    }]
]);
$table->setData($items);

$target->append($table->render());
