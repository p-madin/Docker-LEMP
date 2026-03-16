<?php

namespace App\Security;

/**
 * Class WhitespaceNormalization
 * Trims whitespace and reduces multiple spaces to a single space.
 */
class WhitespaceNormalization extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return trim(preg_replace('/\s+/', ' ', $data));
    }
}
