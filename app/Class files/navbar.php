<?php

class Navbar {
    private array $items;

    public function __construct(SystemConfigController $sysConfigController) {
        $this->items = $sysConfigController->getNavbarItems();
    }

    /**
     * Renders the navbar at the top of the document body.
     * Uses the flex-table layout for a horizontal menu.
     */
    public function render($dom, $sessionController) {
        $userId = $sessionController->getPrimary('userID');
        
        $navContainer = $dom->fabricateChild(parent: $dom->body, tagName: "nav", attributes: ["class"=>"flex-table", "id"=>"main-navbar"]);

        $navRow = $dom->fabricateChild(parent: $navContainer, tagName: "div", attributes: ["class"=>"flex-row"]);

        foreach ($this->items as $item) {
            if ($item['discriminator'] === 'a') continue;
            if (!$item['protected'] || !is_null($userId)) {
                $cell = $dom->dom->createElement('div');
                $cell->setAttribute('class', 'flex-cell');
                $cell->setAttribute('style', 'text-align: center; padding: 10px;');
                
                $hyperlink = new Hyperlink();
                $hyperlink->appendHyperlinkForm($dom, $cell, $item['label'], $item['url']);
                
                $navRow->appendChild($cell);
            }
        }

        $toggleCell = $dom->dom->createElement('div');
        $toggleCell->setAttribute('class', 'flex-cell');
        
        $checkbox = $dom->dom->createElement('input');
        $checkbox->setAttribute('type', 'checkbox');
        $checkbox->setAttribute('id', 'disable_client_validation');
        
        $label = $dom->dom->createElement('label');
        $label->setAttribute('for', 'disable_client_validation');
        $label->setAttribute('style', 'font-size: 0.85em; cursor: pointer;');
        $label->textContent = 'noValidate';
        
        $toggleCell->appendChild($checkbox);
        $toggleCell->appendChild($label);
        
        $navRow->appendChild($toggleCell);

        return $navContainer;
    }
}