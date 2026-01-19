<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;

/**
 * Plugin implementation of the source.
 *
 * @deprecated in ui_patterns:2.0.0-rc1 and is removed from ui_patterns:3.0.0.
 * Merged into AttributesWidget plugin.
 * @see https://www.drupal.org/project/ui_patterns/issues/3491705
 */
#[Source(
  id: 'class_attribute',
  label: new TranslatableMarkup('HTML classes [Deprecated]'),
  description: new TranslatableMarkup('A space-separated list of HTML classes.'),
  prop_types: ['attributes']
)]
class ClassAttributeWidget extends AttributesWidget {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['warning'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'warning' => [
          $this->t('`HTML classes` source is deprecated in favor of `Attributes`.'),
        ],
      ],
    ];
    return $form;
  }

}
