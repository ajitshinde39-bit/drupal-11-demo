<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;

/**
 * Test SlotPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType
 * @group ui_patterns
 */
class SlotPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = SlotPropType::normalize($value);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('slot', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, ["#cache" => []]],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-slots-slot"></div>',
      ],
      "string value" => ["my slot", "my slot"],
      "string in array" => [["my slot"], "my slot"],
      "string as array value" => [["aa" => "my slot"], "my slot"],
      "markup value" => [Markup::create("my slot"), "my slot"],
      "markup in array" => [[Markup::create("my slot")], "my slot"],
      "markup in array value" => [["uu" => Markup::create("my slot")], "my slot"],
      "translatable" => [new TranslatableMarkup("my slot"), "my slot"],
      "t function" => [t("my slot"), "my slot"],
      "array value" => [["#markup" => "my slot"], "my slot"],
      "inline template" => [["#type" => "inline_template", "#template" => "my slot"], "my slot"],
      "array value with weight" => [
        ["b" => ["#weight" => 2, "#markup" => "slot"], "a" => ["#weight" => 1, "#markup" => "my "]],
        "my slot",
      ],
      "render array special" => [
        [0 => ["#markup" => "my slot", "randomKey" => []]],
        "my slot",
      ],
    ];
  }

}
