<?php

namespace App\Security;

/**
 * Class AlphaDashDecorator
 * Strips all characters except letters, numbers, dashes, and underscores.
 * Mirrors the alpha_dash validation rule on both client and server.
 */
class AlphaDashDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return preg_replace('/[^a-zA-Z0-9\-_]/', '', $data);
    }
}
