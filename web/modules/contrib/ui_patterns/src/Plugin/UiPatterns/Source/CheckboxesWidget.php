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
  id: 'checkboxes',
  label: new TranslatableMarkup('Checkboxes'),
  description: new TranslatableMarkup('A set of checkboxes.'),
  prop_types: ['enum_set'],
  tags: ['widget']
)]
class CheckboxesWidget extends SourcePluginPropValueWidget {

  use EnumTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = parent::getPropValue() ?? [];
    $value = is_scalar($value) ? [$value] : $value;
    return array_filter($value);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $defaultValue = $this->getSetting('value') ?? [];
    if (!is_array($defaultValue)) {
      $defaultValue = [$defaultValue];
    }
    $form['value'] = [
      '#type' => 'checkboxes',
      '#default_value' => $defaultValue,
      "#options" => static::getEnumOptions($this->propDefinition['items']),
    ];
    $this->addRequired($form['value']);
    return $form;
  }

}
