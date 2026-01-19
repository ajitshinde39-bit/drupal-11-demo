<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test WysiwygWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\WysiwygWidget
 * @group ui_patterns
 */
class WysiwygWidgetTest extends SourcePluginsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['editor', 'filter', 'text'];

  /**
   * Test WysiwygWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('wysiwyg_');
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../../fixtures/config/filter.format.html.yml');
    $this->importConfigFixture('filter.format.html', $config_import);
    $testData = self::loadTestDataFixture(__DIR__ . "/../../../fixtures/wysiwyg_tests.yml");
    $testSet = $testData->getTestSet("wysiwyg_html");
    $this->runSourcePluginTest($testSet);
  }

}
