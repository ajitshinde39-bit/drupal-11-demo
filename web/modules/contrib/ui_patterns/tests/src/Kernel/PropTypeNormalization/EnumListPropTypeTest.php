<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Core\Template\Attribute;
use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\EnumListPropType;

/**
 * Test EnumListPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\EnumListPropType
 * @group ui_patterns
 */
class EnumListPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = EnumListPropType::normalize($value, $this->testComponentProps['enum_list_multiple']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('enum_list_multiple', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, []],
      "single item" => [[2], [2]],
      "single item string" => [["2"], [2]],
      "single string" => ["2", [2]],
      "multiple items" => [[2, 2, 2], [2, 2, 2]],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-enum_list_multiple"></div>',
      ],
      "multiple items with bad values" => [
        [2, "BAD", "2", 2, 444, "BAD", new Attribute(), [2]],
        '<div class="ui-patterns-props-enum_list_multiple"><span>2</span><span>2</span><span>2</span></div>',
      ],
    ];
  }

}
