<?php

namespace App\Security;

/**
 * Interface SanitizationStrategy
 * The core interface that all sanitizers and decorators must implement.
 */
interface SanitizationStrategy {
    /**
     * @param mixed $input
     * @return string
     */
    public function sanitize($input): string;
}
