<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Core\Form\FormStateInterface;

/**
 * Component to render a single prop.
 *
 * Usage example:
 *
 * @code
 * $form['prop_name'] = [
 *   '#type' => 'component_prop_form',
 *   '#component_id' => 'component_id',
 *   '#prop_id' => 'prop'
 *   '#default_value' => [
 *     'source' => [],
 *     'source_id' => 'textfield'
 *   ],
 * ];
 * @endcode
 *
 * Value example:
 *
 * @code
 * '#default_value' => ['source_id' => 'id', 'source' => []]
 * @endcode
 *
 *  Configuration:
 *
 *  '#component_id' => Required Component ID.
 *  '#prop_id' => Required Prop ID.
 *  '#source_contexts' => The context of the sources.
 *  '#tag_filter' => Filter sources based on these tags.
 *
 * @FormElement("component_prop_form")
 */
class ComponentPropForm extends ComponentFormBase {

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
      '#component_id' => NULL,
      '#slot_id' => NULL,
    // Wrapped (into details/summary) or not.
      '#wrap' => FALSE,
      '#render_sources' => TRUE,
      '#process' => [
        [$class, 'buildForm'],
        [$class, 'processPropOrSlot'],
      ],
      '#pre_render' => [
        [$class, 'preRenderPropOrSlot'],
      ],
      '#theme_wrappers' => [],
      '#after_build' => [
        [$class, 'afterBuild'],
      ],
      '#element_validate' => [
        [$class, 'elementValidate'],
      ],
    ];
  }

  /**
   * Build props forms.
   */
  public static function buildForm(array &$element, FormStateInterface $form_state): array {
    $element['#tree'] = TRUE;
    $component = static::getComponent($element);
    if (!$component || !isset($element['#prop_id'])) {
      return [];
    }
    $prop_id = $element['#prop_id'];
    $definition = $component->metadata->schema['properties'][$prop_id];
    $configuration = $element['#default_value'] ?? [];
    $wrapper_id = static::getElementId($element, 'ui-patterns-prop-item-' . $prop_id);
    // -----
    $sources = static::getSources($prop_id, $definition, $element);
    $selected_source = static::getSelectedSource($configuration, $sources);
    $source_selector = static::buildSourceSelector($sources, $selected_source, $wrapper_id);
    $source_form = $selected_source ? static::getSourcePluginForm($form_state, $selected_source, $wrapper_id) : [
      '#type' => 'container',
      '#attributes' => [
        'id' => $wrapper_id,
      ],
    ];

    $element += [
      'source_id' => $source_selector,
      'source' => array_merge($source_form, ['#prop_id' => $prop_id]),
    ];
    if (!($element['#render_sources'] ?? TRUE) && $selected_source) {
      $element['source_id'] = [
        '#type' => 'hidden',
        '#value' => $selected_source->getPluginId(),
      ];
    }
    $element = static::addRequired($element, $prop_id);
    // This allows "widgets" to have a title when #wrap is unset.
    if (!($element['#wrap'] ?? TRUE)) {
      $element = static::addTitleAndDescription($element);
    }
    return $element;
  }

}
