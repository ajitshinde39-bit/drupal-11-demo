<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Element\ComponentSlotForm;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Drupal\ui_patterns\SourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A widget to display the UI Patterns configuration form.
 *
 * @internal
 *   Plugin classes are internal.
 */
#[FieldWidget(
  id: 'ui_patterns_source',
  label: new TranslatableMarkup('All slot sources (UI Patterns)'),
  description: new TranslatableMarkup('Widget to edit an UI Patterns source field and configure any source for a slot.'),
  field_types: ['ui_patterns_source'],
)]
class SourceWidget extends WidgetBase {

  /**
   * The source plugin manager.
   *
   * @var \Drupal\ui_patterns\SourcePluginManager
   */
  protected SourcePluginManager $sourcePluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sourcePluginManager = $container->get('plugin.manager.ui_patterns_source');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $field_name = $this->fieldDefinition->getName();
    $element['#slot_id'] = $delta;
    $element['#parents'] = array_merge($element['#field_parents'] ?? [], [$field_name, $delta]);
    $default_value = $this->getDefaultValue($items, $delta, $element, $form, $form_state);
    $element['#source_contexts'] = $this->getComponentSourceContexts($items);
    $element['#tag_filter'] = $this->getSetting('tag_filter') ?? [];
    $source_form = ComponentSlotForm::buildSourceForm($element, $form_state, [], $default_value);
    $source_form['source_id']['#empty_option'] = t("- Select a source to add -");
    return $element + $source_form;
  }

  /**
   * Returns the default value for the source field.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field items.
   * @param int $delta
   *   The delta.
   * @param array $element
   *   The element.
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return mixed
   *   The default value.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function getDefaultValue(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) : mixed {
    $full_form_state_values = $form_state->getValues() ?? [];
    $full_input = $form_state->getUserInput();
    $form_state_values = &NestedArray::getValue($full_form_state_values, $element["#parents"] ?? []);
    if (!empty($full_input) || $form_state->isProcessingInput() || $form_state->isRebuilding()) {
      $form_state_values = &NestedArray::getValue($full_input, $element["#parents"] ?? []);
    }
    $default_value = array_merge($items[$delta]?->getValue() ?? [], $form_state_values ?? []);
    return $default_value;
  }

  /**
   * Set the context.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface|null $items
   *   Field items.
   *
   * @return array
   *   Source contexts.
   */
  protected function getComponentSourceContexts(?FieldItemListInterface $items = NULL): array {
    $contexts = [];
    if ($entity = $items?->getEntity()) {
      $contexts['entity'] = EntityContext::fromEntity($entity);
      $contexts['bundle'] = new Context(ContextDefinition::create('string'), $contexts["entity"]->getContextValue()->bundle() ?? "");
    }
    $contexts = RequirementsContext::addToContext(["field_granularity:item"], $contexts);
    return $contexts;
  }

}
