<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\Form\ComponentFormBuilderTrait;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field widget for a predefined component display form.
 */
#[FieldWidget(
  id: "ui_patterns_ui_component_form_display",
  label: new TranslatableMarkup("Component Display Form (UI Patterns UI)"),
  description: new TranslatableMarkup("Displays a predefined Component Form Display."),
  field_types: ["ui_patterns_source"],
)]
class ComponentFormDisplayWidget extends WidgetBase {

  use ComponentFormBuilderTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected ComponentPluginManager $componentPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->componentPluginManager = $container->get('plugin.manager.sdc');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings(): array {
    return [
      'display_id' => NULL,
      'display_component_form' => NULL,
      'additional_display_ids' => NULL,
    ] + parent::defaultSettings();
  }

  /**
   * Get the display mode options.
   *
   * @return array
   *   Display mode options.
   */
  protected function getFormModeOptions(): array {
    $options = [];

    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[] $form_displays */
    $form_displays = $this->entityTypeManager
      ->getStorage('component_form_display')
      ->loadByProperties(['status' => TRUE]);

    foreach ($form_displays as $id => $form_display) {
      $label = sprintf('[%s] %s', $form_display->getComponentId(), $form_display->label());
      $options[$this->escapeDisplayId($id)] = $label;
    }

    asort($options);

    return $options;
  }

  /**
   * Converts dot notation to double pipes for safe storage.
   */
  private function escapeDisplayId(string $id): string {
    return str_replace('.', '||', $id);
  }

  /**
   * Converts double pipes back to dot notation.
   */
  private function unescapeDisplayId(?string $id): ?string {
    if ($id === NULL) {
      return NULL;
    }
    return str_replace('||', '.', $id);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $options = $this->getFormModeOptions();
    $element = [];

    $element['display_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Default display ID'),
      '#default_value' => $this->getSetting('display_id'),
      '#required' => TRUE,
      '#options' => $options,
      '#empty_value' => '',
      '#empty_option' => t('- None -'),
    ];

    $element['additional_display_ids'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Additional display ID'),
      '#default_value' => $this->getSetting('additional_display_ids'),
      '#options' => $options,
    ];

    $element['display_component_form'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show component form'),
      '#default_value' => $this->getSetting('display_component_form'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $summary = [];
    if ($this->getSetting('display_id') !== NULL) {
      $summary[] = $this->t('Display Id: @display', ['@display' => $this->unescapeDisplayId($this->getSetting('display_id'))]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function form(FieldItemListInterface $items, array &$form, FormStateInterface $form_state, $get_delta = NULL) {
    $field_name = $this->fieldDefinition->getName();
    $parents = $form['#parents'];
    if (!static::getWidgetState($parents, $field_name, $form_state)) {
      $field_state = [
        'items_count' => count($items) - 1,
        'array_parents' => [],
      ];
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    return parent::form($items, $form, $form_state, $get_delta);
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item_delta_value = $items[$delta]->getValue() ?? [];
    $source_id = $item_delta_value['source_id'] ?? 'component';

    if ($source_id !== 'component') {
      // The widget can only deal with component sources.
      // To make sure no data will be overwritten we disable the widget.
      $element['#access'] = FALSE;
      return $element;
    }

    $settings = $this->getSettings() ?? [];
    $display_id = $this->unescapeDisplayId($settings['display_id']) ?? NULL;
    $display_component_form = $settings['display_component_form'] ?? FALSE;
    $additional_display_ids = $settings['additional_display_ids'] ?? [];
    $additional_display_ids = array_filter($additional_display_ids);
    $additional_display_ids_values = [$display_id];
    if ($additional_display_ids) {
      foreach ($additional_display_ids as $additional_display_id) {
        $additional_display_ids_values[] = $this->unescapeDisplayId($additional_display_id);
      }
    }
    $component_id = NULL;

    if (!$display_id) {
      $element['#access'] = FALSE;
      return $element;
    }

    $form_display = $this->entityTypeManager
      ->getStorage('component_form_display')
      ->load($display_id);

    if ($form_display instanceof ComponentFormDisplay) {
      $component_id = $form_display->getComponentId();
    }

    if (!$component_id) {
      $element['#access'] = FALSE;
      return $element;
    }

    $source_data = $item_delta_value["source"] ?? [];
    $component_default_value = $source_data['component'] ??
      [
        '#component_id' => $component_id,
        '#display_id' => $display_id,
      ];

    $element['source'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $element['source_id'] = ['#type' => 'hidden', '#value' => $source_id];

    $contexts = $this->getComponentSourceContexts($items);
    $component_element = $this->buildComponentsForm($form_state, $contexts, $component_id, TRUE, TRUE, 'ui_patterns');

    $component_element['#include_display_ids'] = $additional_display_ids_values;
    $component_element['#default_value'] = $component_default_value;
    $component_element['#display_component_form'] = $display_component_form;
    $element['source']['component'] = $component_element;

    return $element;
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
