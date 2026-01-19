<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for source plugins.
 */
interface DerivableContextInterface extends ConfigurableInterface, PluginInspectionInterface, DependentPluginInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Returns the derived context.
   *
   * @return array< array<string, \Drupal\Core\Plugin\Context\ContextInterface> >
   *   An array of derived contexts.
   */
  public function getDerivedContexts(): array;

}
