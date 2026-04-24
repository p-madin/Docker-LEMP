<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $raw */
/** @var int $id */
/** @var int $form_id */
/** @var array $formSchemas */

$hlink = new Hyperlink();

if ($id > 0) {
    $dom->fabricateChild($target, "h1", [], "Edit Field");
    if (empty($raw['tcPK']) && empty($raw['tcpk'])) {
        $dom->fabricateChild($target, "p", ["style" => "color:red;"], "Field not found.");
        $wrapper_back = $dom->dom->createElement("div");
        $hlink->appendHyperlinkForm($dom, $wrapper_back, "Back to Forms", "/form_management");
        $target->appendChild($wrapper_back);
        return;
    }
} else {
    if ($form_id === 0) {
         $dom->fabricateChild($target, "p", ["style" => "color:red;"], "Missing Form ID context.");
         return;
    }
    $dom->fabricateChild($target, "h1", [], "Create New Field");
}

$form = new xmlForm("editColumn", $dom);
$form->prep("/editColumn", "POST");
$form->formWrapper->setAttribute("id", "editColumnFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editColumn', $formSchemas, $raw);
$form->submitRow();

$target->append($form->render());

$wrapper_bottom = $dom->dom->createElement("div");
$wrapper_bottom->setAttribute("style", "margin-top: 20px;");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Form Fields", "/edit_form?id=" . $form_id);
$target->appendChild($wrapper_bottom);
