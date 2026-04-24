<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $u */
/** @var int $id */
/** @var array $formSchemas */

if ($id > 0) {
    $dom->fabricateChild($target, "h1", [], "Edit Navbar Item");
    if (empty($u['nbPK']) && empty($u['nbpk'])) {
        $dom->fabricateChild($target, "p", ["style" => "color:red;"], "Navbar item not found.");
        $dom->fabricateChild($target, "a", ["href" => "/navbar_management", "class" => "button"], "Back to List");
        return;
    }
} else {
    $dom->fabricateChild($target, "h1", [], "Create Navbar Item");
}

$form = new xmlForm("navbar", $dom);
$form->prep("/editNavbar", "POST");
$form->formWrapper->setAttribute("id", "editNavbarFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('navbar', $formSchemas, $u);

// Append boolean dynamic field
$form->addRow('nbProtected', 'Protected (Requires Login):', 'checkbox', $u['nbProtected']);
$form->submitRow();

$target->append($form->render());

$dom->fabricateChild($target, "hr");
$hlink = new Hyperlink();
$wrapper_bottom = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Navbar List", "/navbar_management");
$target->appendChild($wrapper_bottom);
