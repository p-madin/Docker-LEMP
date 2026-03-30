<?php

namespace App\Security;

/**
 * Class TextareaWhitespaceDecorator
 * Normalizes horizontal whitespace only, preserving vertical whitespace (newlines).
 * Intended for <textarea> inputs where line breaks are meaningful.
 */
class TextareaWhitespaceDecorator extends SanitizerDecorator {
    public function sanitize($input): string {
        $data = $this->sanitizer->sanitize($input);
        // Collapse horizontal whitespace runs to a single space, per line
        $lines = explode("\n", $data);
        $lines = array_map(fn($line) => trim(preg_replace('/[^\S\n]+/', ' ', $line)), $lines);
        // Remove leading/trailing blank lines
        return trim(implode("\n", $lines));
    }
}
