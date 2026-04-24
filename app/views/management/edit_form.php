<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $u */
/** @var int $id */
/** @var int $readOnly */
/** @var array $formSchemas */
/** @var array|null $cols */

$hlink = new Hyperlink();

if ($id > 0) {
    $dom->fabricateChild($target, "h1", [], "Edit Form Settings");
    if (empty($u['tfPK']) && empty($u['tfpk'])) {
        $dom->fabricateChild($target, "p", ["style" => "color:red;"], "Form not found.");
        $wrapper_back = $dom->dom->createElement("div");
        $hlink->appendHyperlinkForm($dom, $wrapper_back, "Back to List", "/form_management");
        $target->appendChild($wrapper_back);
        return;
    }
} else {
    $dom->fabricateChild($target, "h1", [], "Create New Form");
}

$form = new xmlForm("editForm", $dom);
$form->prep("/editForm", "POST");
$form->formWrapper->setAttribute("id", "editFormFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editForm', $formSchemas, $u);

if ($readOnly === 1) {
    $submitAttributes = ['disabled' => 'disabled', 'style' => 'pointer-events:none; background-color: #ccc; color:#666; cursor:not-allowed; padding:10px 15px; border:none; border-radius:4px; font-weight:bold; margin-top:20px;'];
    $form->addSubmit('sub_btn', $submitAttributes);
} else {
    $form->submitRow();
}

$target->append($form->render());

$dom->fabricateChild($target, "hr");

if ($id > 0 && isset($cols)) {
    $dom->fabricateChild($target, "h2", [], "Form Fields");
    $addStyle = "margin-bottom: 15px; display:inline-block;";
    if ($readOnly === 1) {
        $addStyle .= " pointer-events:none; background-color: #ccc; opacity:0.6;";
    }
    $wrapper_add = $dom->dom->createElement("div");
    $wrapper_add->setAttribute("style", $addStyle);
    $hlink->appendHyperlinkForm($dom, $wrapper_add, "Add New Field", "/edit_column?form_id=" . $id);
    $target->appendChild($wrapper_add);
    
    $table = new FlexTableComponent($dom);
    $table->setColumns([
        ['key' => 'tcOrder', 'label' => 'Order', 'isAction' => false],
        ['key' => 'tcName', 'label' => 'Name', 'isAction' => false],
        ['key' => 'tcLabel', 'label' => 'Label', 'isAction' => false],
        ['key' => 'tcType', 'label' => 'Type', 'isAction' => false],
        ['key' => 'actions', 'label' => 'Actions', 'isAction' => true, 'renderCallback' => function($dom, $cell, $row) use ($id, $readOnly) {
            $hlink = new Hyperlink();
            $editStyle = ['edit'];
            $delStyle = ['delete'];
            if ($readOnly === 1) { $editStyle[] = 'disabled'; $delStyle[] = 'disabled'; }

            $editFieldForm = $hlink->appendHyperlinkForm($dom, $cell, "Edit", "/edit_column?id=" . $row['tcPK'], [], $editStyle);
            $editFieldForm->setAttribute('id', 'edit-field-' . $row['tcName']);
            $deleteFieldForm = $hlink->appendHyperlinkForm($dom, $cell, "Delete", "/editColumn", 
                                        ['action' => 'delete', 'tcPK' => $row['tcPK'], 'form_id' => $id], $delStyle);
            $deleteFieldForm->setAttribute('id', 'delete-field-' . $row['tcName']);
        }]
    ]);
    $table->setData($cols);
    $target->append($table->render());
}

$wrapper_bottom = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Forms List", "/form_management");
$target->appendChild($wrapper_bottom);
