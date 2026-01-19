<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourcePluginManager;
use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;

/**
 * Component display form element.
 *
 * @FormElement("uip_display_form")
 */
class UiPComponentFormDisplayForm extends FormElementBase {

  /**
   * Returns the source manager.
   */
  public static function getSourceManager(): SourcePluginManager {
    return \Drupal::service('plugin.manager.ui_patterns_source');
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => NULL,
      '#source_contexts' => [],
      '#tag_filter' => [],
      '#process' => [
        [$class, 'buildForm'],
      ],
      '#after_build' => [
        [$class, 'afterBuild'],
      ],
      '#element_validate' => [
              [$class, 'elementValidate'],
      ],
      '#theme_wrappers' => ['form_element'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {

    if (is_array($input)) {
      $display_id = $element['#display_id'];
      $display = ComponentFormDisplay::load($display_id);
      $variant_id = NULL;
      $props = [];
      $slots = [];
      foreach ($input as $key => $source) {
        if ($key === 'variant') {
          $variant_id = $source;
          continue;
        }
        if (in_array($key, ['props', 'slots', 'component_id', 'display_id', 'variant_id'])) {
          continue;
        }
        if ($display->isSlot($key)) {
          $display_options = $display->getPropSlotOption($key);
          if ($display_options['source_id'] === '') {
            unset($source['add_more_button']);
            $slots[$key] = $source;
          }
          else {
            $slots[$key]['sources'][] = $source;
          }
        }
        else {
          $props[$key] = $source;
        }
      }
      $output = [
        'component_id' => $element['#component_id'],
        'display_id' => $element['#display_id'],
        'variant_id' => $variant_id,
        'props' => $props,
        'slots' => $slots,
      ];
      $element['#default_value'] = $output;
      return $output;
    }
    else {
      return [
        'component_id' => NULL,
        'variant_id' => NULL,
        'props' => [],
        'slots' => [],
      ];
    }
  }

  /**
   * Alter the element during validation.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function elementValidate(array &$element, FormStateInterface $form_state) : void {
    $element['#value'] = self::valueCallback($element, $form_state->getValue($element['#parents']), $form_state);
    if (isset($element['#value'])) {
      $form_state->setValueForElement($element, $element['#value']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) : array {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function buildForm(array $element, FormStateInterface $form_state): array {
    $display_id = $element['#display_id'] ?? '';
    $display = $display_id ? ComponentFormDisplay::load($display_id) : NULL;
    if (!$display) {
      return $element;
    }
    $element['#display'] = $display;
    $slot_prop_options = $display->getPropSlotOptions();

    foreach ($slot_prop_options as $prop_slot_id => $slot_prop_option) {
      if ($slot_prop_option['region'] !== 'content') {
        continue;
      }
      $source_id = $slot_prop_option['source_id'] ?? NULL;
      if ($source_id === NULL) {
        continue;
      }

      $prop_definition = $display->getPropDefinition($prop_slot_id);
      if ($prop_definition === NULL) {
        // Continue if the prop definition is removed from SDC component.
        continue;
      }

      if ($source_id === '') {
        self::buildSourceSelectorForm($display, $prop_slot_id, $element);
      }
      else {
        self::buildSlotPropForm($form_state, $slot_prop_option, $prop_definition, $prop_slot_id, $source_id, $element);
      }
    }
    return $element;
  }

  /**
   * Build Slot or prop form based on the given source id.
   */
  protected static function buildSlotPropForm(FormStateInterface $form_state, array $slot_prop_option, array $prop_definition, string $prop_slot_id, string $source_id, array &$element): void {
    $source_plugin_manager = self::getSourceManager();
    if ($prop_slot_id === 'variant') {
      $configuration = $element['#default_value']['variant_id'] ?? [];
    }
    else {
      $configuration = $element['#default_value']['props'][$prop_slot_id] ?? $element['#default_value']['slots'][$prop_slot_id]['sources'][0] ?? [];
    }
    $source_contexts = [];
    $form_array_parents = $element['#array_parents'];
    $configuration['widget_settings'] = $slot_prop_option['widget_settings'] ?? [];
    if (!isset($configuration['widget_settings']['title_display'])) {
      $configuration['widget_settings']['title_display'] = 'before';
    }
    $source_configuration = SourcePluginBase::buildConfiguration($prop_slot_id, $prop_definition, $configuration, $source_contexts, $form_array_parents);
    /** @var \Drupal\ui_patterns\SourcePluginBase $source */
    $source = $source_plugin_manager->createInstance($source_id, $source_configuration);
    $form = $source->settingsForm([], $form_state);
    $element[$prop_slot_id]['#type'] = 'container';
    $element[$prop_slot_id]['source'] = $form;
    $element[$prop_slot_id]['source_id'] = ['#type' => 'hidden', '#default_value' => $source_id];
  }

  /**
   * Build source selector forms.
   */
  protected static function buildSourceSelectorForm(ComponentFormDisplay $display, string $prop_slot_id, array &$element): void {
    $prop_definition = $display->getPropDefinition($prop_slot_id);
    if ($display->isSlot($prop_slot_id)) {
      $configuration = $element['#default_value']['slots'][$prop_slot_id]['sources'] ?? [];
      $element[$prop_slot_id]['#type'] = 'component_slot_form';
      $element[$prop_slot_id]['#slot_id'] = $prop_slot_id;
      $element[$prop_slot_id]['#title'] = $prop_definition['title'] ?? $prop_slot_id;
      $element[$prop_slot_id]['#source_contexts'] = $element['#source_contexts'] ?? [];
      $element[$prop_slot_id]['#component_id'] = $element['#component_id'];
      $element[$prop_slot_id]['#default_value'] = ['sources' => $configuration];
      $element[$prop_slot_id]['#tag_filter'] = $element['#tag_filter'] ?? [];
      $element[$prop_slot_id]['#prefix'] = "<div class='component-form-slot'>";
      $element[$prop_slot_id]['#suffix'] = "</div>";
    }
    else {
      $configuration = $element['#default_value']['props'][$prop_slot_id] ?? [];
      $element[$prop_slot_id]['#type'] = 'component_prop_form';
      $element[$prop_slot_id]['#title'] = $prop_definition['title'] ?? $prop_slot_id;
      $element[$prop_slot_id]['#prop_id'] = $prop_slot_id;
      $element[$prop_slot_id]['#source_contexts'] = $element['#source_contexts'] ?? [];
      $element[$prop_slot_id]['#component_id'] = $element['#component_id'];
      $element[$prop_slot_id]['#default_value'] = $configuration;
      $element[$prop_slot_id]['#wrap'] = TRUE;
      $element[$prop_slot_id]['#tag_filter'] = $element['#tag_filter'] ?? [];
    }
  }

}
