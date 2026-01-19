<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;

/**
 * Plugin implementation of the 'component_all' formatter.
 *
 * Field types are altered in
 * ui_patterns_field_formatters_field_formatter_info_alter().
 */
#[FieldFormatter(
  id: 'ui_patterns_component',
  label: new TranslatableMarkup('Component (UI Patterns)'),
  description: new TranslatableMarkup('Render a field as a SDC.'),
  field_types: [
    'entity_reference',
  ],
)]
class ComponentFormatter extends ComponentFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (count($items) === 0) {
      return [];
    }
    $context = $this->getComponentSourceContexts($items);
    $context['ui_patterns:lang_code'] = new Context(new ContextDefinition('any'), $langcode);
    $context['ui_patterns:field:items'] = new Context(new ContextDefinition('any'), $items);
    // If context 'ui_patterns:field:index' exists
    // it will be kept.
    if (isset($context['ui_patterns:field:index'])) {
      unset($context['ui_patterns:field:index']);
    }
    // If not wrapped into an array, it won't be rendered as expected.
    return [$this->buildComponentRenderable($this->getComponentConfiguration()['component_id'], $context)];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $result = (parent::isApplicable($field_definition) && (
        ($cardinality === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) ||
        ($cardinality > 1)));
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function getComponentSourceContexts(?FieldItemListInterface $items = NULL): array {
    // Set the context of field and entity (override the method trait).
    return RequirementsContext::addToContext(["field_granularity:items"], parent::getComponentSourceContexts($items));
  }

}
