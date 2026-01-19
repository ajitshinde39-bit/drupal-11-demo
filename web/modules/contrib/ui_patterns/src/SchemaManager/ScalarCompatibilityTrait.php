<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\SchemaManager;

/**
 * JSON Schema compatibility checks for scalars.
 *
 * Moved in a trait to keep CompatibilityChecker lighter.
 */
trait ScalarCompatibilityTrait {

  /**
   * Check if different strings are compatible.
   */
  protected function isStringCompatible(array $checked_schema, array $reference_schema): bool {
    // FALSE if at least one of those tests is FALSE.
    if (array_key_exists("format", $reference_schema) && !$this->isStringFormatCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    if (array_key_exists("enum", $reference_schema) && !$this->isEnumCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    if (!$this->isStringLengthCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    if (array_key_exists("pattern", $reference_schema) && !$this->isStringPatternCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * See: json-schema.org/understanding-json-schema/reference/string#regexp.
   */
  protected function isStringPatternCompatible(array $checked_schema, array $reference_schema): bool {
    if (!array_key_exists("pattern", $checked_schema)) {
      return FALSE;
    }
    // It would be better to check if checked schema pattern is a sub pattern
    // of reference schema. But not done yet.
    return ($checked_schema["pattern"] === $reference_schema["pattern"]);
  }

  /**
   * See json-schema.org/understanding-json-schema/reference/string#format.
   */
  protected function isStringFormatCompatible(array $checked_schema, array $reference_schema): bool {
    if (!array_key_exists("format", $checked_schema)) {
      return FALSE;
    }
    $checked_format = $checked_schema["format"];
    $reference_format = $reference_schema["format"];
    if ($checked_format == $reference_format) {
      return TRUE;
    }
    // Ex: an uri is also a valid uri-reference
    // Ex: an uri-reference is also a valid iri-reference.
    $compatibility_map = [
      "uri" => [
        "uri-reference",
        "iri-reference",
        "iri",
      ],
      "iri" => [
        "iri-reference",
      ],
      "uri-reference" => [
        "iri-reference",
      ],
      "email" => [
        "idn-email",
      ],
    ];
    if (array_key_exists($checked_format, $compatibility_map)) {
      return in_array($reference_format, $compatibility_map[$checked_format]);
    }
    return FALSE;
  }

  /**
   * Is string length compatible?
   */
  protected function isStringLengthCompatible(array $checked_schema, array $reference_schema): bool {
    // FALSE if at least one of those tests is FALSE.
    if (array_key_exists("minLength", $reference_schema) && !$this->isMinLengthCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    if (array_key_exists("maxLength", $reference_schema) && !$this->isMaxLengthCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Is minLength property compatible?
   */
  protected function isMinLengthCompatible(array $checked_schema, array $reference_schema): bool {
    if (!array_key_exists("minLength", $checked_schema)) {
      return FALSE;
    }
    return ($checked_schema["minLength"] >= $reference_schema["minLength"]);
  }

  /**
   * Is maxLength property compatible?
   */
  protected function isMaxLengthCompatible(array $checked_schema, array $reference_schema): bool {
    if (!array_key_exists("maxLength", $checked_schema)) {
      return FALSE;
    }
    return ($checked_schema["maxLength"] <= $reference_schema["maxLength"]);
  }

  /**
   * Check if different numbers are compatible.
   */
  protected function isNumberCompatible(array $checked_schema, array $reference_schema): bool {
    if ($reference_schema["type"] === "integer") {
      // Integers are always numbers, but numbers are not always integer.
      return FALSE;
    }
    return $this->isNumericCompatible($checked_schema, $reference_schema);
  }

  /**
   * Check if different integers are compatible.
   */
  protected function isIntegerCompatible(array $checked_schema, array $reference_schema): bool {
    return $this->isNumericCompatible($checked_schema, $reference_schema);
  }

  /**
   * Rules shared by numbers and integers.
   */
  protected function isNumericCompatible(array $checked_schema, array $reference_schema): bool {
    // FALSE if at least one of those tests is FALSE.
    if (array_key_exists("enum", $reference_schema) && !$this->isEnumCompatible($checked_schema, $reference_schema)) {
      return FALSE;
    }
    // Multiple and range are not managed yet.
    return TRUE;
  }

}
