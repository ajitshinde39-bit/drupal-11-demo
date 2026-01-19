<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test FieldPropertySource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\FieldPropertySource
 * @group ui_patterns
 */
class FieldPropertySourceTest extends SourcePluginsTestBase {

  /**
   * Test FieldPropertySource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('field_property_');
  }

}
