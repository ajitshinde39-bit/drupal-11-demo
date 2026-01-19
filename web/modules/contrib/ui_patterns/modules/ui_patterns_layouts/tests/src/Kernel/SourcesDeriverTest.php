<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_layouts\Kernel;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;
use Drupal\ui_patterns_layouts\Plugin\Layout\ComponentLayout;

/**
 * Tests UI patterns layouts plugin deriver.
 *
 * @group ui_patterns_layouts
 */
class SourcesDeriverTest extends SourcePluginsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_discovery', 'ui_patterns', 'ui_patterns_test', 'ui_patterns_layouts'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutManager;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected $componentManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->layoutManager = $this->container->get('plugin.manager.core.layout');
    $this->componentManager = $this->container->get('plugin.manager.sdc');
  }

  /**
   * Tests creating fields of all types on a content type.
   */
  public function testDerivedPluginPerComponent() {
    /* @phpstan-ignore method.notFound */
    $components = $this->componentManager->getNegotiatedSortedDefinitions();
    foreach ($components as $component) {
      $component_instance = $this->componentManager->find($component['id']);
      $id = str_replace('-', '_', (string) $component['id']);
      $layout_plugin_id = sprintf('ui_patterns:%s', $id);
      $layout = $this->layoutManager->createInstance($layout_plugin_id);
      $this->assertNotNull($layout, "Layout for component {$component['id']} is missing");
      $this->assertInstanceOf(ComponentLayout::class, $layout);
      /** @var \Drupal\Core\Layout\LayoutDefinition $layout_definition */
      $layout_definition = $layout->getPluginDefinition();
      $regions = array_keys($layout_definition->getRegions());
      $slots = $component_instance->metadata->slots ?? [];
      foreach (array_keys($slots) as $slot_id) {
        $this->assertContains($slot_id, $regions, "Slot {$slot_id} is missing in layout for component {$component['id']}");
      }
    }
  }

}
