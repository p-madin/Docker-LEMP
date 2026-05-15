<?php
global $assetManager, $sessionController;
// Inject the state as a JS variable for the editor
$dom->fabricateChild($dom->body, 'script', ['nonce' => $assetManager->getNonce()], "
    window.EDITOR_STATE = {
        pageId: " . json_encode($pageId) . ",
        pageData: " . json_encode($pageData) . ",
        elements: " . json_encode($elements) . ",
        csrfToken: " . json_encode($sessionController->getCSRFToken()) . "
    };
");

// Inject the Javascript for the block editor as an ES module


$target->setAttribute("class", $target->getAttribute("class")." full-width");

// The $target acts as the container for the page content.
// The Javascript will look for #editor-root to mount itself.
$dom->fabricateChild($target, 'div', [
    'id' => 'editor-root'
]);
?>
