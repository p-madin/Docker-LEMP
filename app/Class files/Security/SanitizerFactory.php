<?php

namespace App\Security;

class SanitizerFactory {
    /**
     * Creates the default sanitization strategy used when no specific type matches.
     * This ensures everything globally is safely stripped of tags and escaped unless overridden.
     */
    public static function createDefault(): SanitizationStrategy {
        return new HtmlEscapeDecorator(
            new SingleLineDecorator(
                new WhitespaceNormalization(
                    new StripTagsDecorator(
                        new CleanSanitizer()
                    )
                )
            )
        );
    }

    /**
     * Creates a specific sanitization strategy based on a given input type.
     * Returns null if no specific strategy exists (which implies the default should be used)
     * or if the field should not be sanitized (like password).
     */
    public static function createForType(string $type): ?SanitizationStrategy {
        return match($type) {
            // Preserves newlines; collapses horizontal whitespace per line
            'textarea' => new HtmlEscapeDecorator(
                new TextareaWhitespaceDecorator(
                    new MultiLineNormalizeDecorator(
                        new StripTagsDecorator(
                            new CleanSanitizer()
                        )
                    )
                )
            ),
            // Strips everything but digits; no HTML sanitization needed
            'number' => new IntegerSanitizerDecorator(
                new CleanSanitizer()
            ),
            // Skipped fields — sanitized separately or not at all
            'password', 'hidden' => null,
            // Falls back to global default strategy
            default => null,
        };
    }
}
