<?php

namespace App\Security;

/**
 * Sanitizes input as a signed integer.
 */
class IntegerSanitizerDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $input = $this->sanitizer->sanitize($input);
        
        // Remove everything except digits and optional leading minus sign
        $sanitized = preg_replace('/(?<!^)-|[^0-9-]/', '', (string)$input);
        
        // If it's just a minus or empty, return '0'
        if ($sanitized === '-' || $sanitized === '') {
            return '0';
        }
        
        return (string)$sanitized;
    }
}
