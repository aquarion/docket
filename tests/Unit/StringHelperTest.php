<?php

namespace Tests\Unit;

use App\Support\StringHelper;
use Tests\TestCase;

class StringHelperTest extends TestCase
{
  public function test_removes_emojis_from_text(): void
  {
    $text = 'Hello 👋 World 🌍';
    $clean = StringHelper::removeEmoji($text);

    $this->assertEquals('Hello  World ', $clean);
  }

  public function test_remove_emoji_preserves_regular_text(): void
  {
    $text = 'Just regular text';
    $clean = StringHelper::removeEmoji($text);

    $this->assertEquals('Just regular text', $clean);
  }
}
