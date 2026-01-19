<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\BooleanPropType;

/**
 * Test BooleanPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\BooleanPropType
 * @group ui_patterns
 */
class BooleanPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = BooleanPropType::normalize($value, $this->testComponentProps['boolean']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('boolean', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Test rendered component with prop default false.
   *
   * @dataProvider renderingTestsDefaultFalse
   */
  public function testRenderingDefaultFalse(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('boolean_with_default_false', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Test rendered component with prop default true.
   *
   * @dataProvider renderingTestsDefaultTrue
   */
  public function testRenderingDefaultTrue(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('boolean_with_default_true', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, NULL],
      "false value" => [FALSE, FALSE],
      "true value" => [TRUE, TRUE],
      "integer 0" => [0, FALSE],
      "integer pos" => [22, TRUE],
      "string empty" => ["", FALSE],
      "string zero" => ["0", FALSE],
      "string not zero" => ["22", TRUE],
      "html" => ["<p>0</p>", TRUE],
      "markup 0" => [["#markup" => "0"], FALSE],
      "markup 1" => [["#markup" => "1"], TRUE],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-boolean"></div>',
      ],
      "false value" => [
        FALSE,
        '<div class="ui-patterns-props-boolean"></div>',
      ],
      "true value" => [
        TRUE,
        '<div class="ui-patterns-props-boolean">1</div>',
      ],
      "zero string value" => [
        "0",
        '<div class="ui-patterns-props-boolean"></div>',
      ],
      "not zero string value" => [
        "22",
        '<div class="ui-patterns-props-boolean">1</div>',
      ],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTestsDefaultFalse() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-boolean_with_default_false"></div>',
      ],
      "false value" => [
        FALSE,
        '<div class="ui-patterns-props-boolean_with_default_false"></div>',
      ],
      "true value" => [
        TRUE,
        '<div class="ui-patterns-props-boolean_with_default_false">1</div>',
      ],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTestsDefaultTrue() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-boolean_with_default_true">1</div>',
      ],
      "false value" => [
        FALSE,
        '<div class="ui-patterns-props-boolean_with_default_true"></div>',
      ],
      "true value" => [
        TRUE,
        '<div class="ui-patterns-props-boolean_with_default_true">1</div>',
      ],
    ];
  }

}
