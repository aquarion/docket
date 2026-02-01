<?php

namespace App\Support;

class ColorHelper
{
    /**
     * Adjusts the brightness of a color.
     * Negative values make the color darker, positive values make the color lighter.
     */
    public static function adjustBrightness(string $hex, int $steps): string
    {
        // Steps should be between -255 and 255. Negative = darker, positive = lighter
        $steps = max(-255, min(255, $steps));

        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2).
              str_repeat(substr($hex, 1, 1), 2).
              str_repeat(substr($hex, 2, 1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);
        $return = '#';

        foreach ($color_parts as $color) {
            $color = hexdec($color); // Convert to decimal
            $color = max(0, min(255, $color + $steps)); // Adjust color
            // Make two char hex code
            $return .= str_pad(dechex($color), 2, '0', STR_PAD_LEFT);
        }

        return $return;
    }

    /**
     * Converts a hexadecimal color code to RGBA format.
     */
    public static function hexToRGBA(string $hex): array
    {
        // Normalize into a six character long hex string
        $hex = str_replace('#', '', $hex);
        if (strlen($hex) == 3) {
            $hex = str_repeat(substr($hex, 0, 1), 2).
              str_repeat(substr($hex, 1, 1), 2).
              str_repeat(substr($hex, 2, 1), 2);
        }

        // Split into three parts: R, G and B
        $color_parts = str_split($hex, 2);

        $color_rgb = [];
        foreach ($color_parts as $color) {
            $color_rgb[] = hexdec($color); // Convert to decimal
        }

        return $color_rgb;
    }

    /**
     * Converts an RGB color value to a CSS color string.
     */
    public static function rgbToCSS(string $hexColor, float $alpha): string
    {
        $color_rgb = self::hexToRGBA($hexColor);

        return "rgba({$color_rgb[0]}, {$color_rgb[1]}, {$color_rgb[2]}, {$alpha})";
    }

    /**
     * Removes emojis from the given text.
     */
    public static function removeEmoji(string $text): string
    {
        $preg = '/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|'.
          '\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|'.
          '\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}]'.
          '[\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|'.
          '[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}]'.
          '[\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|'.
          '[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}]'.
          '[\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]'.
          '?/u';

        return preg_replace($preg, '', $text);
    }
}
