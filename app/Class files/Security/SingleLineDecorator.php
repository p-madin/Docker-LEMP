<?php

namespace App\Security;

/**
 * Class SingleLineDecorator
 * Removes all newline characters.
 */
class SingleLineDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return str_replace(["\r", "\n"], '', $data);
    }
}
