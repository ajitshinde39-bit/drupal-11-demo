<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_blocks\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns_blocks\Plugin\Derivative\EntityComponentBlock as DerivativeEntityComponentBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a component block.
 */
#[Block(
  id: "ui_patterns_entity",
  admin_label: new TranslatableMarkup("Component (UI Patterns)"),
  category: new TranslatableMarkup("UI Patterns"),
  deriver: DerivativeEntityComponentBlock::class
)]
class EntityComponentBlock extends ComponentBlock {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    // For Layout builder.
    $plugin->setContextMapping(["entity" => "layout_builder.entity"]);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  protected function addContextAssignmentElement(ContextAwarePluginInterface $plugin, array $contexts) {
    return $this->componentsAdjustContextEntitySelection(parent::addContextAssignmentElement($plugin, $contexts), "layout_builder.entity");
  }

}
