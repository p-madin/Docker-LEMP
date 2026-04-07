<?php

namespace App\Security;

/**
 * Class AttributeWhitelistDecorator
 * Filters HTML attributes.
 */
class AttributeWhitelistDecorator extends SanitizerDecorator {
    protected $allowedAttributes;

    public function __construct(SanitizationStrategy $sanitizer, array $allowedAttributes = ['href', 'title', 'src', 'alt']) {
        parent::__construct($sanitizer);
        $this->allowedAttributes = $allowedAttributes;
    }

    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        if (empty($data)) return '';

        $dom = \Dom\HTMLDocument::createFromString($data, LIBXML_NOERROR);
        $xpath = new \DOM\XPath($dom);
        $nodes = $xpath->query('//@*');
        foreach ($nodes as $node) {
            if (!in_array($node->nodeName, $this->allowedAttributes)) {
                $node->parentNode->removeAttribute($node->nodeName);
            }
        }
        
        $output = $dom->saveHTML();
        return $output !== false ? $output : '';
    }
}
