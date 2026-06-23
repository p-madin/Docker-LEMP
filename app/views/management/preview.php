<?php
global $dom, $db, $dialect;

function renderElement($el, $dom, $target) {
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
        $rawContent = htmlspecialchars_decode($el['eleContent'], ENT_QUOTES);
        $config = json_decode($rawContent, true);
        if ($config) {
            $formName = $config['source'] ?? null;
            if ($formName) {
                global $db, $dialect;
                $qb = new QueryBuilder($dialect);
                $fData = $qb->table('tblForm')->select(['tfReadOnly'])->where('tfName', '=', $formName)->executeFetch($db);
                if ($fData) {
                    $formComp = new FormComponent($dom, $formName);
                    if (!empty($config['valueProvider'])) {
                        $formComp->setValueProvider($config['valueProvider']);
                    }
                    if (!empty($config['initialValidate'])) {
                        $formComp->setInitialValidate(true);
                    }
                    $formComp->buildFromSchema();
                    if (!(bool)$fData['tfReadOnly']) {
                        $formComp->addSubmit('Submit');
                    }
                    $node->appendChild($formComp->render());
                }
            }
        } else {
            // Legacy fallback
            $formId = (int)$el['eleContent'];
            if ($formId > 0) {
                global $db, $dialect;
                $qb = new QueryBuilder($dialect);
                $fData = $qb->table('tblForm')->select(['tfName', 'tfReadOnly'])->where('tfPK', '=', $formId)->executeFetch($db);
                if ($fData) {
                    $formName = $fData['tfName'];
                    $formComp = new FormComponent($dom, $formName);
                    if (!empty($config['valueProvider'])) {
                        $formComp->setValueProvider($config['valueProvider']);
                    }
                    if (!empty($config['initialValidate'])) {
                        $formComp->setInitialValidate(true);
                    }
                    $formComp->buildFromSchema();
                    if (!(bool)$fData['tfReadOnly']) {
                        $formComp->addSubmit('Submit');
                    }
                    $node->appendChild($formComp->render());
                }
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
            $chart = new ChartComponent($dom);
            if (!empty($config['dataProvider'])) {
                $chart->setDataProvider($config['dataProvider']);
            } else {
                $chart->setDataSource($config['source'] ?? null);
                $chart->setDataConfig(json_decode($config['config'] ?? '{}', true) ?: []);
            }
            $node->setAttribute('style', 'width: 100%;');
            $node->appendChild($chart->render());
        }
    } else if ($el['eleType'] === 'hyperlink') {
        $rawContent = htmlspecialchars_decode($el['eleContent'], ENT_QUOTES);
        $config = json_decode($rawContent, true);
        if ($config) {
            $hlink = new Hyperlink();
            $hlink->appendHyperlinkForm($dom, $node, $config['label'], $config['url'], $config['params'] ?? [], $config['cssClasses'] ?? []);
        }
    } else if (!in_array($el['eleType'], ['container', 'divider'])) {
        // Text content
        $node->textContent = htmlspecialchars_decode($el['eleContent'], ENT_QUOTES);
    }

    // Recursively render children
    foreach ($el['children'] as $child) {
        renderElement($child, $dom, $node);
    }
}

$dom->fabricateChild($target, 'h1', [], $pageData['pagTitle']);

foreach ($tree as $rootEl) {
    renderElement($rootEl, $dom, $target);
}
?>
