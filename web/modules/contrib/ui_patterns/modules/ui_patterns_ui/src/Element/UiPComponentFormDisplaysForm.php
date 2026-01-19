<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ui_patterns\Element\ComponentForm;

/**
 * Provides a Component form builder element.
 *
 * @FormElement("uip_displays_form")
 */
class UiPComponentFormDisplaysForm extends ComponentForm {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return array_merge(
      ['#include_display_ids' => NULL, '#display_component_form' => TRUE],
      parent::getInfo()
    );
  }

  /**
   * Returns the entity type manager.
   */
  public static function getEntityTypeManager(): EntityTypeManagerInterface {
    return \Drupal::entityTypeManager();
  }

  /**
   * Get form displays displays for a component.
   *
   * @param string $component_id
   *   The component id.
   *
   * @return \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[]
   *   The component form displays.
   */
  protected static function getComponentDisplays(string $component_id) : array {
    $storage = self::getEntityTypeManager()->getStorage('component_form_display');
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[] $matched_displays */
    $matched_displays = $storage->loadByProperties([
      'component_id' => $component_id,
      'status' => TRUE,
    ]);
    uasort($matched_displays, function ($a, $b) {
      return strnatcasecmp((string) $a->label(), (string) $b->label());
    });
    return $matched_displays;
  }

  /**
   * Build the display selector.
   */
  protected static function buildSelectComponentDisplays(array &$element, array $matched_displays, string $wrapper_id, ?string $selected_display_id = NULL) : void {
    $options = [];
    foreach ($matched_displays as $matched_display) {
      if (is_array($element['#include_display_ids'])) {
        if (!in_array($matched_display->id(), $element['#include_display_ids'])) {
          continue;
        }
      }
      $options[$matched_display->id()] = $matched_display->label();
    }
    if ($element['#display_component_form'] == TRUE &&
      \Drupal::currentUser()->hasPermission('access ui patterns component form')) {
      $options['_component_form'] = t('Component form');
    }

    $element['display_id'] = [
      '#type' => 'select',
      '#options' => $options,
      '#attributes' => ['class' => ['uip-display-select']],
      '#default_value' => $selected_display_id ?? array_keys($options)[0],
      '#ajax' => [
        'callback' => [static::class, 'changeSelectorFormChangeAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
      '#executes_submit_callback' => FALSE,
      '#access' => count($options) > 1,
    ];
  }

  /**
   * Build the display form.
   */
  protected static function buildComponentFormDisplay(array &$element, FormStateInterface $form_state, string $component_id, array $matched_displays) : void {
    $all_form_state_values = $form_state->getValues();
    $form_state_values = &NestedArray::getValue($all_form_state_values, $element['#parents'] ?? []);
    $all_input_values = $form_state->getUserInput();
    $input_values = &NestedArray::getValue($all_input_values, $element['#parents'] ?? []);
    $display_id = $element['#default_value']['display_id'] ?? NULL;

    // If there is already an existing config use _component_form
    // to display this config.
    if ($display_id === NULL && (
      count($element['#default_value']['props'] ?? []) !== 0 ||
      count($element['#default_value']['slots'] ?? []) !== 0)) {
      $display_id = '_component_form';
    }
    if (isset($form_state_values['display_id'])) {
      $display_id = $form_state_values['display_id'];
    }
    elseif (isset($input_values['display_id'])) {
      $display_id = $input_values['display_id'];
    }
    $wrapper_id = static::getElementId($element, 'display');
    static::buildSelectComponentDisplays($element, $matched_displays, $wrapper_id, $display_id);
    $display_id = $element['display_id']['#default_value'];
    $element["display"] = [
      '#type' => 'container',
      '#attributes' => ['id' => $wrapper_id],
      'component_id' => [
        '#type' => 'hidden',
        '#value' => $component_id,
      ],
      'display_id' => [
        '#type' => 'hidden',
        '#value' => $display_id,
      ],
    ];
    if ($display_id === '_component_form') {
      $element["display"]['value_' . Html::getId($display_id)] = [
        '#type' => 'component_form',
        '#allow_override' => TRUE,
        '#component_id' => $component_id,
        '#component_required' => $element['#component_required'] ?? FALSE,
        '#component_validation' => FALSE,
        '#tag_filter' => $element['#tag_filter'] ?? [],
        '#ajax_url' => $element['#ajax_url'] ?? NULL,
        '#source_contexts' => $element['#source_contexts'] ?? [],
        '#render_slots' => $element['#render_slots'] ?? TRUE,
        '#render_props' => $element['#render_props'] ?? TRUE,
        '#default_value' => $element['#default_value'],
        '#wrap' => $element['#wrap'] ?? TRUE,
        '#prop_filter' => $element['#prop_filter'] ?? NULL,
        '#render_headings' => $element['#render_headings'] ?? TRUE,
        '#render_sources' => $element['#render_sources'] ?? TRUE,
      ];
    }
    elseif ($display_id) {
      $element["display"]['value_' . Html::getId($display_id)] = [
        '#type' => 'uip_display_form',
        '#source_contexts' => $element['#source_contexts'] ?? [],
        '#display_id' => $display_id,
        '#component_id' => $component_id,
        '#default_value' => $element['#default_value'],
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function buildForm(array &$element, FormStateInterface $form_state) : array {
    $element = ComponentForm::buildForm($element, $form_state);
    // Retrieve component id.
    $component_id = self::getSelectedComponentId($element);
    $matched_displays = ($component_id !== NULL) ? static::getComponentDisplays($component_id) : [];
    if (count($matched_displays) === 0) {
      return $element;
    }
    // Hide elements from component form.
    $element["variant_id"]['#access'] = FALSE;
    $element["props"]['#access'] = FALSE;
    $element["slots"]['#access'] = FALSE;
    $element['#component_validation'] = TRUE;

    $element["#after_build"] = [
      [static::class, 'afterBuild'],
    ];
    // Display ID.
    static::buildComponentFormDisplay($element, $form_state, $component_id, $matched_displays);
    return $element;
  }

  /**
   * Ajax callback for display selector change.
   */
  public static function changeSelectorFormChangeAjax(
    array $form,
    FormStateInterface $form_state,
  ) : array {
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $parents = array_merge(array_slice($parents, 0, -1), ['display']);
    $sub_form = &NestedArray::getValue($form, $parents);
    return $sub_form ?? [];
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
  public static function elementValidate(array &$element, FormStateInterface $form_state): void {
    $display_id = $element['display']['display_id']['#value'] ?? NULL;

    if (empty($display_id) || !isset($element['display']['value_' . Html::getId($display_id)]['#value'])) {
      return;
    }
    $value = $element['display']['value_' . Html::getId($display_id)]['#value'];
    $element['#value'] = $value;
    if (($element['display']['display_id']['#value'] ?? '') !== '_component_form') {
      $element['#value']['display_id'] = $element['display']['display_id']['#value'];
    }
    $element['#value']['component_id'] = $element['display']['component_id']['#value'];
    $form_state->setValueForElement($element, $element['#value']);
  }

}
