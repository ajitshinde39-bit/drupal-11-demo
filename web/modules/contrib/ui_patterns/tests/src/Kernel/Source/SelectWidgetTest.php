<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test SelectWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\SelectWidget
 * @group ui_patterns
 */
class SelectWidgetTest extends SourcePluginsTestBase {

  /**
   * Test SelectWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('select_');
  }

}
