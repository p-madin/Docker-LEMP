<?php

class View {
    private string $path;
    private array $data;
    private ?string $layout;

    public function __construct(string $path, array $data = [], ?string $layout = 'main') {
        $this->path = $path;
        $this->data = $data;
        $this->layout = $layout;
    }

    public static function render(string $path, array $data = [], ?string $layout = 'main'): self {
        return new self($path, $data, $layout);
    }

    public function execute(): string {
        global $dom, $sessionController;

        // 1. Setup Layout first (or fallback to basic)
        if ($this->layout === null) {
            $target = $dom->body;
        } else {
            $layoutClass = $this->layout . 'Layout';
            $layoutComp = class_exists($layoutClass) 
                ? new $layoutClass($dom) 
                : new LayoutComponent($dom);

            $this->injectGlobalState($layoutComp);
            
            // Build layout (attaches to body internally)
            $layoutComp->render();
            $target = $layoutComp->getContentTarget() ?: $dom->body;
        }

        // 2. Extract data for the view template
        extract($this->data);

        // 3. Render the view content into the DOM target
        $viewFile = __DIR__ . '/../views/' . $this->path . '.php';
        if (file_exists($viewFile)) {
            // The view template now has access to $dom and $target
            include $viewFile;
        } else {
            $dom->fabricateChild($target, "p", ["style" => "color:red;"], "View not found: " . $this->path);
        }

        return $dom->dom->saveHTML();
    }

    private function injectGlobalState(Component $layout): void {
        global $dom, $sessionController, $sysConfigController;

        // Auto-inject Navbar
        if (isset($sysConfigController) && isset($sessionController)) {
            $navbar = new Navbar($sysConfigController);
            // We'll need a way to pass the navbar to the layout
            if (method_exists($layout, 'setNavbar')) {
                $layout->setNavbar($navbar);
            }
        }
    }
}
