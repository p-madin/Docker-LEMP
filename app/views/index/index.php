<?php
/** @var xmlDom $dom */
/** @var \Dom\HTMLElement $target */
/** @var array $graphData */
/** @var bool $isLoggedIn */
/** @var array $formSchemas */

// 1. Graph
$graph = new DataGraph($graphData);
$details = $dom->dom->createElement('details');
$summary = $dom->dom->createElement('summary');
$summary->textContent = "Visits per hour";
$details->appendChild($summary);
$graph->render($dom, $details);
$target->append($details);

// 2. Login Section
$dom->fabricateChild($target, "h1", [], "Login form");
if ($isLoggedIn) {
    $dom->fabricateChild($target, "p", ["id" => 'loginWidgetSummary'], "You are already signed in");
    $logout_container = $dom->dom->createElement('div');
    $hyperlink = new Hyperlink();
    $hyperlink->appendHyperlinkForm($dom, $logout_container, "Click here to logout", "/logout");
    $target->appendChild($logout_container);
} else {
    $dom->fabricateChild($target, "p", ["id" => 'loginWidgetSummary'], "Sign in here");
    $login_form = new xmlForm("login", $dom);
    $login_form->prep("/login", "POST");
    $login_form->formWrapper->setAttribute("id", "loginFormComponent");
    $login_form->buildFromSchema('login', $formSchemas);
    $login_form->submitRow();
    $target->append($login_form->render());
}

// 3. Register Section
$dom->fabricateChild($target, "h1", [], "Register form");
$register_form = new xmlForm("register", $dom);
$register_form->prep("/register", "POST");
$register_form->formWrapper->setAttribute("id", "registerFormComponent");
$register_form->buildFromSchema('register', $formSchemas);
$register_form->submitRow();
$target->append($register_form->render());
