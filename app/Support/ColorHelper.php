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
}
