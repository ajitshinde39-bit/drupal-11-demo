<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test EntityFieldSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\EntityFieldSource
 * @group ui_patterns
 */
class EntityFieldSourceTest extends SourcePluginsTestBase {

  /**
   * Test EntityFieldSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('entity_field_');
  }

}
