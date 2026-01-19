<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Default class used for stories plugins.
 */
class StoryDefault extends PluginBase implements StoryInterface {

  /**
   * {@inheritdoc}
   */
  public function name(): string {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      return $def->id();
    }
    return (string) ($def['name'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function machineName(): string {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      [,, $story_id] = explode(':', $def->id());
      return $story_id;
    }
    return (string) ($def['machineName'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function description(): string {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      return '';
    }
    return (string) ($def['description'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function component(): string {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      [$provider, $component_id] = explode(':', $def->id());
      return $provider . ':' . $component_id;
    }
    return (string) ($def['component'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function slots(): array {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      return [];
    }
    return $def['slots'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function props(): array {
    $def = $this->pluginDefinition;
    if ($def instanceof PluginDefinitionInterface) {
      return [];
    }
    return $def['props'] ?? [];
  }

}
