<?php
namespace App\Security;

/**
 * Class SlugDecorator
 * Whitelists only lowercase alphanumeric characters and hyphens.
 */
class SlugDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        // Lowercase everything
        $data = strtolower($data);
        // Remove anything that isn't a-z, 0-9, or -
        return preg_replace('/[^a-z0-9\-]/', '', $data);
    }
}
