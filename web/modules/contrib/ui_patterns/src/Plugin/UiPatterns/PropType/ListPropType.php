<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'list' PropType.
 */
#[PropType(
  id: 'list',
  label: new TranslatableMarkup('List'),
  description: new TranslatableMarkup('List of free string or number items.'),
  default_source: 'list_textarea',
  schema: [
    'type' => 'array',
    'items' => [
      'type' => ['string', 'number', 'integer'],
    ],
  ],
  priority: 1,
)]
class ListPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $summary = parent::getSummary($definition);
    if (isset($definition['items']['enum'])) {
      $values = implode(", ", $definition['items']['enum']);
      $summary[] = $this->t("Values: @values", ["@values" => $values]);
    }
    if (isset($definition['items']['minItems'])) {
      $summary[] = $this->t("Min items: @length", ["@length" => $definition['items']['minItems']]);
    }
    if (isset($definition['items']['maxItems'])) {
      $summary[] = $this->t("Max items: @length", ["@length" => $definition['items']['maxItems']]);
    }
    return $summary;
  }

}
