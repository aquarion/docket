<?php

namespace App\Support;

class StringHelper
{
    /**
     * Removes emojis from the given text.
     *
     * Uses Unicode emoji properties to match all emoji characters including:
     * - Standard emoji (\p{Emoji})
     * - Emoji with presentation selectors (\x{FE0F})
     * - Keycap sequences (#️⃣, *️⃣, etc.)
     * - Flag sequences and other extended pictographic characters
     */
    public static function removeEmoji(string $text): string
    {
        // Match emoji characters and sequences
        // \p{Extended_Pictographic} covers most modern emoji
        // \x{FE0F} is the emoji presentation selector (variation selector-16)
        // \x{20E3} is the combining enclosing keycap
        return preg_replace('/[\p{Extended_Pictographic}\x{FE0F}\x{20E3}]+/u', '', $text);
    }

    /**
     * Sanitizes a string to be a valid CSS class name.
     * CSS class names must start with a letter, underscore, or hyphen,
     * and can contain letters, numbers, hyphens, and underscores.
     *
     * IMPORTANT: Keep this function in sync with the JavaScript version:
     * resources/js/css-utils.js:CssUtils.sanitizeCssClassName()
     */
    public static function sanitizeCssClassName(string $name): string
    {
        // Remove any characters that aren't alphanumeric, hyphen, or underscore
        $sanitized = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $name);

        // Ensure it doesn't start with a number or hyphen followed by number
        if (preg_match('/^(\d|-\d)/', $sanitized)) {
            $sanitized = 'cal-'.$sanitized;
        }

        // Ensure it doesn't start with two hyphens (invalid)
        $sanitized = preg_replace('/^--+/', 'cal-', $sanitized);

        // Remove consecutive hyphens and underscores
        $sanitized = preg_replace('/[-_]{2,}/', '-', $sanitized);

        // Trim leading/trailing hyphens and underscores
        $sanitized = trim($sanitized, '-_');

        // If empty after sanitization, provide a fallback
        if (empty($sanitized)) {
            $sanitized = 'calendar-'.md5($name);
        }

        return $sanitized;
    }
}
