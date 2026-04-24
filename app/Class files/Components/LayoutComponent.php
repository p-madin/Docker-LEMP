<?php

class LayoutComponent extends Component {
    protected string $content;
    protected ?Navbar $navbar = null;
    protected string $title = "Application";
    protected ?\Dom\HTMLElement $contentTarget = null;

    public function __construct(xmlDom $xmlDom, string $content = "", array $attributes = []) {
        parent::__construct($xmlDom, 'html', array_merge(['lang' => 'en'], $attributes));
        $this->content = $content;
    }

    public function getContentTarget(): ?\Dom\HTMLElement {
        return $this->contentTarget;
    }

    public function setNavbar(Navbar $navbar): void {
        $this->navbar = $navbar;
    }

    public function setTitle(string $title): void {
        $this->title = $title;
    }

    protected function build(): void {
        global $sessionController;

        if ($this->navbar) {
            $this->navbar->render($this->xmlDom, $sessionController);
        }

        $this->contentTarget = $this->fabricateChild($this->xmlDom->body, "div", ["class" => "container", "id" => "content-root"]);
    }
}
