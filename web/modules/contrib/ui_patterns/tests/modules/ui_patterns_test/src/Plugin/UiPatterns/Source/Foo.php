<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_test\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source_provider.
 */
#[Source(
  id: 'foo',
  label: new TranslatableMarkup('Foo (ui_patterns_test)'),
  description: new TranslatableMarkup('Foo description.'),
  prop_types: ['string']
)]
final class Foo extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form["value"] = [
      '#type' => 'textfield',
      '#attributes' => [
        'placeholder' => $this->t('Test: FOO'),
      ],
      '#default_value' => $this->getSetting('value'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    return 'foo';
  }

}
