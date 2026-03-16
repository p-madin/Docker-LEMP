<?php

namespace App\Security;

/**
 * Class SanitizerDecorator
 * An abstract class to facilitate the creation of decorators.
 */
abstract class SanitizerDecorator implements SanitizationStrategy {
    protected $sanitizer;

    public function __construct(SanitizationStrategy $sanitizer) {
        $this->sanitizer = $sanitizer;
    }

    abstract public function sanitize($input): string;
}
