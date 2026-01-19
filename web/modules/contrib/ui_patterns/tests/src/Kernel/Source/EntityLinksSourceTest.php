<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test EntityLinksSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\EntityLinksSource
 * @group ui_patterns
 */
class EntityLinksSourceTest extends SourcePluginsTestBase {

  /**
   * Test EntityLinksSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('entity_links_');
  }

}
