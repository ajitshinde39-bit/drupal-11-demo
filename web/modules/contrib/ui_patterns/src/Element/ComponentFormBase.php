<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElementBase;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\ui_patterns\SourceInterface;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Base class for components forms.
 */
abstract class ComponentFormBase extends FormElementBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderPropOrSlot', 'processPropOrSlot'];
  }

  /**
   * Check if the form element needs a details.
   *
   * @param array $element
   *   The form element.
   *
   * @return string|null
   *   Prop or slot id if the form element needs a details.
   */
  protected static function checkDetailsElement(array &$element) : ?string {
    if (!isset($element["#wrap"]) || !$element["#wrap"]) {
      return NULL;
    }
    $prop_or_slot_id = $element["#prop_id"] ?? self::getSlotId($element);
    $title_in_component = $element["#title_in_component"] ?? $prop_or_slot_id;
    $title = !empty($element['#title']) ? $element['#title'] : $title_in_component;
    if (!array_key_exists($prop_or_slot_id, $element)) {
      $element[$prop_or_slot_id] = [
        "#type" => "details",
        "#title" => $title,
        "#description" => $element["#description"] ?? NULL,
        "#open" => FALSE,
      ];
    }
    return $prop_or_slot_id;
  }

  /**
   * Customize slot or prop form elements (pre-render).
   *
   * @param array $element
   *   Element to process.
   *
   * @return array
   *   Processed element
   */
  public static function preRenderPropOrSlot(array $element) : array {
    if ($prop_or_slot_id = static::checkDetailsElement($element)) {
      $children_keys = Element::children($element);
      foreach ($children_keys as $child_key) {
        if ($child_key === $prop_or_slot_id) {
          continue;
        }
        $element[$prop_or_slot_id][] = $element[$child_key];
        $element[$child_key]["#printed"] = TRUE;
      }
    }
    return $element;
  }

  /**
   * Customize slot or prop form elements (process).
   *
   * @param array $element
   *   Element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The processed element.
   */
  public static function processPropOrSlot(array &$element, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    if ($prop_or_slot_id = static::checkDetailsElement($element)) {
      if (is_array($triggering_element) && isset($triggering_element["#array_parents"]) && is_array($triggering_element["#array_parents"])) {
        $element_array_parents = $element["#array_parents"];
        $trigger_array_parents = $triggering_element["#array_parents"];
        $start_of_trigger_parents = array_slice($trigger_array_parents, 0, count($element_array_parents));
        if ($start_of_trigger_parents === $element_array_parents) {
          $element[$prop_or_slot_id]["#open"] = TRUE;
        }
      }
    }
    return $element;
  }

  /**
   * Get a unique element id based on the parents and a parameter.
   */
  protected static function getElementId(array $element, string $base_id): string {
    $parents = (array_key_exists("#array_parents", $element) && is_array($element["#array_parents"])) ?
      $element["#array_parents"] : [];
    $returned = (count($parents) > 0) ?
      Html::getId(implode("_", $parents) . "_" . $base_id)
      : Html::getId($base_id);
    return $returned;
  }

  /**
   * Expand each ajax element with ajax urls.
   *
   * @param array $element
   *   The ajax element.
   *
   * @return array
   *   The extended ajax form.
   */
  protected static function expandAjax(array $element): array {
    $url = $element['#ajax_url'] ?? NULL;
    if (isset($element['#ajax']) && $url) {
      $element['#ajax']['url'] = $url;
    }
    return $element;
  }

  /**
   * Helper function to get the currently selected component ID.
   *
   * @param array $element
   *   The form element.
   *
   * @return string|null
   *   The selected component ID or NULL if not set.
   */
  protected static function getSelectedComponentId(array $element): ?string {
    return $element['#component_id'] ?? $element['#default_value']['component_id'] ?? NULL;
  }

  /**
   * Helper function to return the component.
   */
  protected static function getComponent(array $element): Component | NULL {
    $component_id = self::getSelectedComponentId($element);
    /** @var \Drupal\Core\Theme\ComponentPluginManager $component_plugin_manager */
    $component_plugin_manager = \Drupal::service("plugin.manager.sdc");
    return $component_id ? $component_plugin_manager->find($component_id) : NULL;
  }

  /**
   * Get sources for a prop or slot, ordered.
   *
   * @param string $prop_or_slot_id
   *   The prop or slot ID.
   * @param array $definition
   *   The prop or slot definition.
   * @param array $element
   *   The form element.
   *
   * @return array<string, \Drupal\ui_patterns\SourceInterface>
   *   The sources, ordered.
   */
  protected static function getSources(string $prop_or_slot_id, array $definition, array $element): array {
    $configuration = $element['#default_value'] ?? [];
    $source_contexts = $element['#source_contexts'] ?? [];
    $form_array_parents = $element['#array_parents'] ?? [];
    $tag_filter = $element['#tag_filter'] ?? [];
    /** @var \Drupal\ui_patterns\SourcePluginManager $source_plugin_manager */
    $source_plugin_manager = \Drupal::service("plugin.manager.ui_patterns_source");
    /** @var \Drupal\ui_patterns\PropTypeInterface $prop_type */
    $prop_type = empty($definition) ? $source_plugin_manager->getSlotPropType() : $definition['ui_patterns']['type_definition'];
    $prop_plugin_definition = $prop_type->getPluginDefinition();
    $default_source_id = (is_array($prop_plugin_definition) && isset($prop_plugin_definition["default_source"])) ? $prop_plugin_definition["default_source"] : NULL;
    $sources = $source_plugin_manager->getDefinitionsForPropType($prop_type->getPluginId(), $source_contexts, $tag_filter);
    $source_ids = array_keys($sources);
    $source_ids = array_combine($source_ids, $source_ids);
    if (empty($source_ids)) {
      return [];
    }
    $valid_sources = $source_plugin_manager->createInstances($source_ids, SourcePluginBase::buildConfiguration($prop_or_slot_id, $definition, $configuration, $source_contexts, $form_array_parents));
    foreach ($valid_sources as &$source) {
      /** @var \Drupal\ui_patterns\SourcePluginBase $source  */
      $source_id = $source->getPluginId();
      $source->setConfiguration(array_merge($source->getConfiguration(), [
        "selection" => [
          "default" => ($source_id === $default_source_id),
          "tags" => $sources[$source_id]["tags"] ?? [],
        ],
      ]));
    }
    unset($source);
    return static::orderSources($valid_sources, $default_source_id);
  }

  /**
   * Helper function to get the prop ID from an element.
   *
   * @param array $element
   *   The form element.
   *
   * @return string
   *   The prop ID or empty string if not set.
   */
  protected static function getSlotId(array $element): string {
    return (string) ($element['#slot_id'] ?? "");
  }

  /**
   * Get source plugin form.
   */
  protected static function getSourcePluginForm(FormStateInterface $form_state, ?SourceInterface $source, string $wrapper_id): array {
    if (!$source) {
      return [
        "#type" => 'container',
        "#attributes" => [
          'id' => $wrapper_id,
        ],
      ];
    }
    $form = $source->settingsForm([], $form_state);
    // @phpstan-ignore-next-line
    $form['#prefix'] = "<div id='" . $wrapper_id . "'>" . ($form['#prefix'] ?? '');
    $form['#suffix'] = ($form['#suffix'] ?? '') . "</div>";
    // Weird, but :switchSourceForm() AJAX handler doesn't work without that.
    foreach (Element::children($form) as $child) {
      if (isset($form[$child]['#description']) && !isset($form[$child]['#description_display'])) {
        $form[$child]['#description_display'] = 'after';
      }
    }
    return $form;
  }

  /**
   * Ajax handler: Switch source plugin form.
   */
  public static function switchSourceForm(array $form, FormStateInterface $form_state): array {
    $parents = $form_state->getTriggeringElement()["#array_parents"];
    $subform = NestedArray::getValue($form, array_slice($parents, 0, -1));
    return $subform["source"];
  }

  /**
   * Get selected source plugin.
   */
  protected static function getSelectedSource(array $configuration, array $sources): ?SourceInterface {
    $source_id = $configuration['source_id'] ?? NULL;
    foreach ($sources as $source) {
      if ($source->getPluginId() === $source_id) {
        return $source;
      }
    }
    return static::selectDefaultSource($sources);
  }

  /**
   * Select default source plugin from a list.
   *
   * @param array $sources
   *   The sources.
   *
   * @return \Drupal\ui_patterns\SourceInterface|null
   *   The selected source or NULL.
   */
  protected static function selectDefaultSource(array $sources): ?SourceInterface {
    // Try to return the first widget source.
    foreach ($sources as $source) {
      /** @var \Drupal\ui_patterns\SourceInterface $source */
      $plugin_definition = $source->getPluginDefinition() ?? [];
      $tags = is_array($plugin_definition) ? ($plugin_definition["tags"] ?? []) : [];
      if (in_array("widget", $tags)) {
        return $source;
      }
    }
    return NULL;
  }

  /**
   * Add required visual clue to the fieldset.
   *
   * The proper required control is managed by SourcePluginBase::addRequired()
   * so the visual clue is present whether or not the control is done by the
   * source plugin. This is feature, not a bug.
   */
  protected static function addRequired(array $element, string $prop_id): array {
    $component = static::getComponent($element);
    if (!$component || !isset($component->metadata->schema["required"])) {
      return $element;
    }
    $required_props = $component->metadata->schema["required"];
    if (!in_array($prop_id, $required_props)) {
      return $element;
    }
    $element["#required"] = TRUE;
    return $element;
  }

  /**
   * Add title and description to the source element.
   *
   * @param array $element
   *   The element.
   *
   * @return array
   *   The element with title and description.
   */
  protected static function addTitleAndDescription(array $element): array {
    if (isset($element["source"]["value"])) {
      $element["source"]["value"]["#title_display"] = 'before';
      if (empty($element["source"]["value"]["#title"])) {
        $element["source"]["value"]["#title"] = $element["#title"];
      }
      if (empty($element["source"]["value"]["#description"])) {
        $element["source"]["value"]["#description"] = $element['#description'] ?? NULL;
      }
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
   * Alter the element during validation.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function elementValidate(array &$element, FormStateInterface $form_state) : void {
    // For browser-submitted forms, the submitted values do not contain
    // values for certain elements (empty multiple select, unchecked
    // checkbox). Child elements are processed after the parent element,
    // The processed values, stored in the form_state, are propagated
    // to the '#value' of the element here (parents will process).
    $element['#value'] = $form_state->getValue($element['#parents']);
    // Values are cleaned
    // similarly to $form_state->cleanValues();
    if (!empty($element['#value'])) {
      static::cleanValues($element['#value']);
    }
  }

  /**
   * Clean values.
   *
   * @param array $value
   *   Value to clean.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected static function cleanValues(array &$value) : void {

  }

  /**
   * Build sources selector widget.
   */
  protected static function buildSourceSelector(array $sources, ?SourceInterface $selected_source, string $wrapper_id): array {
    if (empty($sources)) {
      return [];
    }
    if ($selected_source && (count($sources) == 1)) {
      return [
        '#type' => 'hidden',
        '#value' => array_keys($sources)[0],
        // To allow the AJAX to work.
        '#ajax' => [
          'callback' => [static::class, 'switchSourceForm'],
          'wrapper' => $wrapper_id,
          'effect' => 'fade',
        ],
      ];
    }
    $options = static::sourcesToOptions($sources);
    return [
      '#type' => 'select',
      "#options" => $options,
      '#title' => t('Source'),
      '#default_value' => $selected_source?->getPluginId(),
      '#attributes' => [
        'class' => ["uip-source-selector"],
      ],
      '#empty_option' => t('- Select -'),
      '#ajax' => [
        'callback' => [static::class, 'switchSourceForm'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Order sources according to a strategy.
   *
   * @param array<string, \Drupal\ui_patterns\SourceInterface> $sources
   *   The sources to order.
   * @param string $default_source_id
   *   The default source id.
   *
   * @return array<string, \Drupal\ui_patterns\SourceInterface>
   *   The ordered sources.
   */
  protected static function orderSources(array $sources, string $default_source_id) : array {
    $returned = [];
    if ($default_source_id && isset($sources[$default_source_id])) {
      $returned[$default_source_id] = $sources[$default_source_id];
    }
    $native_sources = array_filter($sources, function ($source) use ($default_source_id) {
      /** @var \Drupal\ui_patterns\SourcePluginBase $source */
      return ($source->getPluginId() !== $default_source_id) && in_array("prop_type_compatibility:native", $source->getConfiguration()["selection"]["tags"] ?? []);
    });
    $converted_sources = array_filter($sources, function ($source) use ($default_source_id) {
      /** @var \Drupal\ui_patterns\SourcePluginBase $source */
      return ($source->getPluginId() !== $default_source_id) && !in_array("prop_type_compatibility:native", $source->getConfiguration()["selection"]["tags"] ?? []);
    });
    uasort($native_sources, function ($a, $b) {
      return strcasecmp($a->label(), $b->label());
    });
    uasort($converted_sources, function ($a, $b) {
      return strcasecmp($a->label(), $b->label());
    });
    foreach ($native_sources as $source) {
      $returned[$source->getPluginId()] = $source;
    }
    foreach ($converted_sources as $source) {
      $returned[$source->getPluginId()] = $source;
    }
    return $returned;
  }

  /**
   * Get selected source plugin.
   */
  protected static function sourcesToOptions(array $sources, bool $use_group = TRUE): array {
    $options = [];
    $context_switchers = [];
    foreach ($sources as $valid_source_plugin) {
      $plugin_configuration = $valid_source_plugin->getConfiguration();
      $label = (string) $valid_source_plugin->getPluginDefinition()["label"];
      if ($use_group && isset($plugin_configuration['selection']) && isset($plugin_configuration['selection']["tags"]) && in_array("context_switcher", $plugin_configuration['selection']["tags"])) {
        $context_switchers[$valid_source_plugin->getPluginId()] = $label;
        continue;
      }
      $options[$valid_source_plugin->getPluginId()] = $label;
    }
    if ($context_switchers) {
      $label = (string) t("More data");
      $options[$label] = $context_switchers;
    }
    return $options;
  }

}
