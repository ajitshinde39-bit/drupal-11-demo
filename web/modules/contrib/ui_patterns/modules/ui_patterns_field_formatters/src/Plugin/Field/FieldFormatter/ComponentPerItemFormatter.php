<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Attribute\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;

/**
 * Plugin implementation of the 'component_each' formatter.
 *
 * Field types are altered in
 * ui_patterns_field_formatters_field_formatter_info_alter().
 */
#[FieldFormatter(
  id: 'ui_patterns_component_per_item',
  label: new TranslatableMarkup('Component per item (UI Patterns)'),
  description: new TranslatableMarkup('Render each field item as a SDC.'),
  field_types: [
    'entity_reference',
  ],
)]
class ComponentPerItemFormatter extends ComponentFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $build = [];
    $context = $this->getComponentSourceContexts($items);
    $context['ui_patterns:lang_code'] = new Context(new ContextDefinition('any'), $langcode);
    $context['ui_patterns:field:items'] = new Context(new ContextDefinition('any'), $items);
    for ($field_item_index = 0; $field_item_index < $items->count(); $field_item_index++) {
      $build[] = $this->buildComponentRenderable($this->getComponentConfiguration()['component_id'],
        array_merge($context, ['ui_patterns:field:index' => new Context(new ContextDefinition('integer'), $field_item_index)]));
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getComponentSourceContexts(?FieldItemListInterface $items = NULL): array {
    // Set the context of field and entity (override the method trait).
    return RequirementsContext::addToContext(["field_granularity:item"], parent::getComponentSourceContexts($items));
  }

}
