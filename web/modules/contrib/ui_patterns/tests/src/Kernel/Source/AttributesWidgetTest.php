<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test AttributesWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\AttributesWidget
 * @group ui_patterns
 */
class AttributesWidgetTest extends SourcePluginsTestBase {

  /**
   * Test AttributesWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('attributes_');
    $this->runSourcePluginTests('attributes_', __DIR__ . "/../../../fixtures/source_only_tests.yml");
  }

}
