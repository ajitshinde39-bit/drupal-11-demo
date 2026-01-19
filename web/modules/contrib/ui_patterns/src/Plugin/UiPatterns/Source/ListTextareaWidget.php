<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'list_textarea',
  label: new TranslatableMarkup('Textarea for list'),
  description: new TranslatableMarkup('One item by line.'),
  prop_types: ['list'],
  tags: ['widget', 'widget:dismissible']
)]
class ListTextareaWidget extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $long_text = $this->getSetting('value') ?? '';
    if (empty($long_text)) {
      return [];
    }
    $list_of_items = (array) preg_split("/\r\n|\n|\r/", $long_text);
    // Cast items if required.
    $items_types = $this->propDefinition['items']['type'] ?? [];
    if (!empty($items_types) && is_string($items_types)) {
      $items_types = [$items_types];
    }
    if (!is_array($items_types)) {
      return $list_of_items;
    }
    return $this->castValues($list_of_items, $items_types);
  }

  /**
   * Cast values.
   *
   * @param array $values
   *   The values to cast.
   * @param array $types
   *   The types to cast to.
   *
   * @return array
   *   The casted values.
   */
  private function castValues(array $values, array $types): array {
    $casted_values = [];
    foreach ($values as $value) {
      $converted = NULL;
      if ($value === "" && in_array("string", $types, TRUE)) {
        $casted_values[] = $value;
        continue;
      }
      foreach ($types as $type) {
        $converted = $this->castValue($value, $type);
        if ($converted !== NULL) {
          break;
        }
      }
      $casted_values[] = ($converted !== NULL) ? $converted : $value;
    }
    return $casted_values;
  }

  /**
   * Cast value.
   *
   * @param mixed $value
   *   The value to cast.
   * @param string $type
   *   The type to cast to.
   *
   * @return mixed
   *   The casted value or NULL.
   */
  private function castValue($value, string $type): mixed {
    try {
      if (self::isConvertibleToString($value)) {
        $value = $this->replaceTokens($value, FALSE);
      }
      return match ($type) {
        'integer' => (is_int($value) || is_numeric($value) || ($value === "")) ? (int) $value : NULL,
        'float', 'decimal' => is_float($value) ? $value : (float) $value,
        'boolean' => is_bool($value) ? $value : (boolean) $value,
        'string' => $value,
        default => NULL,
      };
    }
    catch (\Exception) {
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $items = $this->getSetting('value');
    if (is_array($items)) {
      $items = implode("\r", $items);
    }
    $form['value'] = [
      '#type' => 'textarea',
      '#default_value' => $items,
      "#description" => $this->t("One item by line"),
    ];
    $this->addRequired($form['value']);
    return $form;
  }

}
