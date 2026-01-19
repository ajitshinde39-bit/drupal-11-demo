<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;

/**
 * Component to render slots for a component.
 *
 * Usage example:
 *
 * @code
 * $form['slots'] = [
 *   '#type' => 'component_slots_form',
 *   '#component_id' => 'id'
 *   '#default_value' => [
 *     'slots' => [],
 *   ],
 * ];
 * @endcode
 *
 * Value example:
 *
 * @code
 *   ['#default_value' =>
 *     'slots' => [
 *       'slots_id' => [
 *         ['sources' =>
 *           ['source_id' => 'id', 'value' => []]
 *         ]
 *       ],
 *     ],
 *   ]
 * @endcode
 *
 * @FormElement("component_slots_form")
 */
class ComponentSlotsForm extends ComponentFormBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#multiple' => FALSE,
      '#default_value' => NULL,
      '#component_id' => NULL,
      '#source_contexts' => [],
      '#tag_filter' => [],
      '#wrap' => TRUE,
      '#render_headings' => TRUE,
      '#process' => [
        [$class, 'buildForm'],
      ],
      '#after_build' => [
        [$class, 'afterBuild'],
      ],
      '#element_validate' => [
        [$class, 'elementValidate'],
      ],
    ];
  }

  /**
   * Alter the element after the form is built.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered element.
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) : array {
    if ($form_state->isProcessingInput()) {
      static::elementValidate($element, $form_state);
    }
    return $element;
  }

  /**
   * Alter the element after the form is built.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function elementValidate(array &$element, FormStateInterface $form_state) : void {
    if (isset($element['#value']) && is_array($element['#value'])) {
      // For browser-submitted forms, the submitted values do not contain
      // values for certain elements (empty multiple select, unchecked
      // checkbox). Child elements are processed after the parent element,
      // The processed values, stored in '#value', are bubbled up to the
      // parent element here.
      foreach ($element['#value'] as $slot => &$slot_value) {
        if (isset($element[$slot]) && isset($element[$slot]['#value'])) {
          $slot_value = $element[$slot]['#value'];
        }
      }
    }
  }

  /**
   * Processes slots form element.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public static function buildForm(array &$element, FormStateInterface $form_state): array {

    $component = static::getComponent($element);
    if (!isset($component->metadata->slots) || count(
        $component->metadata->slots
      ) === 0) {
      $element['#access'] = FALSE;
      return $element;
    }
    $contexts = $element['#source_contexts'] ?? [];
    $configuration = $element['#default_value'] ?? [];
    if ($element['#render_headings']) {
      $slot_heading = new FormattableMarkup("<p><strong>@title</strong></p>", ["@title" => t("Slots")]);
      $element[] = [
        '#markup' => $slot_heading,
      ];
    }
    foreach ($component->metadata->slots as $slot_id => $slot) {
      $element[$slot_id] = [
        '#title' => $slot['title'] ?? '',
        '#type' => 'component_slot_form',
        '#description' => $slot["description"] ?? NULL,
        '#default_value' => $configuration[$slot_id] ?? [],
        '#component_id' => $component->getPluginId(),
        '#slot_id' => $slot_id,
        '#source_contexts' => $contexts,
        '#wrap' => $element['#wrap'] ?? TRUE,
        '#tag_filter' => $element['#tag_filter'],
        '#prefix' => "<div class='component-form-slot'>",
        '#suffix' => "</div>",
      ];
    }
    return $element;
  }

}
