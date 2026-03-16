<?php

namespace App\Security;

/**
 * Class LengthLimitDecorator
 * Truncates input to a specific length.
 */
class LengthLimitDecorator extends SanitizerDecorator {
    protected $limit;

    public function __construct(SanitizationStrategy $sanitizer, int $limit = 255) {
        parent::__construct($sanitizer);
        $this->limit = $limit;
    }

    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return mb_substr($data, 0, $this->limit, 'UTF-8');
    }
}
