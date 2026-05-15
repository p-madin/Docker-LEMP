<?php
global $dom, $db, $dialect;

function renderElement($el, $dom, $target, $formSchemas) {
    $tag = 'div';
    switch ($el['eleType']) {
        case 'heading': $tag = 'h2'; break;
        case 'paragraph': $tag = 'p'; break;
        case 'button': $tag = 'button'; break;
        case 'divider': $tag = 'hr'; break;
        case 'container': $tag = 'div'; break;
        case 'form': $tag = 'div'; break;
        default: $tag = 'div'; break;
    }

    $attrs = [];
    if (!empty($el['eleCSSClasses'])) {
        $attrs['class'] = $el['eleCSSClasses'];
    }
    
    // Create the element
    $node = $dom->fabricateChild($target, $tag, $attrs);
    
    if ($el['eleType'] === 'form') {
        // Special case for forms
        $formId = (int)$el['eleContent'];
        if ($formId > 0) {
            // Find form name in schemas
            $formName = null;
            // We need to fetch the form name from tblForm because $formSchemas keys are names
            global $db, $dialect;
            $qb = new QueryBuilder($dialect);
            $fData = $qb->table('tblForm')->where('tfPK', '=', $formId)->getFetch($db);
            if ($fData) {
                $formName = $fData['tfName'];
                $formComp = new FormComponent($dom, $formName);
                $formComp->buildFromSchema($formName, $formSchemas);
                $node->appendChild($formComp->render());
            }
        }
    } else if ($el['eleType'] === 'table') {
        $rawContent = htmlspecialchars_decode($el['eleContent'], ENT_QUOTES);
        $config = json_decode($rawContent, true);
        if ($config) {
            $table = new FlexTableComponent($dom);
            if (!empty($config['dataProvider'])) {
                $table->setDataProvider($config['dataProvider']);
            } else {
                $table->setDataSource($config['source'] ?? null);
                $table->setDataConfig(json_decode($config['config'] ?? '{}', true) ?: []);
            }
            $node->appendChild($table->render());
        }
    } else if ($el['eleType'] === 'chart') {
        $rawContent = htmlspecialchars_decode($el['eleContent'], ENT_QUOTES);
        $config = json_decode($rawContent, true);
        if ($config) {
            $chart = new DataGraphComponent($dom);
            $chart->setDataSource($config['source'] ?? null);
            $chart->setDataConfig(json_decode($config['config'] ?? '{}', true) ?: []);
            $node->appendChild($chart->render());
        }
    } else if (!in_array($el['eleType'], ['container', 'divider'])) {
        // Text content
        $node->textContent = $el['eleContent'];
    }

    // Recursively render children
    foreach ($el['children'] as $child) {
        renderElement($child, $dom, $node, $formSchemas);
    }
}

$dom->fabricateChild($target, 'h1', [], $pageData['pagTitle']);

foreach ($tree as $rootEl) {
    renderElement($rootEl, $dom, $target, $formSchemas);
}
?>
