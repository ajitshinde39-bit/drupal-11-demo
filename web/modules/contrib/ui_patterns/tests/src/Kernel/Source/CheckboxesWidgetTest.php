<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test CheckboxesWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\CheckboxesWidget
 * @group ui_patterns
 */
class CheckboxesWidgetTest extends SourcePluginsTestBase {

  /**
   * Test CheckboxesWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('checkboxes_');
  }

}
