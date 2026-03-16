<?php

namespace App\Security;

/**
 * Class XssSanitizer
 * Core protection against Cross-Site Scripting.
 */
class XssSanitizer implements SanitizationStrategy {
    public function sanitize($input): string {
        return htmlspecialchars((string)$input, ENT_QUOTES, 'UTF-8');
    }
}
