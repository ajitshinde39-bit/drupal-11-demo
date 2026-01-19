<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;

/**
 * Base class for prop_type_adapter plugins.
 */
abstract class PropTypeAdapterPluginBase extends PluginBase implements PropTypeAdapterInterface {

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return ($this->pluginDefinition instanceof PluginDefinitionInterface) ? $this->pluginDefinition->id() : (string) ($this->pluginDefinition["label"] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getPropTypeId(): string {
    return ($this->pluginDefinition instanceof PluginDefinitionInterface) ? '' : ($this->pluginDefinition['prop_type'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getSchema(): array {
    return ($this->pluginDefinition instanceof PluginDefinitionInterface) ? [] : (array) ($this->pluginDefinition['schema'] ?? []);
  }

  /**
   * {@inheritdoc}
   */
  public function transform(mixed $data): mixed {
    return $data;
  }

}
