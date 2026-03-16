<?php

namespace App\Security;

/**
 * Class UrlSanitizerDecorator
 * Validates and sanitizes URLs.
 */
class UrlSanitizerDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return filter_var($data, FILTER_SANITIZE_URL);
    }
}
