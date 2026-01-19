<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Core\Url;
use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\UrlPropType;

/**
 * Test UrlPropType normalization.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\PropType\UrlPropType
 * @group ui_patterns
 */
class UrlPropTypeTest extends PropTypeNormalizationTestBase {

  /**
   * Test normalize static method.
   *
   * @dataProvider normalizationTests
   */
  public function testNormalization(mixed $value, mixed $expected) : void {
    $normalized = UrlPropType::normalize($value, $this->testComponentProps['url']);
    $this->assertEquals($normalized, $expected);
  }

  /**
   * Test normalize static method manually.
   *
   * We need the container to be initialized to use the Url::fromUri method.
   * So it's not possible to use a dataProvider for this test.
   */
  public function testNormalizationManualData() : void {
    $tests = [
      "uri" => [Url::fromUri("https://drupal.org"), "https://drupal.org"],
      "uri internal" => [Url::fromUri("internal:/user"), "/user"],
      "uri internal front" => [Url::fromUri("internal:/"), "/"],
    ];
    foreach ($tests as $test) {
      $value = $test[0];
      $expected = $test[1];
      $normalized = UrlPropType::normalize($value, $this->testComponentProps['url']);
      $this->assertEquals($normalized, $expected);
    }
  }

  /**
   * Test rendered component with prop.
   *
   * @dataProvider renderingTests
   */
  public function testRendering(mixed $value, mixed $rendered_value) : void {
    $this->runRenderPropTest('url', ["value" => $value, "rendered_value" => $rendered_value]);
  }

  /**
   * Provides data for testNormalization.
   */
  public static function normalizationTests() : array {
    return [
      "null value" => [NULL, ""],
      "string" => ["https://drupal.org", "https://drupal.org"],
      "string empty" => ["", ""],
      "uri internal classic" => ["/user", "/user"],
      "uri from shortcut" => ["<front>", "/"],
    ] + self::notAnUrl() + self::validUrl();
  }

  /**
   * Provides data for testNormalization.
   */
  public static function renderingTests() : array {
    return [
      "null value" => [
        NULL,
        '<div class="ui-patterns-props-url"></div>',
      ],
    ];
  }

  /**
   * Not an URL.
   */
  protected static function notAnUrl() {
    return [
      "Empty string" => [
        "",
        "",
      ],
      "Boolean" => [
        TRUE,
        "",
      ],
      "Integer" => [
        3,
        "",
      ],
      "Array" => [
      [],
        "",
      ],
    ];
  }

  /**
   * Valid URL.
   */
  protected static function validUrl() {
    return [
      "HTTP URL (domain only)" => [
        "http://www.foo.com",
        "http://www.foo.com",
      ],
      "HTTP URL" => [
        "http://www.foo.com/path/to",
        "http://www.foo.com/path/to",
      ],
      "HTTPS URL" => [
        "https://www.foo.com/path/to",
        "https://www.foo.com/path/to",
      ],
      "HTTP(S) URL" => [
        "//www.foo.com/path/to",
        "//www.foo.com/path/to",
      ],
      "SFTP URL" => [
        "sftp://www.foo.com/path/to",
        "sftp://www.foo.com/path/to",
      ],
      "Full path" => [
        "/path/to",
        "/path/to",
      ],
      "Relative path" => [
        "path/to",
        "path/to",
      ],
      "HTTPS IRI" => [
        "https://en.tranché.org/bien-sûr",
        "https://en.tranché.org/bien-sûr",
      ],
      "HTTPS IRI percent encoded" => [
        "https://en.wiktionary.org/wiki/%E1%BF%AC%CF%8C%CE%B4%CE%BF%CF%82",
        "https://en.wiktionary.org/wiki/%E1%BF%AC%CF%8C%CE%B4%CE%BF%CF%82",
      ],
    ];
  }

}
