<?php

class xmlDom{

    public $dom;
    public $html;
    public $head;
    public $body;
    protected $security;

    public function __construct(){
        $this->dom = new DOMDocument();

        $this->html = $this->dom->createElement('html');
        $this->head = $this->dom->createElement('head');
        $this->body = $this->dom->createElement('body');

        $this->dom->appendChild($this->html);
        $this->html->appendChild($this->head);
        $this->html->appendChild($this->body);

        // Initialize output security strategy
        // NOTE: We omit HtmlEscapeDecorator/XssSanitizer because nodeValue handles encoding
        $this->security = new \App\Security\SecurityValidation();
        $this->security->setStrategy(
            new \App\Security\StripTagsDecorator(
                new \App\Security\WhitespaceNormalization(
                    new \App\Security\CleanSanitizer()
                )
            )
        );
    }

    public function decorate_javascript(){
        $script = $this->dom->createElement('script');

        $script->setAttribute('type', 'text/javascript');
        $script->setAttribute('src', 'Static/exceptions.js');

        $this->head->appendChild($script);

        $script2 = $this->dom->createElement('script');
        $script2->setAttribute('type', 'text/javascript');
        $script2->setAttribute('src', 'Static/validator.js');
        $this->head->appendChild($script2);
    }

    public function decorate_cascade(){
        $link = $this->dom->createElement('link');

        $link->setAttribute('rel', 'stylesheet');
        $link->setAttribute('type', 'text/css');
        $link->setAttribute('href', 'Static/styles.css');

        $this->head->appendChild($link);
        $link = $this->dom->createElement('link');

        $link->setAttribute('rel', 'stylesheet');
        $link->setAttribute('type', 'text/css');
        $link->setAttribute('href', 'Static/dataGraphStyles.css');

        $this->head->appendChild($link);
    }

    public function decorate_navbar($navbar, $sessionController){
        $navbar->render($this, $sessionController);
    }

    public function fabricateChild($parent, $tagName, $attributes=array(), $innerContent=""){
        $returnable = $this->dom->createElement($tagName);

        foreach($attributes as $key=>$value){
            $returnable->setAttribute($key, $value);
        }

        if(!empty($innerContent)){
            $returnable->nodeValue = $this->security->process($innerContent);
        }
        
        $parent->appendChild($returnable);

        return $returnable;
    }
}