<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;

/**
 * Plugin implementation of the field_label source.
 */
#[Source(
  id: 'field_label',
  label: new TranslatableMarkup('[Field] Label'),
  description: new TranslatableMarkup('Field label source plugin.'),
  prop_types: ['string'],
  tags: ['entity', 'field', 'field_label'],
  context_requirements: ['field_formatter'],
  context_definitions: [
    'entity' => new ContextDefinition('entity', label: new TranslatableMarkup('Entity'), required: TRUE),
    'field_name' => new ContextDefinition('string', label: new TranslatableMarkup('Field Name'), required: TRUE),
  ]
)]
class FieldLabelSource extends FieldSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $field_definition = $this->getFieldDefinition();
    if (!$field_definition) {
      return NULL;
    }
    return $field_definition->getLabel();
  }

}
