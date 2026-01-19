<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test ComponentSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\ComponentSource
 * @group ui_patterns
 */
class ComponentSourceTest extends SourcePluginsTestBase {

  /**
   * Test ComponentSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('component_');
  }

}
