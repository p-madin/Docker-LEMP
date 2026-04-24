<?php

class NavbarComponent extends Component {
    private array $items;
    private $userId;

    public function __construct(xmlDom $xmlDom, array $items, $userId) {
        parent::__construct($xmlDom, 'nav', ['class' => 'flex-table', 'id' => 'main-navbar']);
        $this->items = $items;
        $this->userId = $userId;
    }

    protected function build(): void {
        $navRow = $this->fabricateChild($this->root, "div", ["class" => "flex-row"]);

        // 1. Group items by parent
        $tree = [];
        $children = [];
        foreach ($this->items as $item) {
            if ($item['discriminator'] === 'a') continue;
            if ($item['protected'] && is_null($this->userId)) continue;

            if ($item['parentFK']) {
                $children[$item['parentFK']][] = $item;
            } else {
                $tree[] = $item;
            }
        }

        // 2. Render Tree
        foreach ($tree as $item) {
            $cell = $this->fabricateChild($navRow, "div", [
                "class" => "flex-cell",
                "style" => "text-align: center; padding: 10px;"
            ]);

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
        $toggleCell = $this->fabricateChild($navRow, "div", ["class" => "flex-cell", "style" => "padding: 10px;"]);
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

    public function render(xmlDom $dom, $sessionController) {
        $userId = $sessionController->getPrimary('userID');
        $component = new NavbarComponent($dom, $this->items, $userId);
        return $dom->body->append($component->render());
    }
}