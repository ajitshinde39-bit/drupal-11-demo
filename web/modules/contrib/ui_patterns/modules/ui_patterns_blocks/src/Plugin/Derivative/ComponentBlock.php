<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_blocks\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\ui_patterns\ComponentPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides block plugin definitions for components.
 *
 * @see \Drupal\ui_patterns\Plugin\Block\ComponentBlock
 */
class ComponentBlock extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The component plugin manager.
   *
   * @var \Drupal\ui_patterns\ComponentPluginManager
   */
  protected $pluginManager;

  /**
   * Constructs new ComponentBlock.
   *
   * @param \Drupal\ui_patterns\ComponentPluginManager $plugin_manager
   *   The component plugin manager.
   */
  public function __construct(ComponentPluginManager $plugin_manager) {
    $this->pluginManager = $plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('plugin.manager.sdc')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->pluginManager;
    /** @var array<string, array> $components */
    $components = $manager->getNegotiatedSortedDefinitions(NULL, 'label', TRUE);
    foreach ($components as $component_id => $component) {
      $this->derivatives[$component_id] = $base_plugin_definition;
      $this->derivatives[$component_id]['admin_label'] = $component['annotated_name'] ?? $component['name'] ?? $component['id'];
      $this->derivatives[$component_id]['_block_ui_hidden'] = FALSE;
      $this->derivatives[$component_id]['provider'] = 'ui_patterns_blocks';
    }

    return $this->derivatives;
  }

}
