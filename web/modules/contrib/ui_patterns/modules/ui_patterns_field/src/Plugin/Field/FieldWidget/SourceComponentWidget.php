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
use Drupal\Core\Render\Component\Exception\ComponentNotFoundException;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\ComponentPluginManager as UIPatternsComponentPluginManager;
use Drupal\ui_patterns\Form\ComponentFormBuilderTrait;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A widget to display the UI Patterns configuration form.
 *
 * @internal
 *   Plugin classes are internal.
 */
#[FieldWidget(
  id: 'ui_patterns_source_component',
  label: new TranslatableMarkup('Components only (UI Patterns)'),
  description: new TranslatableMarkup('Widget to edit an UI Patterns source field, but configure only the Component source for a slot.'),
  field_types: ['ui_patterns_source'],
)]
class SourceComponentWidget extends WidgetBase {

  use ComponentFormBuilderTrait;

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
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'component_id' => NULL,
      'hide_slots' => TRUE,
      'prop_sources' => NULL,
      'prop_filter_enable' => FALSE,
      'allow_override' => FALSE,
      'selection' => [],
    ] + parent::defaultSettings();
  }

  /**
   * Get the widget settings.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Widget settings.
   */
  protected function getWidgetSettings(FormStateInterface $form_state) : array {
    $field_name = $this->fieldDefinition->getName();
    $array_parents = ["fields", $field_name, "settings_edit_form", "settings"];
    $full_form_state_values = $form_state->getValues();
    $current_settings = &NestedArray::getValue($full_form_state_values, $array_parents);
    return array_merge($this->getSettings(), $current_settings ?? []);
  }

  /**
   * Get the component options.
   *
   * @return array
   *   Component options.
   */
  protected function getComponentOptions() : array {
    $definitions = [];
    if ($this->componentPluginManager instanceof UIPatternsComponentPluginManager) {
      $definitions = $this->componentPluginManager->getGroupedDefinitions();
    }
    $options = [];
    foreach ($definitions as $group_id => $group) {
      foreach ($group as $component_id => $definition) {
        $options[$group_id][$component_id] = $definition['annotated_name'];
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $options = $this->getComponentOptions();
    $field_name = $this->fieldDefinition->getName();
    $settings = $this->getWidgetSettings($form_state);

    $wrapper_id = 'component-props-selection';
    $element = [];
    $element['component_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Component ID'),
      '#default_value' => $this->getSetting('component_id'),
      '#required' => FALSE,
      '#options' => $options,
      '#ajax' => [
        'callback' => [static::class, 'changeSelectorFormChangeAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
      '#executes_submit_callback' => FALSE,
      '#empty_value' => '',
      '#empty_option' => t('- None -'),
    ];
    $element['allow_override'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow override of component form?'),
      '#default_value' => $settings['allow_override'] ?? FALSE,
      '#required' => FALSE,
    ];
    $element['hide_slots'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide slots'),
      '#default_value' => $settings['hide_slots'] ?? TRUE,
      '#required' => FALSE,
    ];
    $prop_sources = $settings['prop_sources'] ?? NULL;
    if ($prop_sources === NULL) {
      $prop_sources = '';
    }
    $element['prop_sources'] = [
      '#type' => 'select',
      '#title' => $this->t('Prop sources'),
      '#options' => [
        '' => $this->t('Display all'),
        'widgets' => $this->t('Only widgets'),
        'default' => $this->t('Only default'),
      ],
      '#default_value' => $prop_sources,
      '#required' => FALSE,
    ];
    $element['prop_filter_enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show only selected props'),
      '#default_value' => $settings['prop_filter_enable'] ?? FALSE,
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          [
            ":input[name='fields[{$field_name}][settings_edit_form][settings][component_id]']" => ['!value' => ''],
          ],
        ],
      ],
    ];

    $element["selection"] = [
      "#type" => "container",
      "#attributes" => [
        "id" => $wrapper_id,
      ],
      "#tree" => TRUE,

    ];
    $component_id_selected = $settings["component_id"] ?? '';
    if (!empty($component_id_selected)) {
      $selection = $settings["selection"] ?? [];
      try {
        $component_selected = $this->componentPluginManager->find($component_id_selected);
        $props = $component_selected->metadata->schema['properties'];
        $options = ["variant" => t("Variant")];
        foreach ($props as $prop_id => $prop) {
          if ($prop_id === 'variant') {
            continue;
          }
          $propTitle = $prop['title'] ?? '';
          $options[$prop_id] = empty($propTitle) ? $prop_id : $propTitle;
        }
        $element["selection"]['prop_filter'] = [
          "#type" => "select",
          '#limit_validation_errors' => [],
          "#multiple" => TRUE,
          "#options" => $options,
          "#default_value" => $selection['prop_filter'] ?? [],
          '#states' => [
            'visible' => [
              [
                ":input[name='fields[{$field_name}][settings_edit_form][settings][prop_filter_enable]']" => ['checked' => TRUE],
                ":input[name='fields[{$field_name}][settings_edit_form][settings][component_id]']" => ['!value' => ''],
              ],
            ],
          ],
        ];
      }
      catch (ComponentNotFoundException $e) {

      }
    }
    return $element;
  }

  /**
   * Ajax callback for component selector change.
   */
  public static function changeSelectorFormChangeAjax(
    array $form,
    FormStateInterface $form_state,
  ) : array {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $sub_form_parents = array_merge(array_slice($parents, 0, -1), ["selection"]);
    $sub_form = NestedArray::getValue($form, $sub_form_parents);
    $form_state->setRebuild();
    return $sub_form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    if ($this->getSetting('component_id') !== NULL) {
      $summary[] = $this->t('Component Id: @component', ['@component' => $this->getSetting('component_id')]);
    }
    if (!($this->getSetting('hide_slots') ?? TRUE)) {
      $summary[] = $this->t('Hide slots');
    }
    $selection = $this->getSetting('prop_filter_enable') ?? [];
    $props_selection = $selection['props'] ?? NULL;
    if (is_array($props_selection)) {
      $summary[] = $this->t('Only selected props: @props', ['@props' => implode(",", $props_selection)]);
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
    $field_name = $this->fieldDefinition->getName();
    $element['#parents'] = array_merge($element['#field_parents'] ?? [], [$field_name, $delta]);
    $settings = $this->getSettings() ?? [];
    $component_id = $settings['component_id'] ?? NULL;
    if (empty($component_id)) {
      $component_id = NULL;
    }
    if ($source_id !== 'component') {
      // The widget can only deal with component sources.
      // To make sure no data will be overwritten we disable the widget.
      $element['#access'] = FALSE;
      return $element;
    }
    $source_data = $item_delta_value["source"] ?? [];
    $component_default_value = $source_data['component'] ?? [];
    $component_in_data = $component_default_value['component_id'] ?? NULL;
    if (!isset($component_default_value['component_id'])) {
      $component_default_value['component_id'] = $component_id;
    }
    elseif ($component_id && $component_in_data && $component_in_data !== $component_id) {
      $element['#access'] = FALSE;
      return $element;
    }
    $contexts = $this->getComponentSourceContexts($items);
    $element['source'] = [
      '#type' => 'container',
      '#tree' => TRUE,
    ];
    $selection = $settings["selection"] ?? [];
    $prop_sources = $settings['prop_sources'] ?? '';
    $wrap = ($prop_sources !== 'default');
    $hide_slots = $settings['hide_slots'] ?? TRUE;
    $form_element_overrides = [
      '#allow_override' => $settings['allow_override'] ?? FALSE,
      '#tag_filter' => ((bool) ($settings['only_widgets'] ?? TRUE)) ? ["widget" => TRUE] : [],
      '#default_value' => $component_default_value,
      '#wrap' => $wrap,
      '#render_headings' => !$hide_slots,
      '#render_sources' => $wrap,
      '#prop_filter' => ($this->getSetting('prop_filter_enable') ?? FALSE) ? $selection['prop_filter'] ?? NULL : NULL,
    ];
    $element['source']["component"] = $this->buildComponentsForm($form_state, $contexts, $component_id, !$hide_slots, TRUE, 'ui_patterns', $form_element_overrides);
    $element['source_id'] = ['#type' => 'hidden', '#value' => $source_id];
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
