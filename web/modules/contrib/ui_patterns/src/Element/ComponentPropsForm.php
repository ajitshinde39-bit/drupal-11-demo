<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Component to render all props of a component.
 *
 * Usage example:
 *
 * @code
 * $form['props'] = [
 *   '#component_id' => 'component_id',
 *   '#type' => 'component_props_form',
 *   '#default_value' => [
 *     'props' => [],
 *   ],
 * ];
 * @endcode
 *
 * Value example:
 *
 * @code
 *   ['#default_value' =>
 *     'props' => [
 *       ['props_id' =>
 *        ['source_id' => 'id', 'source' => []]
 *       ]
 *     ],
 *   ]
 * @endcode
 *
 * Configuration:
 *
 *  '#component_id' => Required Component ID.
 *  '#source_contexts' => The context of the sources.
 *  '#tag_filter' => Filter sources based on these tags.
 *
 * @FormElement("component_props_form")
 */
class ComponentPropsForm extends ComponentFormBase {

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
      '#prop_filter' => NULL,
      '#render_headings' => TRUE,
      '#render_sources' => TRUE,
      '#wrap' => TRUE,
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
   * Build props forms.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public static function buildForm(array &$element, FormStateInterface $form_state): array {
    $component = static::getComponent($element);
    $contexts = $element['#source_contexts'] ?? [];
    $props = $component->metadata->schema['properties'];
    if (empty($props)) {
      return $element;
    }
    $configuration = $element['#default_value']['props'] ?? [];
    if ($element['#render_headings']) {
      $prop_heading = new FormattableMarkup("<p><strong>@title</strong></p>", ["@title" => t("Props")]);
      $element[] = [
        '#markup' => $prop_heading,
      ];
    }
    $prop_filter = $element['#prop_filter'] ?? NULL;
    foreach ($props as $prop_id => $prop) {
      if ($prop_id === 'variant') {
        continue;
      }
      $prop_type = $prop['ui_patterns']['type_definition'];
      $element[$prop_id] = [
        '#type' => 'component_prop_form',
        '#title' => $prop["title"] ?? $prop_type->label(),
        '#description' => $prop["description"] ?? $prop_type->getPluginDefinition()['description'] ?? NULL,
        '#default_value' => $configuration[$prop_id] ?? [],
        '#source_contexts' => $contexts,
        '#tag_filter' => $element['#tag_filter'],
        '#component_id' => $component->getPluginId(),
        '#prop_id' => $prop_id,
        '#wrap' => $element['#wrap'] ?? TRUE,
        '#render_sources' => $element['#render_sources'] ?? TRUE,
      ];
      if (is_array($prop_filter) && !in_array($prop_id, $prop_filter)) {
        $element[$prop_id]['#access'] = FALSE;
      }
    }
    if (count(Element::children($element)) === 0) {
      $element['#access'] = FALSE;
    }
    return $element;
  }

}
