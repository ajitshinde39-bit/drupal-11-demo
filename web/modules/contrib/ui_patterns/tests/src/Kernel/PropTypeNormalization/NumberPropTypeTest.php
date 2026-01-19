<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\NumberPropType;

/**
 * Test NumberPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\NumberPropType
 * @group ui_patterns
 */
class NumberPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method with prop number.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = NumberPropType::normalize($value, $this->testComponentProps['number']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test normalize static method with prop number_with_min_max.
   *
   * @dataProvider normalizationTestsMinMax
   */
  public function testNormalizationNumberMinMax(mixed $value, mixed $expected) : void {
    $normalized = NumberPropType::normalize($value, $this->testComponentProps['number_with_min_max']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test normalize static method with prop integer.
   *
   * @dataProvider normalizationTestsInteger
   */
  public function testNormalizationInteger(mixed $value, mixed $expected) : void {
    $normalized = NumberPropType::normalize($value, $this->testComponentProps['integer']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test normalize static method with prop integer_with_min_max.
   *
   * @dataProvider normalizationTestsIntegerMinMax
   */
  public function testNormalizationIntegerMinMax(mixed $value, mixed $expected) : void {
    $normalized = NumberPropType::normalize($value, $this->testComponentProps['integer_with_min_max']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('number', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, NULL],
      "integer value" => [1, 1],
      "float value" => [1.1, 1.1],
      "string value" => ["1", 1],
      "float string value" => ["1.1", 1.1],
    ];
  }

  /**
   * Provides data for testNormalizationInteger.
   */
  public static function normalizationTestsInteger() : array {
    return [
      "null value" => [NULL, NULL],
      "integer value to integer" => [1, 1],
      "float value to integer" => [1.1, 1],
      "string value to integer" => ["1", 1],
      "float string value to integer" => ["1.1", 1],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTestsMinMax() : array {
    return [
      "null value" => [NULL, NULL],
      "integer value" => [5, 5],
      "integer value below" => [2, NULL],
      "integer value above" => [332, NULL],
      "float value" => [10, 10],
      "float value below" => [-4.0, NULL],
      "float value above" => [345.65, NULL],
      "string value above" => ["12", NULL],
      "string value below" => ["2", NULL],
      "float string value" => ["1.1", NULL],
    ];
  }

  /**
   * Provides data for testNormalizationInteger.
   */
  public static function normalizationTestsIntegerMinMax() : array {
    return [
      "null value" => [NULL, NULL],
      "integer value to integer" => [7, 7],
      "integer value below" => [2, NULL],
      "integer value above" => [332, NULL],
      "float value to integer" => [7.1, 7],
      "string value to integer" => ["8", 8],
      "string value above" => ["12", NULL],
      "string value below" => ["2", NULL],
      "float string value to integer" => ["8.1", 8],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-number"></div>',
      ],
    ];
  }

}
