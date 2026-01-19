<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;

/**
 * Interface for source plugins.
 */
interface SourceInterface extends ConfigurableInterface, PluginInspectionInterface, PluginSettingsInterface, ContextAwarePluginInterface, DependentPluginInterface {

  /**
   * Returns the translated plugin label.
   *
   * @param bool $with_context
   *   Whether to include context information in the label.
   *
   * @return string
   *   The translated title.
   */
  public function label(bool $with_context = FALSE): string;

  /**
   * Retrieve and process the prop value.
   */
  public function getPropValue(): mixed;

  /**
   * Retrieve the value, eventually converted for prop type.
   *
   * @param \Drupal\ui_patterns\PropTypeInterface|null $prop_type
   *   The expected prop type of the value or NULL to get default value.
   *
   * @return mixed
   *   The converted value.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getValue(?PropTypeInterface $prop_type = NULL): mixed;

  /**
   * Returns the associated prop id.
   */
  public function getPropId(): string;

  /**
   * Returns the associated prop definition.
   */
  public function getPropDefinition(): mixed;

  /**
   * Allow sources to alter the component render element.
   */
  public function alterComponent(array $element): array;

  /**
   * Get metadata stored in the plugin definition.
   *
   * @param string $key
   *   The key name of plugin definition to get data.
   *
   * @return null|mixed
   *   The data inside plugin definition or false if error.
   */
  public function getCustomPluginMetadata(string $key): mixed;

}
