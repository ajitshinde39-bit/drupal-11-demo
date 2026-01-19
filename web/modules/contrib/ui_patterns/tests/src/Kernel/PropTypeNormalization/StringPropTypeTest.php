<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\StringPropType;

/**
 * Test StringPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\StringPropType
 * @group ui_patterns
 */
class StringPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = StringPropType::normalize($value, $this->testComponentProps['string']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('string', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Test rendered component with prop and contentMediaType.
   *
   * @dataProvider renderingTestsStringPlain
   */
  public function testRenderingStringPlain(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('string_plain', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, ""],
      "string" => ["my string", "my string"],
      "string empty" => ["", ""],
      "int" => [2, "2"],
      "render array" => [["#markup" => "my string"], "my string"],
      "string with markup" => [Markup::create("my string"), "my string"],
      "string with url" => [Url::fromUri("https://drupal.org"), "https://drupal.org"],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-string"></div>',
      ],
      "string with link" => [
        Link::fromTextAndUrl(Markup::create("test"), Url::fromUri("https://drupal.org")),
        '<div class="ui-patterns-props-string"><a href="https://drupal.org">test</a></div>',
      ],
      "html string" => [
        '<form><input type="checkbox" /></form><b>test</b>',
        '<div class="ui-patterns-props-string"><form><input type="checkbox" /></form><b>test</b></div>',
      ],
      "html markup object" => [
        Markup::create('<form><input type="checkbox" /></form><b>test</b>'),
        '<div class="ui-patterns-props-string"><form><input type="checkbox" /></form><b>test</b></div>',
      ],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTestsStringPlain() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-string_plain"></div>',
      ],
      "string with link" => [
        Link::fromTextAndUrl(Markup::create("test"), Url::fromUri("https://drupal.org")),
        '<div class="ui-patterns-props-string_plain">test</div>',
      ],
      "html string" => [
        "<b>test</b>",
        '<div class="ui-patterns-props-string_plain">test</div>',
      ],
      "html markup object" => [
        Markup::create("<b>test</b>"),
        '<div class="ui-patterns-props-string_plain">test</div>',
      ],
    ];
  }

}
