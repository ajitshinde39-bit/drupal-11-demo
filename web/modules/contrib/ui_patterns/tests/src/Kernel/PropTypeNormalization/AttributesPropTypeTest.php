<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Core\Template\Attribute;
use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\AttributesPropType;

/**
 * Test AttributesPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\AttributesPropType
 * @group ui_patterns
 */
class AttributesPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = AttributesPropType::normalize($value, $this->testComponentProps['attributes']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('attributes', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "Empty value" => [[], []],
      "Standardized primitives, so already OK" => self::standardizedPrimitives(),
      "Type transformations" => self::typeTransformation(),
      "List array" => self::listArray(),
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "Empty Value" => [
        [],
        '<div data-component-id="ui_patterns_test:test-component"></div>',
      ],
      "attribute_object" => [
        new Attribute(["data-foo" => "bar"]),
        ' data-foo="bar"',
      ],
      "integer" => [
        ["data-foo" => 1],
        ' data-foo="1"',
      ],
      "array" => [
        ["key" => ["1", "2"]],
        ' key="1 2"',
      ],
      "escaping" => [
        ["key" => '"'],
        ' key="&quot;"',
      ],
      "rendered value" => [
        ["key" => ["#markup" => "value"]],
        ' key="value"',
      ],
    ];
  }

  /**
   * Standardized primitives, so already OK.
   */
  protected static function standardizedPrimitives() {
    $value = [
      "foo" => "bar",
      "string" => "Lorem ipsum",
      "array" => [
        "One",
        "Two",
        3,
      ],
      "integer" => 4,
      "float" => 1.4,
    ];
    return [$value, $value];
  }

  /**
   * Type transformations.
   */
  protected static function typeTransformation() {
    $value = [
      "true_boolean" => TRUE,
      "false_boolean" => FALSE,
      "null_boolean" => NULL,
      "markup" => "Hello <b>World</b>",
      "associative_array" => [
        "Un" => "One",
        "Deux" => "Two",
        "Trois" => 3,
      ],
      "nested_array" => [
        "One",
        [
          "Two",
          "Three",
        ],
        [
          "deep" => [
            "very deep" => ["foo", "bar"],
          ],
        ],
      ],
    ];
    $expected = [
      "true_boolean" => "1",
      "false_boolean" => "",
      "null_boolean" => "",
      "markup" => "Hello World",
      "associative_array" => [
        "One",
        "Two",
        3,
      ],
      "nested_array" => [
        "One",
        // JSON encoding because we don't know how deep is the nesting.
        '["Two","Three"]',
        '{"deep":{"very deep":["foo","bar"]}}',
      ],
    ];
    return [$value, $expected];
  }

  /**
   * List array.
   */
  protected static function listArray() {
    $value = [
      "One",
      "Two",
      3,
    ];
    // This doesn't look like a valid HTML attribute structure, but we rely on
    // Drupal Attribute object normalization here.
    $expected = [
      "0" => "One",
      "1" => "Two",
      "2" => 3,
    ];
    return [$value, $expected];
  }

}
