<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\VariantPropType;

/**
 * Test VariantPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\VariantPropType
 * @group ui_patterns
 */
class VariantPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = VariantPropType::normalize($value, $this->testComponentProps['variant']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('variant', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, ""],
      "empty string" => ["", ""],
      "default value" => ["default", "default"],
      "other value" => ["other", "other"],
      "bad value" => ["BAD", ""],
      "integer value" => [2, ""],
      "array value" => [[], ""],
      "object value" => [new \stdClass(), ""],
      "render array" => [["#markup" => "other"], "other"],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        ' class="ui-patterns-test-component ui-patterns-test-component-variant-"',
      ],
      "empty value" => [
        "",
        ' class="ui-patterns-test-component ui-patterns-test-component-variant-"',
      ],
      "other" => [
        "other",
        ' class="ui-patterns-test-component ui-patterns-test-component-variant-other"',
      ],
    ];
  }

}
