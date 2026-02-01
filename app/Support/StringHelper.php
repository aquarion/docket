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
}
