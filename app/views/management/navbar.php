<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $items */

$dom->fabricateChild($target, "h1", [], "Navbar Management");

$hlink = new Hyperlink();
$createLinkWrapper = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $createLinkWrapper, "Create New Navbar Item", "/edit_navbar");
$target->appendChild($createLinkWrapper);

$table = new FlexTableComponent($dom);
$table->setNestedKey('children');
$table->setColumns([
    ['key' => 'nbPK', 'label' => 'ID', 'isAction' => false],
    ['key' => 'nbText', 'label' => 'Text', 'isAction' => false, 'renderCallback' => function($dom, $cell, $row) {
        if (!empty($row['children'])) {
            $cell->setAttribute("class","Directory ". $cell->getAttribute("class"));
        }
        $cell->textContent = $row['nbText'];
    }],
    ['key' => 'nbDiscriminator', 'label' => 'Type', 'isAction' => false],
    ['key' => 'nbPath', 'label' => 'Path', 'isAction' => false],
    ['key' => 'nbParentFK', 'label' => 'Parent ID', 'isAction' => false],
    ['key' => 'nbOrder', 'label' => 'Order', 'isAction' => false],
    ['key' => 'actions', 'label' => 'Actions', 'isAction' => true, 'renderCallback' => function($dom, $cell, $row) {
        $hlink = new Hyperlink();
        $edit_link = $hlink->appendHyperlinkForm($dom, $cell, "Edit", "/edit_navbar?id=" . $row['nbPK']);
        $edit_link->setAttribute('id', 'edit-navbar-' . $row['nbPath']);
        $delete_link = $hlink->appendHyperlinkForm($dom, $cell, "Delete", "/editNavbar", 
                                     ['action' => 'delete', 'nbPK' => $row['nbPK']], ['delete']);
        $delete_link->setAttribute('id', 'delete-navbar-' . $row['nbPath']);
    }]
]);
$table->setData($items);

$target->append($table->render());
