<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test ListTextareaWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\ListTextareaWidget
 * @group ui_patterns
 */
class ListTextareaWidgetTest extends SourcePluginsTestBase {

  /**
   * Test ListTextareaWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('list_textarea_');
  }

}
