<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\EnumTrait;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'enum_set' PropType.
 */
#[PropType(
  id: 'enum_set',
  label: new TranslatableMarkup('Set of enums'),
  description: new TranslatableMarkup('Set of unique predefined string or number items.'),
  default_source: 'checkboxes',
  schema: [
    'type' => 'array',
    'uniqueItems' => TRUE,
    'items' => [
      'type' => ['string', 'number', 'integer'],
      'enum' => [],
    ],
  ],
  priority: 10
)]
class EnumSetPropType extends PropTypePluginBase {

  use EnumTrait;

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $summary = parent::getSummary($definition);
    if (isset($definition['items']['enum'])) {
      $values = implode(", ", static::getAllowedValues($definition['items']));
      $summary[] = $this->t("Allowed values: @values", ["@values" => $values]);
    }
    if (isset($definition['minItems'])) {
      $summary[] = $this->t("Min items: @length", ["@length" => $definition['minItems']]);
    }
    if (isset($definition['maxItems'])) {
      $summary[] = $this->t("Max items: @length", ["@length" => $definition['maxItems']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    $definition_items = (!is_array($definition)) ? [] : ($definition['items'] ?? []);
    $value = array_unique(static::normalizer()->normalizeEnumValues($value, $definition_items['enum'] ?? []));
    return static::normalizeEnumListSize($value, $definition, TRUE);
  }

}
