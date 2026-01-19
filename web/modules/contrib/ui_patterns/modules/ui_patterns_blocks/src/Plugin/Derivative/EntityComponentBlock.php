<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_blocks\Plugin\Derivative;

use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides block plugin definitions for components, with an entity context.
 */
class EntityComponentBlock extends ComponentBlock {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    parent::getDerivativeDefinitions($base_plugin_definition);
    foreach ($this->derivatives as &$definition) {
      $definition['_block_ui_hidden'] = TRUE;
      if (!array_key_exists("context_definitions", $definition) ||
        !is_array($definition['context_definitions'])) {
        $definition['context_definitions'] = [];
      }
      $entity_context_def = new ContextDefinition("entity");
      $entity_context_def->setRequired(TRUE);
      $definition['context_definitions']["entity"] = $entity_context_def;
    }
    unset($definition);
    return $this->derivatives;
  }

}
