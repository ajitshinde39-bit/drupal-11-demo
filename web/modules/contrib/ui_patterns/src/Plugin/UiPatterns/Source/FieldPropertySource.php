<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\Plugin\Derivative\FieldPropertySourceDeriver;

/**
 * Plugin implementation of the prop source.
 */
#[Source(
  id: 'field_property',
  label: new TranslatableMarkup('Field Property (Props)'),
  description: new TranslatableMarkup('Field property source plugin for props.'),
  deriver: FieldPropertySourceDeriver::class
)]
class FieldPropertySource extends FieldValueSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $items = $this->getEntityFieldItemList();
    $delta = (isset($this->context['ui_patterns:field:index'])) ? $this->getContextValue('ui_patterns:field:index') : 0;
    $property = $this->getCustomPluginMetadata('property');
    if (empty($property) || empty($items)) {
      return NULL;
    }
    /** @var \Drupal\Core\Field\FieldItemInterface $field_item_at_delta */
    $field_item_at_delta = $items->get($delta);
    if (!$field_item_at_delta) {
      return NULL;
    }
    $property_value = $field_item_at_delta->get($property)->getValue();
    $prop_typ_types = [];
    if (isset($this->propDefinition['type'])) {
      // Type can be an array of types or a single type.
      $prop_typ_types = is_array($this->propDefinition['type']) ? $this->propDefinition['type'] : [$this->propDefinition['type']];
    }
    return $this->transTypeProp($property_value, $prop_typ_types);
  }

  /**
   * Trans-type the value to be valid inside SDC.
   *
   * @param mixed $value
   *   The value to trans-type.
   * @param array<string> $prop_types
   *   The prop types.
   *
   * @return bool|float|int
   *   The value converted.
   */
  protected function transTypeProp(mixed $value, array $prop_types): mixed {
    foreach ($prop_types as $prop_type) {
      $converted = match ($prop_type) {
        'integer' => is_int($value) ? $value : (int) $value,
        'float', 'decimal' => is_float($value) ? $value : (float) $value,
        'boolean' => is_bool($value) ? $value : (boolean) $value,
        default => NULL,
      };
      if ($converted !== NULL) {
        return $converted;
      }
    }
    return $value;
  }

}
