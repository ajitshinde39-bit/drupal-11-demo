<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test CheckboxWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\CheckboxWidget
 * @group ui_patterns
 */
class CheckboxWidgetTest extends SourcePluginsTestBase {

  /**
   * Test CheckboxWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('checkbox_');
  }

}
