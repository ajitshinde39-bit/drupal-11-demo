<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'number' PropType.
 */
#[PropType(
  id: 'number',
  label: new TranslatableMarkup('Number'),
  description: new TranslatableMarkup('Either integers or floating point numbers.'),
  default_source: 'number',
  schema: ['type' => ['number', 'integer']],
  priority: 1,
  typed_data: ['decimal', 'float', 'integer']
)]
class NumberPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $summary = parent::getSummary($definition);
    if (isset($definition['type']) && $definition['type'] === "integer") {
      $summary[] = $this->t("Integers only");
    }
    if (isset($definition['minimum'])) {
      $summary[] = $this->t("Minimum: @length", ["@length" => $definition['minimum']]);
    }
    if (isset($definition['maximum'])) {
      $summary[] = $this->t("Maximum: @length", ["@length" => $definition['minimum']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    if (NULL === $value) {
      return NULL;
    }

    if (isset($definition['minimum']) && ($value < $definition['minimum'])) {
      return NULL;
    }
    if (isset($definition['maximum']) && ($value > $definition['maximum'])) {
      return NULL;
    }

    if (self::expectInteger($definition)) {
      return (int) $value;
    }
    return (float) $value;
  }

  /**
   * Determine from the definition if the prop type should return an integer.
   *
   * @param array|null $definition
   *   The prop type definition.
   *
   * @return bool
   *   TRUE if expecting an integer.
   */
  private static function expectInteger(?array $definition = NULL): bool {
    $type = (is_array($definition) && isset($definition['type'])) ? $definition['type'] : NULL;
    if (($type === 'integer') || (is_array($type) && !in_array('number', $type, TRUE) && in_array('integer', $type, TRUE))) {
      return TRUE;
    }
    return FALSE;
  }

}
