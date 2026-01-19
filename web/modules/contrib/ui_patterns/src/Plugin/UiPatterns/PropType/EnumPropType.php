<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\EnumTrait;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'enum' PropType.
 */
#[PropType(
  id: 'enum',
  label: new TranslatableMarkup('Enum'),
  description: new TranslatableMarkup('A single value restricted to a fixed set of values.'),
  default_source: 'select',
  schema: ['type' => ['string', 'number', 'integer'], 'enum' => []],
  priority: 10,
  typed_data: ['float', 'integer', 'string'],

)]
class EnumPropType extends PropTypePluginBase {

  use EnumTrait;

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $summary = parent::getSummary($definition);
    if (isset($definition['enum'])) {
      $values = implode(", ", static::getAllowedValues($definition));
      $summary[] = $this->t("Allowed values: @values", ["@values" => $values]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    // First get the enum array.
    $enum = (!is_array($definition)) ? [] : ($definition['enum'] ?? []);
    if (!is_array($enum)) {
      $enum = [];
    }
    return static::normalizer()->normalizeEnumValue($value, $enum) ?? static::enumDefaultValue($definition);
  }

}
