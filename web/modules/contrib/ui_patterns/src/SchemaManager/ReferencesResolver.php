<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\SchemaManager;

use JsonSchema\Constraints\BaseConstraint;
use JsonSchema\Exception\RuntimeException;
use JsonSchema\SchemaStorage;
use Psr\Log\LoggerInterface;

/**
 * JSON Schema References resolver.
 *
 * Because SchemaStorage::resolveRefSchema() is not recursively resolving the
 * referenced schemas.
 * See: https://github.com/justinrainbow/json-schema/issues/427
 */
class ReferencesResolver {

  const MAXIMUM_RECURSIVITY_LEVEL = 10;

  /**
   * Constructs a ComponentElementBuilder.
   */
  public function __construct(
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Resolve schema references recursively.
   */
  public function resolve(array $schema, int $depth = 0): array {
    if ($depth > self::MAXIMUM_RECURSIVITY_LEVEL) {
      return $schema;
    }

    $depth++;
    $storage = new SchemaStorage();

    try {
      $schema = BaseConstraint::arrayToObjectRecursive($schema);
      $refSchema = (array) $storage->resolveRefSchema($schema);
      $schema = (array) $schema;

      unset($schema['$ref']);

      // Merge referenced schema into the current schema.
      $schema += $refSchema;

      if (isset($schema['id'])) {
        // Prop types like enum_list has an underscore.
        // This leads to an "Invalid URL format" exception.
        $schema['id'] = str_replace('_', '-', $schema['id']);
      }
    }
    catch (RuntimeException $e) {
      $schema = (array) $schema;
      $this->logger->error(t(
        "Could not resolve schema referenced by \$ref property '@ref': @error",
        [
          '@ref' => $schema['$ref'] ?? '',
          '@error' => $e->getMessage(),
        ]
      ));
    }

    // Recursively resolve nested objects.
    foreach ($schema as $key => $value) {
      if (is_object($value)) {
        $schema[$key] = $this->resolve((array) $value, $depth);
      }
    }

    return $schema;
  }

}
