<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test BlockSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\BlockSource
 * @group ui_patterns
 */
class BlockSourceTest extends SourcePluginsTestBase {

  /**
   * Test BlockSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('block_');
    $this->runSourcePluginTests('block_', __DIR__ . "/../../../fixtures/block_tests.yml");
  }

}
