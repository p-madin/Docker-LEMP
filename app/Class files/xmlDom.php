<?php

class xmlDom{

    public $dom;
    public $html;
    public $head;
    public $body;

    public function __construct(){
        $this->dom = new DOMDocument();

        $this->html = $this->dom->createElement('html');
        $this->head = $this->dom->createElement('head');
        $this->body = $this->dom->createElement('body');

        $this->dom->appendChild($this->html);
        $this->html->appendChild($this->head);
        $this->html->appendChild($this->body);
    }

    public function decorate_cascade(){
        $link = $this->dom->createElement('link');

        $link->setAttribute('rel', 'stylesheet');
        $link->setAttribute('type', 'text/css');
        $link->setAttribute('href', 'Static/styles.css');

        $this->head->appendChild($link);
    }

    public function appendChild($parent, $tagName, $attributes=array(), $innerContent=""){
        $returnable = $this->dom->createElement($tagName);

        foreach($attributes as $key=>$value){
            $returnable->setAttribute($key, $value);
        }

        $returnable->nodeValue = $innerContent;
        $parent->appendChild($returnable);

        return $returnable;
    }
}

?>