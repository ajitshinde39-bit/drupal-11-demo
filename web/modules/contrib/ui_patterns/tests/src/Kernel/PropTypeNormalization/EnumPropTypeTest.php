<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\EnumPropType;

/**
 * Test EnumPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\EnumPropType
 * @group ui_patterns
 */
class EnumPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = EnumPropType::normalize($value, $this->testComponentProps['enum_integer']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('enum_integer', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, NULL],
      "integer" => [2, 2],
      "string" => ["2", 2],
      "string bad" => ["BAD VALUE", NULL],
      "object" => [new \stdClass(), NULL],
      "array" => [[2], 2],
      "array assoc" => [["aa" => 2], 2],
      "array assoc bad" => [["1" => NULL, "aa" => 2], 2],
      "array markup" => [['#markup' => "2"], 2],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-enum_integer"></div>',
      ],
    ];
  }

}
