<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\SchemaManager;

/**
 * Checks whether two schemas are compatible.
 *
 * Used for prop typing.
 *
 * Not the same as Drupal\Core\Theme\Component\SchemaCompatibilityChecker which
 * has different rules and a different goal: validating replace mechanism.
 */
class CompatibilityChecker {

  use ScalarCompatibilityTrait;

  /**
   * Canonicalizer.
   *
   * @var \Drupal\ui_patterns\SchemaManager\Canonicalizer
   */
  protected $canonicalizer;

  /**
   * Constructs a CompatibilityChecker.
   */
  public function __construct(Canonicalizer $canonicalizer) {
    $this->canonicalizer = $canonicalizer;
  }

  /**
   * Checks if the second schema is compatible with the first one.
   *
   * @param array $checked_schema
   *   The schema that should be compatible with the other one.
   * @param array $reference_schema
   *   The schema to check compatibility against.
   *
   * @return bool
   *   TRUE if compatible.
   */
  public function isCompatible(array $checked_schema, array $reference_schema): bool {
    if (empty($reference_schema)) {
      // To avoid catching special prop types like "slot" or "unknown".
      return FALSE;
    }
    $checked_schema = $this->canonicalizer->canonicalize($checked_schema);
    $reference_schema = $this->canonicalizer->canonicalize($reference_schema);
    if ($this->isSame($checked_schema, $reference_schema)) {
      return TRUE;
    }
    if (isset($checked_schema["type"]) && isset($reference_schema["type"])) {
      return $this->isTypeCompatible($checked_schema, $reference_schema);
    }
    if (isset($checked_schema["anyOf"]) || isset($reference_schema["anyOf"])) {
      return $this->isAnyOfCompatible($checked_schema, $reference_schema);
    }
    return FALSE;
  }

  /**
   * Are schemas the same?
   */
  protected function isSame(array $checked_schema, array $reference_schema): bool {
    return (serialize($checked_schema) === serialize($reference_schema));
  }

  /**
   * When the schemas are using type property, are they compatible?
   */
  protected function isTypeCompatible(array $checked_schema, array $reference_schema): bool {
    if (is_array($checked_schema["type"]) || is_array($reference_schema["type"])) {
      // Because of self::resolveMultipleTypes() we are not supposed to meet
      // this situation.
      return FALSE;
    }
    if ($checked_schema["type"] !== $reference_schema["type"]) {
      // Integers are numbers, but numbers are not always integer.
      if (!($checked_schema["type"] === "integer" && $reference_schema["type"] === "number")) {
        return FALSE;
      }
    }
    // Now we know $checked_schema and $reference_schema have the same type.
    // So, testing $checked_schema type is enough.
    return match ($checked_schema["type"]) {
      'null' => TRUE,
      'boolean' => TRUE,
      'object' => $this->isObjectCompatible($checked_schema, $reference_schema),
      'array' => $this->isArrayCompatible($checked_schema, $reference_schema),
      'number' => $this->isNumberCompatible($checked_schema, $reference_schema),
      'integer' => $this->isIntegerCompatible($checked_schema, $reference_schema),
      'string' => $this->isStringCompatible($checked_schema, $reference_schema),
      default => FALSE,
    };
  }

  /**
   * When the schemas are using anyOf property, are they compatible?
   */
  protected function isAnyOfCompatible(array $checked_schema, array $reference_schema): bool {
    if (isset($reference_schema["anyOf"])) {
      foreach ($reference_schema["anyOf"] as $schema) {
        if ($this->isCompatible($checked_schema, $schema)) {
          return TRUE;
        }
      }
    }
    if (isset($checked_schema["anyOf"])) {
      foreach ($checked_schema["anyOf"] as $schema) {
        if ($this->isCompatible($schema, $reference_schema)) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Is the object compatible?
   */
  protected function isObjectCompatible(array $checked_schema, array $reference_schema): bool {
    // FALSE if at least one of those tests is FALSE.
    if (!isset($checked_schema["properties"]) && isset($reference_schema["properties"])) {
      return FALSE;
    }
    if (!isset($checked_schema["patternProperties"]) && isset($reference_schema["patternProperties"])) {
      return FALSE;
    }
    // Properties and patternProperties are not managed yet.
    if (isset($reference_schema["required"]) && !$this->isRequiredCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Check if required properties are compatible.
   */
  protected function isRequiredCompatible(array $checked_schema, array $reference_schema): bool {
    $required = $reference_schema["required"];
    if (array_key_exists("required", $checked_schema)) {
      $properties = $checked_schema["required"];
      return empty(array_diff($required, $properties));
    }
    if (!array_key_exists("properties", $checked_schema)) {
      return TRUE;
    }
    $properties = array_keys($checked_schema["properties"]);
    return empty(array_diff($required, $properties));
  }

  /**
   * Check if different arrays are compatible.
   */
  protected function isArrayCompatible(array $checked_schema, array $reference_schema): bool {
    // FALSE if at least one of those tests is FALSE.
    if (!isset($checked_schema["items"]) && isset($reference_schema["items"])) {
      return FALSE;
    }
    if (($reference_schema["uniqueItems"] ?? FALSE) && (!isset($checked_schema["uniqueItems"]) || !$checked_schema["uniqueItems"])) {
      return FALSE;
    }
    // https://json-schema.org/understanding-json-schema/reference/array#items
    if (isset($checked_schema["items"]) && isset($reference_schema["items"])) {
      if (!$this->isCompatible($checked_schema["items"], $reference_schema["items"])) {
        return FALSE;
      }
    }
    // minItems, maxItems, contains, mincontains, maxcontains and length are
    // not managed yet.
    return TRUE;
  }

  /**
   * Is enum property compatible?
   */
  protected function isEnumCompatible(array $checked_schema, array $reference_schema): bool {
    if (!array_key_exists("enum", $checked_schema)) {
      return FALSE;
    }
    if (empty($reference_schema["enum"])) {
      return TRUE;
    }
    if (count($checked_schema["enum"]) === count($reference_schema["enum"])) {
      $diff = array_diff($checked_schema["enum"], $reference_schema["enum"]);
      return ($diff === []);
    }
    if (count($checked_schema["enum"]) > count($reference_schema["enum"])) {
      return FALSE;
    }
    if (count($checked_schema["enum"]) < count($reference_schema["enum"])) {
      $diff = array_diff($reference_schema["enum"], $checked_schema["enum"]);
      return (count($diff) > 0);
    }
    return FALSE;
  }

}
