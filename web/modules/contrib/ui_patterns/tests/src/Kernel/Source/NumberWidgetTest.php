<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test NumberWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\NumberWidget
 * @group ui_patterns
 */
class NumberWidgetTest extends SourcePluginsTestBase {

  /**
   * Test NumberWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('number_');
  }

}
