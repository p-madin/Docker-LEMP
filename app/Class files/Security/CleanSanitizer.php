<?php

namespace App\Security;

/**
 * Class CleanSanitizer
 * A "pass-through" or basic sanitizer that serves as the base for decoration.
 */
class CleanSanitizer implements SanitizationStrategy {
    public function sanitize($input): string {
        return (string)$input;
    }
}
