<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Provides an interface for plugins which have JSON schema.
 */
interface WithJsonSchemaInterface {

  /**
   * Returns the JSON schema.
   *
   * @return array
   *   The JSON schema.
   */
  public function getSchema(): array;

}
