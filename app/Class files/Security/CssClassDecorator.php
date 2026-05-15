<?php
namespace App\Security;

/**
 * Class CssClassDecorator
 * Whitelists only alphanumeric characters, hyphens, underscores, and spaces.
 */
class CssClassDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        // Remove anything that isn't alphanumeric, -, _, or space
        return preg_replace('/[^a-zA-Z0-9\-_ ]/', '', $data);
    }
}
