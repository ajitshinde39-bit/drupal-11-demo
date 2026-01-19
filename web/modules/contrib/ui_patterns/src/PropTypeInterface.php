<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface for prop_type plugins.
 */
interface PropTypeInterface extends WithJsonSchemaInterface, PluginInspectionInterface {

  /**
   * Returns the translated plugin label.
   *
   * @return string
   *   The translated title.
   */
  public function label();

  /**
   * Convert value from an other type.
   *
   * @return mixed
   *   Converted value.
   *
   * @throws \UnhandledMatchError
   */
  public static function convertFrom(string $prop_type, mixed $value): mixed;

  /**
   * Get default source ID.
   *
   * @return string
   *   Source ID.
   */
  public function getDefaultSourceId(): string;

  /**
   * Returns a short summary for the current prop.
   *
   * @return array
   *   A short summary of the prop.
   */
  public function getSummary(array $definition): array;

  /**
   * Normalize the prop type value before validation.
   *
   * @param mixed $value
   *   The value to normalize.
   * @param array|null $definition
   *   The prop type definition.
   *
   * @return mixed
   *   The JSON schema valid prop type value.
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed;

  /**
   * Preprocess the prop type value before the rendering.
   *
   * Called after the validation, before being sent to the template, in order to
   * ease the work of template owners.
   *
   * @param mixed $value
   *   The value to preprocess.
   * @param array|null $definition
   *   The prop type definition.
   *
   * @return mixed
   *   The processed prop type value.
   */
  public static function preprocess(mixed $value, ?array $definition = NULL): mixed;

}
