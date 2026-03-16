<?php

namespace App\Security;

/**
 * Class MultiLineNormalizeDecorator
 * Normalizes line endings to \n.
 */
class MultiLineNormalizeDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return str_replace(["\r\n", "\r"], "\n", $data);
    }
}
