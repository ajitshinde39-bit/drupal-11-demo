<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test PathSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\PathSource
 * @group ui_patterns
 */
class PathSourceTest extends SourcePluginsTestBase {

  /**
   * Test PathSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('path_');
  }

}
