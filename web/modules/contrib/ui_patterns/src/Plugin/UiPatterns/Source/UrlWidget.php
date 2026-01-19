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
  id: 'url',
  label: new TranslatableMarkup('URL'),
  description: new TranslatableMarkup('External URL.'),
  prop_types: ['url'],
  tags: ['widget', 'widget:dismissible']
)]
class UrlWidget extends SourcePluginPropValueWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['value'] = [
      '#type' => 'url',
      '#default_value' => $this->getSetting('value'),
      '#description' => $this->t("Enter an external URL"),
    ];
    $this->addRequired($form['value']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $value = parent::getPropValue();
    if (empty($value)) {
      return $value;
    }
    return $this->replaceTokens($value, FALSE);
  }

}
