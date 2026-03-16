<?php

namespace App\Security;

use DOMDocument;
use DOMXPath;

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

        $dom = new DOMDocument();
        // Use LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD to avoid adding <html>/<body> tags
        @$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
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
