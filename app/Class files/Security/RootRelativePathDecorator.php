<?php

namespace App\Security;

/**
 * Ensures a string is a root-relative path (starts with / but not //).
 * Used to prevent Open Redirect vulnerabilities.
 */
class RootRelativePathDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $input = $this->sanitizer->sanitize($input);
        
        // Ensure it starts with / and not followed by another / (protocol-relative)
        if (!preg_match('/^\/([^\/]|$)/', (string)$input)) {
            return '/';
        }
        
        return (string)$input;
    }
}
