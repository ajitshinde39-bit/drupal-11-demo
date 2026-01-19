<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\Plugin\Derivative\EntityReferenceFieldPropertyDerivableContextDeriver;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'entity:field_property',
  label: new TranslatableMarkup('Entity reference field property'),
  description: new TranslatableMarkup('Entity reference field property source plugin for props.'),
  deriver: EntityReferenceFieldPropertyDerivableContextDeriver::class,
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: TRUE),
  ]
)]
class EntityReferenceFieldPropertySource extends DerivableContextSourceBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form["derivable_context"]["#type"] = "hidden";
    $form["derivable_context"]["#value"] = $form["derivable_context"]["#default_value"];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function listDerivableContexts() : array {
    $derivable_contexts = parent::listDerivableContexts();
    $field_name = $this->context['field_name']->getContextValue();
    return array_filter($derivable_contexts, function ($derivable_context, $derivable_context_id) use ($field_name) {
          return isset($derivable_context['metadata']) && is_array($derivable_context['metadata'])
            && isset($derivable_context['metadata']['field_name']) && $derivable_context['metadata']['field_name'] === $field_name;
    }, ARRAY_FILTER_USE_BOTH);
  }

  /**
   * {@inheritDoc}
   */
  protected function getSourcesTagFilter(): array {
    return [
      "widget:dismissible" => FALSE,
      "widget" => FALSE,
    ];
  }

  /**
   * {@inheritDoc}
   */
  protected function getDerivationTagFilter(): ?array {
    return [
      // "entity" => TRUE,
      "entity_referenced" => TRUE,
    ];
  }

}
