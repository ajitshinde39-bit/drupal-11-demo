<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test TextfieldWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\TextfieldWidget
 * @group ui_patterns
 */
class TextfieldWidgetTest extends SourcePluginsTestBase {

  /**
   * Test TextfieldWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('textfield_');
  }

}
