<?php

class xmlDom{

    public $dom;
    public $html;
    public $head;
    public $body;
    protected $security;

    public function __construct(){
        $this->dom = \DOM\HTMLDocument::createEmpty();

        $this->html = $this->dom->createElement('html');
        $this->head = $this->dom->createElement('head');
        $this->body = $this->dom->createElement('body');

        $this->dom->appendChild($this->html);
        $this->html->appendChild($this->head);
        $this->html->appendChild($this->body);

        // meta name="viewport" content="width=device-width, initial-scale=1.0">
        $meta = $this->dom->createElement('meta');
        $meta->setAttribute('name', 'viewport');
        $meta->setAttribute('content', 'width=device-width, initial-scale=1.0');
        $this->head->appendChild($meta);

        // Initialize output security strategy
        // NOTE: We omit HtmlEscapeDecorator/XssSanitizer because nodeValue handles encoding
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

        $this->setTitle("LEMP Stack App");
    }

    public function injectAssets(AssetManager $assetManager){
        $assetManager->applyToDom($this);
    }


    public function decorate_navbar($navbar, $sessionController, $assetManager){
        $navbar->render($this, $sessionController, $assetManager);
    }

    public function setTitle($title) {
        $titleElement = $this->dom->getElementsByTagName('title')->item(0);
        if (!$titleElement) {
            $titleElement = $this->dom->createElement('title');
            $this->head->appendChild($titleElement);
        }
        $titleElement->textContent = $this->security->process($title);
    }

    public function fabricateChild($parent, $tagName, $attributes=array(), $innerContent=""){
        $returnable = $this->dom->createElement($tagName);

        foreach($attributes as $key=>$value){
            $returnable->setAttribute($key, $value);
        }

        if(!empty($innerContent)){
            $returnable->textContent = $this->security->process($innerContent);
        }
        
        $parent->appendChild($returnable);

        return $returnable;
    }
}