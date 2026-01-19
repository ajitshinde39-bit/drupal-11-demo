<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a component display entity type.
 */
interface ComponentFormDisplayInterface extends ConfigEntityInterface {

  /**
   * Gets the highest weight of the display options in the display.
   *
   * @return int|null
   *   The highest weight of the display options in the display, or NULL if the
   *   display is empty.
   */
  public function getHighestWeight(): int|null;

  /**
   * Gets the display options for all components.
   *
   * @return array
   *   The array of display options, keyed by component name.
   */
  public function getPropSlotOptions(): array;

  /**
   * Gets the display options set for a component.
   *
   * @param string $name
   *   The name of the component.
   *
   * @return array|null
   *   The display options for the component, or NULL if the component is not
   *   displayed.
   */
  public function getPropSlotOption(string $name);

  /**
   * Sets the display options for a prop or slot.
   *
   * @param string $name
   *   The name of the prop or slot.
   * @param array $options
   *   The display options.
   *
   * @return $this
   */
  public function setPropSlotOptions(string $name, array $options = []);

  /**
   * Sets a prop or slot to be hidden.
   *
   * @param string $name
   *   The name of the prop or slot.
   *
   * @return $this
   */
  public function removePropSlotOption(string $name);

  /**
   * Returns the form mode name.
   *
   * @return ?string
   *   The form mode name.
   */
  public function getFormModeName(): ?string;

  /**
   * Returns the sdc component id.
   *
   * @return string
   *   The sdc component id.
   */
  public function getComponentId():string;

}
