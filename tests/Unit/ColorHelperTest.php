<?php

namespace Tests\Unit;

use App\Support\ColorHelper;
use Tests\TestCase;

class ColorHelperTest extends TestCase
{
  public function test_adjust_brightness_darker(): void
  {
    $color = '#FF0000'; // Red
    $darker = ColorHelper::adjustBrightness($color, -50);

    $this->assertEquals('#cd0000', $darker);
  }

  public function test_adjust_brightness_lighter(): void
  {
    $color = '#0000FF'; // Blue
    $lighter = ColorHelper::adjustBrightness($color, 50);

    $this->assertEquals('#3232ff', $lighter);
  }

  public function test_hex_to_rgba(): void
  {
    $color = '#FF8800';
    $rgb = ColorHelper::hexToRGBA($color);

    $this->assertEquals([255, 136, 0], $rgb);
  }

  public function test_hex_to_rgba_short_format(): void
  {
    $color = '#F80'; // Short format
    $rgb = ColorHelper::hexToRGBA($color);

    $this->assertEquals([255, 136, 0], $rgb);
  }

  public function test_rgb_to_css(): void
  {
    $color = '#FF8800';
    $css = ColorHelper::rgbToCSS($color, 0.5);

    $this->assertEquals('rgba(255, 136, 0, 0.5)', $css);
  }

  public function test_remove_emoji(): void
  {
    $text = 'Hello 👋 World 🌍';
    $clean = ColorHelper::removeEmoji($text);

    $this->assertEquals('Hello  World ', $clean);
  }

  public function test_remove_emoji_preserves_regular_text(): void
  {
    $text = 'Just regular text';
    $clean = ColorHelper::removeEmoji($text);

    $this->assertEquals('Just regular text', $clean);
  }
}
