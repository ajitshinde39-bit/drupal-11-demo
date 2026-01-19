<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_blocks\Kernel;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;
use Drupal\ui_patterns_blocks\Plugin\Block\ComponentBlock;
use Drupal\ui_patterns_blocks\Plugin\Block\EntityComponentBlock;

/**
 * Tests UI patterns block plugin deriver.
 *
 * @group ui_patterns_blocks
 */
class SourcesDeriverTest extends SourcePluginsTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns', 'ui_patterns_test', 'ui_patterns_blocks'];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

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

    $this->blockManager = $this->container->get('plugin.manager.block');
    $this->componentManager = $this->container->get('plugin.manager.sdc');
  }

  /**
   * Tests creating fields of all types on a content type.
   */
  public function testDerivedPluginPerComponent() {
    /* @phpstan-ignore method.notFound */
    $components = $this->componentManager->getNegotiatedSortedDefinitions();
    foreach ($components as $component) {
      $id = (string) $component['id'];
      $block_plugin_id = sprintf('ui_patterns:%s', $id);
      $block = $this->blockManager->createInstance($block_plugin_id);
      $this->assertNotNull($block, "Block for component {$component['id']} is missing");
      $this->assertInstanceOf(ComponentBlock::class, $block,
        get_class($block) . " " . $component['id'] . " " . print_r($this->blockManager->getDefinitions(), TRUE));
      $block_plugin_id = sprintf('ui_patterns_entity:%s', $id);
      $block = $this->blockManager->createInstance($block_plugin_id);
      $this->assertNotNull($block, "Block with entity context for component {$component['id']} is missing");
      $this->assertInstanceOf(EntityComponentBlock::class, $block);
      $plugin_definition = $block->getPluginDefinition() ?? [];
      if (!is_array($plugin_definition)) {
        $plugin_definition = [];
      }
      $context_definitions = $plugin_definition['context_definitions'] ?? [];
      $this->assertArrayHasKey('entity', $context_definitions);
    }
  }

}
