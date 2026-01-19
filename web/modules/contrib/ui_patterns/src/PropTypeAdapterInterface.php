<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for prop_type_adapter plugins.
 */
interface PropTypeAdapterInterface extends WithJsonSchemaInterface, PluginInspectionInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Get prop type ID.
   */
  public function getPropTypeId(): string;

  /**
   * Transform source data.
   */
  public function transform(mixed $data): mixed;

}
