<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\IdentifierPropType;
use Twig\Error\RuntimeError;

/**
 * Test IdentifierPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\IdentifierPropType
 * @group ui_patterns
 */
class IdentifierPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = IdentifierPropType::normalize($value, $this->testComponentProps['identifier']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value, ?string $exception_class = NULL) : void {
    $this->runRenderPropTest('identifier',
      ["value" => $value, "rendered_value" => $rendered_value, "exception_class" => $exception_class]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, NULL],
      "markup" => [['#markup' => "abc"], "abc"],
      "string" => ["abc", "abc"],
      "string with markup" => ["<b>abc</b>", "abc"],
      "string with square brackets" => ["a[v][eee]", "a-v--eee-"],
    ];
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-identifier"></div>',
        RuntimeError::class,
      ],
      "empty value" => [
        "",
        '<div class="ui-patterns-props-identifier"></div>',
        RuntimeError::class,
      ],
      "invalid value" => [
        "2",
        '<div class="ui-patterns-props-identifier"></div>',
        RuntimeError::class,
      ],
      "correct value" => [
        "correct-value🔥",
        '<div class="ui-patterns-props-identifier">correct-value🔥</div>',
      ],
      "corrected value" => [
        "value with space and /",
        '<div class="ui-patterns-props-identifier">value-with-space-and--</div>',
      ],
    ];
  }

}
