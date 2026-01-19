<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\EnumTrait;
use Drupal\ui_patterns\SourcePluginPropValueWidget;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'selects',
  label: new TranslatableMarkup('Selects'),
  description: new TranslatableMarkup('A set of select.'),
  prop_types: ['enum_list'],
  tags: ['widget']
)]
class SelectsWidget extends SourcePluginPropValueWidget {

  use EnumTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = parent::getPropValue() ?? [];
    $value = is_scalar($value) ? [$value] : $value;
    $returned = array_values($value);
    return array_map(function ($item) {
      if (!is_string($item) && !is_object($item) && !is_array($item)) {
        return $item;
      }
      return $this->replaceTokens($item, FALSE);
    }, $returned);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $min = $this->propDefinition['minItems'] ?? 0;
    $max = $this->propDefinition['maxItems'] ?? 1;
    foreach (range(0, $max - 1) as $index) {
      $form['value'][$index] = [
        '#type' => 'select',
        '#default_value' => $this->getSetting('value')[$index] ?? NULL,
        '#options' => static::getEnumOptions($this->propDefinition['items']),
        '#title' => '#' . ($index + 1),
        '#required' => ($index < $min),
        '#empty_value' => "",
      ];
    }
    return $form;
  }

}
