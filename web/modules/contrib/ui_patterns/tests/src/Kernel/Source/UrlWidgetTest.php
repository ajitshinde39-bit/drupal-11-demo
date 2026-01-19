<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test UrlWidget.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\UrlWidget
 * @group ui_patterns
 */
class UrlWidgetTest extends SourcePluginsTestBase {

  /**
   * Test UrlWidget Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('url_');
  }

}
