<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_field\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test UIPatternsSourceFieldPropertySource.
 *
 * @coversDefaultClass \Drupal\ui_patterns_field\Plugin\UiPatterns\Source\UIPatternsSourceFieldPropertySource
 * @group ui_patterns_field
 */
class UIPatternsSourceFieldPropertySourceTest extends SourcePluginsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns_field'];

  /**
   * Test Field Property Plugin.
   */
  public function testPlugin(): void {
    $testData = self::loadTestDataFixture(__DIR__ . "/../../../fixtures/tests.ui_patterns_source.yml");
    $testSets = $testData->getTestSets();
    foreach ($testSets as $test_set_name => $test_set) {
      if (!str_starts_with($test_set_name, 'ui_patterns_source_')) {
        continue;
      }
      $this->runSourcePluginTest($test_set);
    }
  }

}
