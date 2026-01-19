<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_field_formatters\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test FieldFormatterSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\FieldFormatterSource
 * @group ui_patterns_field_formatters
 */
class FieldFormatterSourceTest extends SourcePluginsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns_field_formatters'];

  /**
   * Test Field Property Plugin.
   */
  public function testPlugin(): void {
    $testData = self::loadTestDataFixture(__DIR__ . "/../../../fixtures/tests.formatter_per_item.yml");
    $testSets = $testData->getTestSets();
    foreach ($testSets as $test_set_name => $test_set) {
      if (!str_starts_with($test_set_name, 'field_formatter_')) {
        continue;
      }
      $this->runSourcePluginTest($test_set);
    }
  }

}
