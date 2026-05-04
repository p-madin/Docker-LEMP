<?php

class NavbarComponent extends Component {
    private array $items;
    private $userId;
    private $sessionController;
    private AssetManager $assetManager;

    public function __construct(xmlDom $xmlDom, array $items, $sessionController, AssetManager $assetManager) {
        parent::__construct($xmlDom, 'nav', ['class' => 'flex-table', 'id' => 'main-navbar']);
        $this->items = $items;
        $this->sessionController = $sessionController;
        $this->userId = $sessionController->getSystemUserId();
        $this->assetManager = $assetManager;
    }

    protected function build(): void {
        $navRow = $this->fabricateChild($this->root, "div", ["class" => "flex-row"]);

        // 0. Logo
        $logoCell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell logo icon"]);
        $this->fabricateChild($logoCell, "img", [
            "src" => $this->assetManager->getImageUrl('/Static/logo.svg'),
            "alt" => "Logo",
            "style" => "height: 40px; display: block;"
        ]);

        if(!is_null($this->userId)){
            global $db, $dialect, $eventStore;
            
            $undoableEvents = $eventStore->getUndoableEvents($this->sessionController);
            $undoCount = count($undoableEvents);
            
            $redoableEvents = $eventStore->getRedoableEvents($this->sessionController);
            $redoStack = $this->sessionController->getPrimary('redo_stack');
            if ($redoStack !== null && !is_array($redoStack)) {
                $redoStack = [$redoStack];
            }
            $redoCount = is_array($redoStack) ? count($redoStack) : 0;

            $undoCell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell icon"]);
            $redoCell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell icon"]);

            $hyperlink = new Hyperlink();
            $undoHyperlink = $hyperlink->appendHyperlinkForm($this->xmlDom, $undoCell, '↻ ' . $undoCount, '/undo', ['mode' => 'undo'], ['undo-button']);
            $redoHyperlink = $hyperlink->appendHyperlinkForm($this->xmlDom, $redoCell, '↷ ' . $redoCount, '/undo', ['mode' => 'redo'], ['redo-button']);

            $undoHyperlink_element = $undoHyperlink->querySelector("a");
            
            $undoCaption = ($undoCount>0) ? "Undo ".$undoableEvents[0]['event_type']." event" : "Nothing to undo";
            
            $undoHyperlink_element->setAttribute("title", $undoCaption);

            $redoHyperlink_element = $redoHyperlink->querySelector("a");
            
            $redoCaption = ($redoCount>0) ? "Redo ".$redoableEvents[0]['event_type']." event" : "Nothing to redo";

            $redoHyperlink_element->setAttribute("title", $redoCaption);

            if($undoCount === 0){
                $undoCell->setAttribute('class', $undoCell->getAttribute('class').' disabled');
            }

            if($redoCount === 0){
                $redoCell->setAttribute('class', $redoCell->getAttribute('class').' disabled');
            }
        }

        // 1. Group items by parent
        $tree = [];
        $children = [];
        foreach ($this->items as $item) {
            if ($item['discriminator'] === 'a' || $item['discriminator'] === 'h') continue;
            if ($item['protected'] && is_null($this->userId)) continue;

            if ($item['parentFK']) {
                $children[$item['parentFK']][] = $item;
            } else {
                $tree[] = $item;
            }
        }

        // 2. Render Tree
        foreach ($tree as $item) {
            $cell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell"]);

            if (isset($children[$item['id']])) {
                // Render as <details> for nested menu
                $details = $this->fabricateChild($cell, "details", ["class" => "nav-dropdown"]);
                $this->fabricateChild($details, "summary", ["class" => "nav-summary"], $item['label']);
                
                $dropdownContent = $this->fabricateChild($details, "div", ["class" => "dropdown-content"]);
                foreach ($children[$item['id']] as $child) {
                    $childCell = $this->fabricateChild($dropdownContent, "div", ["class" => "child-cell"]);
                    $hyperlink = new Hyperlink();
                    $hyperlink->appendHyperlinkForm($this->xmlDom, $childCell, $child['label'], $child['url']);
                }
            } else {
                // Standard top-level link
                $hyperlink = new Hyperlink();
                $hyperlink->appendHyperlinkForm($this->xmlDom, $cell, $item['label'], $item['url']);
            }
        }

        // 3. noValidate Toggle (Hardcoded at end)
        $toggleCell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell"]);
        $this->fabricateChild($toggleCell, "input", ["type" => "checkbox", "id" => "disable_client_validation"]);
        $this->fabricateChild($toggleCell, "label", [
            "for" => "disable_client_validation",
            "style" => "font-size: 0.85em; cursor: pointer;"
        ], "noValidate");
    }
}

class Navbar {
    private array $items;

    public function __construct(SystemConfigController $sysConfigController) {
        $this->items = $sysConfigController->getNavbarItems();
    }

    public function render(xmlDom $dom, $sessionController, $assetManager) {
        
        $component = new NavbarComponent($dom, $this->items, $sessionController, $assetManager);
        return $dom->body->append($component->render());
    }
}
?>