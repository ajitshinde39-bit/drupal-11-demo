<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\DerivableContext;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\DerivableContext;
use Drupal\ui_patterns\DerivableContextPluginBase;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Drupal\ui_patterns\Plugin\Derivative\DerivableContextDeriver;

/**
 * Plugin implementation of the prop source.
 */
#[DerivableContext(
  id: 'field',
  label: new TranslatableMarkup('Fields'),
  description: new TranslatableMarkup('Derived contexts for Field.'),
  deriver: DerivableContextDeriver::class
)]
class EntityFieldDerivableContext extends DerivableContextPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivedContexts(): array {
    $contexts = $this->context;
    $split_plugin_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $this->getPluginId());
    $field_name = array_pop($split_plugin_id);
    $field_name_context_definition = new ContextDefinition("string", "Field Name");
    $contexts['field_name'] = new Context($field_name_context_definition, $field_name);
    if (isset($contexts['ui_patterns:field:index'])) {
      unset($contexts['ui_patterns:field:index']);
    }
    $plugin_definition = $this->getPluginDefinition();
    $contexts = RequirementsContext::removeFromContext(["field_granularity:item"], $contexts);
    if (is_array($plugin_definition) && isset($plugin_definition["metadata"]["field"]["cardinality"])) {
      $field_cardinality = $plugin_definition["metadata"]["field"]["cardinality"];
      if ($field_cardinality === 1) {
        $contexts = RequirementsContext::addToContext(["field_granularity:item"], $contexts);
      }
    }
    return [$contexts];
  }

}
