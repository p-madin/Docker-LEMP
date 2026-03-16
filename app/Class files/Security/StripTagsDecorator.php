<?php

namespace App\Security;

/**
 * Class StripTagsDecorator
 * Removes all HTML tags.
 */
class StripTagsDecorator extends SanitizerDecorator {
    protected $allowedTags;

    public function __construct(SanitizationStrategy $sanitizer, $allowedTags = null) {
        parent::__construct($sanitizer);
        $this->allowedTags = $allowedTags;
    }

    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return strip_tags($data, $this->allowedTags);
    }
}
