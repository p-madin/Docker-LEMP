<?php

namespace App\Security;

/**
 * Class HtmlEscapeDecorator
 * Specifically escapes HTML entities.
 */
class HtmlEscapeDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
}
