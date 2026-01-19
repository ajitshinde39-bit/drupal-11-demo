<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;

/**
 * Test TokenSource.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\TokenSource
 * @group ui_patterns
 */
class TokenSourceTest extends SourcePluginsTestBase {

  /**
   * Test TokenSource Plugin.
   */
  public function testPlugin(): void {
    $this->runSourcePluginTests('token_');
  }

}
