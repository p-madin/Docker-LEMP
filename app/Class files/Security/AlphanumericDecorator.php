<?php

namespace App\Security;

/**
 * Class AlphanumericDecorator
 * Whitelists only alphanumeric characters.
 */
class AlphanumericDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return preg_replace('/[^a-zA-Z0-9]/', '', $data);
    }
}
