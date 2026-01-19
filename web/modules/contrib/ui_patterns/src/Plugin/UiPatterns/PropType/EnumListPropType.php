<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\EnumTrait;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'enum_list' PropType.
 */
#[PropType(
  id: 'enum_list',
  label: new TranslatableMarkup('List of enums'),
  description: new TranslatableMarkup('Ordered list of predefined string or number items.'),
  default_source: 'selects',
  schema: ['type' => 'array', 'items' => ['type' => ['string', 'number', 'integer'], 'enum' => []]],
  priority: 5,
)]
class EnumListPropType extends PropTypePluginBase {

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
    return static::normalizeEnumListSize(static::normalizer()->normalizeEnumValues($value, $definition_items['enum'] ?? []), $definition, FALSE);
  }

}
