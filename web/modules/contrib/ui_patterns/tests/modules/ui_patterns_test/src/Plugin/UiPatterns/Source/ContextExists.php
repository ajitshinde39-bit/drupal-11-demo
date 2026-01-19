<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_test\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Plugin implementation of the source_provider.
 */
#[Source(
  id: 'context_exists',
  label: new TranslatableMarkup('Context exists'),
  description: new TranslatableMarkup('Test Plugin to display context.'),
  prop_types: ['string'],
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: FALSE),
  ]
)]
final class ContextExists extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return ['context_type' => 'entity'];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form['context_exists'] = [
      '#type' => 'markup',
      '#markup' => '<div class="context-exists">' . $this->getPropValue() . '</div>',
    ];

    $form['context_type'] = [
      '#type' => 'select',
      '#options' => [
        'entity' => $this->t('Entity'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $context_type = $this->getSetting('context_type') ?? 'entity';
    $entity = $this->getContextValue($context_type);
    return $context_type . ' exists: ' . ($entity !== NULL);
  }

}
