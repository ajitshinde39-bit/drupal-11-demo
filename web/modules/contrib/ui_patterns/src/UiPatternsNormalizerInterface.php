<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Provides an interface for managers that support plugins with JSON schema.
 */
interface UiPatternsNormalizerInterface {

  /**
   * Try to downcast an ufo to a scalar value.
   *
   * @param mixed $value
   *   The value to convert.
   * @param bool $strip_tags_from_render_arrays
   *   Whether to strip tags from render arrays.
   */
  public function convertToScalar(mixed &$value, bool $strip_tags_from_render_arrays = TRUE) : void;

  /**
   * Convert a value to a string.
   *
   * @param mixed $value
   *   The value to convert.
   *
   * @return string
   *   The converted value.
   */
  public function convertToString(mixed $value) : string;

  /**
   * Normalize enum values.
   *
   * @param mixed $values
   *   The values to normalize.
   * @param array|null $enum
   *   The enum array.
   *
   * @return array
   *   The normalized values.
   */
  public function normalizeEnumValues(mixed $values, ?array $enum = NULL): array;

  /**
   * Normalize the value without returning a default value.
   *
   * @param mixed $value
   *   The value to normalize.
   * @param array $enum
   *   The enum array.
   *
   * @return mixed
   *   The normalized value.
   */
  public function normalizeEnumValue(mixed $value, ?array $enum = NULL): mixed;

  /**
   * Converts a source value type to enum data type.
   *
   * @param mixed $value
   *   The stored.
   * @param array $enum
   *   The defined enums.
   *
   * @return float|int|mixed
   *   The converted value.
   */
  public function convertValueToEnumType(mixed $value, array $enum) : mixed;

}
