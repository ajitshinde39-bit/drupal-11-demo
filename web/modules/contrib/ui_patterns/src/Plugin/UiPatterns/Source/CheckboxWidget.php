<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginPropValueWidget;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'checkbox',
  label: new TranslatableMarkup('Checkbox'),
  description: new TranslatableMarkup('Single checkbox'),
  prop_types: ['boolean'],
  tags: ['widget']
)]
class CheckboxWidget extends SourcePluginPropValueWidget {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    return (bool) parent::getPropValue();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['value'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('value'),
    ];
    $this->addRequired($form['value']);
    return $form;
  }

}
