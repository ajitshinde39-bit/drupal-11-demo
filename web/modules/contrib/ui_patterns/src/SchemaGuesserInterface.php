<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Provides an interface for managers that support plugins with JSON schema.
 */
interface SchemaGuesserInterface {

  /**
   * Guess prop type from a JSON schema.
   *
   * @param array $prop_schema
   *   A JSON schema.
   *
   * @return ?WithJsonSchemaInterface
   *   A JSON schema.
   */
  public function guessFromSchema(array $prop_schema): ?WithJsonSchemaInterface;

}
