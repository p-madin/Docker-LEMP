<?php

abstract class Component {
    protected xmlDom $xmlDom;
    protected \DOM\HTMLDocument $dom;
    protected \DOM\Element $root;
    protected \App\Security\SecurityValidation $security;
    protected array $slots = [];

    /**
     * @param xmlDom $xmlDom The xmlDom instance this component belongs to.
     * @param string $tagName The root tag for this component.
     * @param array $attributes Initial attributes for the root tag.
     */
    public function __construct(xmlDom $xmlDom, string $tagName = 'div', array $attributes = []) {
        $this->xmlDom = $xmlDom;
        $this->dom = $xmlDom->dom;
        $this->root = $this->dom->createElement($tagName);
        
        foreach ($attributes as $key => $value) {
            $this->root->setAttribute($key, $value);
        }

        // Initialize security strategy (mirrors xmlDom)
        $this->security = new \App\Security\SecurityValidation();
        $this->security->setStrategy(
            new \App\Security\StripTagsDecorator(
                new \App\Security\WhitespaceNormalization(
                    new \App\Security\MultiLineNormalizeDecorator(
                        new \App\Security\CleanSanitizer()
                    )
                )
            )
        );
    }

    /**
     * Assigns content to a specific slot.
     * 
     * @param string $name The data-slot name.
     * @param mixed $content Either a DOMNode, a Component, or a string.
     */
    public function setSlot(string $name, $content) {
        $this->slots[$name] = $content;
        return $this;
    }

    protected bool $built = false;

    /**
     * Renders the component by processing slots and returning the root element.
     */
    public function render(): \DOM\Element {
        if (!$this->built) {
            $this->build();
            $this->built = true;
        }
        $this->processSlots();
        return $this->root;
    }


    /**
     * Hook for concrete components to build their internal structure.
     */
    protected abstract function build(): void;

    /**
     * Internal method to inject slot content using querySelector.
     */
    protected function processSlots(): void {
        foreach ($this->slots as $name => $content) {
            $slotElement = $this->root->querySelector("[data-slot='{$name}']");
            if ($slotElement) {
                // Clear existing content in the slot
                $slotElement->textContent = '';
                
                if ($content instanceof \DOM\Node) {
                    $slotElement->appendChild($content);
                } elseif ($content instanceof Component) {
                    $slotElement->appendChild($content->render());
                } else {
                    // Apply security processing to raw text
                    $slotElement->textContent = $this->security->process((string)$content);
                }
            }
        }
    }

    /**
     * Helper to create and append children deliberately (mirrors fabricateChild).
     */
    protected function fabricateChild($parent, $tagName, $attributes = [], $innerContent = ""): \DOM\Element {
        $element = $this->dom->createElement($tagName);
        foreach ($attributes as $key => $value) {
            $element->setAttribute($key, $value);
        }
        if (!empty($innerContent)) {
            $element->textContent = $this->security->process($innerContent);
        }
        $parent->appendChild($element);
        return $element;
    }
}
