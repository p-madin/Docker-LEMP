<?php

class Navbar {
    private $items = [
        ['label' => 'Home', 'url' => 'index.php', 'protected' => false],
        ['label' => 'Dashboard', 'url' => 'dashboard.php', 'protected' => true],
        ['label' => 'Account Management', 'url' => 'account_management.php', 'protected' => true],
        ['label' => 'Logout', 'url' => 'logout-action.php', 'protected' => true]
    ];

    /**
     * Renders the navbar at the top of the document body.
     * Uses the flex-table layout for a horizontal menu.
     */
    public function render($dom, $sessionController) {
        $userId = $sessionController->getPrimary('userID');
        
        $navContainer = $dom->appendChild(parent: $dom->body, tagName: "div", attributes: ["class"=>"flex-table", "id"=>"main-navbar"]);

        $navRow = $dom->appendChild(parent: $navContainer, tagName: "div", attributes: ["class"=>"flex-row"]);

        foreach ($this->items as $item) {
            if (!$item['protected'] || !is_null($userId)) {
                $cell = $dom->dom->createElement('div');
                $cell->setAttribute('class', 'flex-cell');
                $cell->setAttribute('style', 'text-align: center; padding: 10px;');
                
                $hyperlink = new Hyperlink();
                $hyperlink->render($dom, $cell, $item['label'], $item['url']);
                
                $navRow->appendChild($cell);
            }
        }

        return $navContainer;
    }
}