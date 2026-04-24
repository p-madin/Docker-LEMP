<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array|null $user */
/** @var array $formSchemas */

$dom->fabricateChild($target, "h1", [], "Edit Account");

if (!$user) {
    $dom->fabricateChild($target, "p", ["style" => "color:red;"], "User not found.");
    $dom->fabricateChild($target, "a", ["href" => "/account_management", "class" => "button"], "Back to List");
    return;
}

$dom->fabricateChild($target, "h2", [], "Editing User: " . $user['username']);

$form = new xmlForm("editUser", $dom);
$form->prep("/editAccount", "POST");
$form->formWrapper->setAttribute("id", "editUserFormComponent");
$form->formWrapper->setAttribute("data-initial-validate", "true");
$form->buildFromSchema('editUser', $formSchemas, $user);

// Add verification toggle
$form->addRow('verified_status', 'Verified:', 'checkbox', $user['verified']);
$form->submitRow();

$target->append($form->render());

$dom->fabricateChild($target, "hr");
$hlink = new Hyperlink();
$wrapper_bottom = $dom->dom->createElement("div");
$hlink->appendHyperlinkForm($dom, $wrapper_bottom, "Back to Account List", "/account_management");
$target->appendChild($wrapper_bottom);
