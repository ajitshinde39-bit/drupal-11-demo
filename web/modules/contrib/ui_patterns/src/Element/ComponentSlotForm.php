<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

/**
 * Component to render a single slot.
 *
 * Usage example:
 *
 * @code
 * $form['slot'] = [
 *   '#type' => 'component_slot_form',
 *   '#component_id' => 'card',
 *   '#slot_id' => 'body',
 *   '#default_value' => [
 *     'sources' => [],
 *   ],
 * ];
 * @endcode
 *
 * Value example:
 *
 * @code
 *    ['#default_value' =>
 *      ['sources' =>
 *        ['source_id' => 'id', 'value' => []]
 *      ]
 *    ]
 * @endcode
 *
 * Configuration:
 *
 * '#component_id' =>Optional Component ID.
 *    A slot can rendered without knowing any context.
 * '#slot_id' =>Optional Slot ID.
 * '#source_contexts' =>The context of the sources.
 * '#tag_filter' =>Filter sources based on these tags.
 * '#display_remove' =>Display or hide the remove button. Default = true
 * '#cardinality_multiple' =>Allow or disallow multiple slot items
 *
 * @FormElement("component_slot_form")
 */
class ComponentSlotForm extends ComponentFormBase {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return array_merge(parent::trustedCallbacks(), ['postRenderSlotTable']);
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
      '#display_remove' => TRUE,
      '#display_weight' => TRUE,
      '#component_id' => NULL,
      '#slot_id' => NULL,
      '#cardinality_multiple' => TRUE,
      '#process' => [
        [$class, 'buildForm'],
        [$class, 'processPropOrSlot'],
      ],
      '#pre_render' => [
        [$class, 'preRenderPropOrSlot'],
      ],
      "#wrap" => TRUE,
      "#title_in_component" => NULL,
      '#after_build' => [
        [$class, 'afterBuild'],
      ],
      '#element_validate' => [
          [$class, 'elementValidate'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected static function cleanValues(array &$value) : void {
    if (isset($value['add_more_button'])) {
      unset($value['add_more_button']);
    }
    if (isset($value['sources'])) {
      foreach (Element::children($value['sources']) as $delta) {
        $source = &$value['sources'][$delta];
        if (isset($source['_remove'])) {
          unset($source['_remove']);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function afterBuild(array $element, FormStateInterface $form_state) : array {
    $trigger_element = $form_state->getTriggeringElement();
    if (isset($trigger_element['#ui_patterns_slot']) && ($trigger_element['#ui_patterns_slot_parents'] == $element['#parents'])) {
      $value = $form_state->getValue($trigger_element['#ui_patterns_slot_parents']);
      if (isset($trigger_element['#ui_patterns_slot_operation']) && $slot_operation = $trigger_element['#ui_patterns_slot_operation']) {
        switch ($slot_operation) {
          case 'remove':
            $delta_to_remove = $trigger_element['#delta'];
            $value['sources'] = array_filter($value['sources'], function ($key) use ($delta_to_remove) {
              return $key !== $delta_to_remove;
            }, ARRAY_FILTER_USE_KEY);
            $element["sources"] = array_filter($element["sources"], function ($key) use ($delta_to_remove) {
              return $key !== $delta_to_remove;
            }, ARRAY_FILTER_USE_KEY);
            break;
        }
      }
      $element['#default_value'] = $value;
    }
    parent::afterBuild($element, $form_state);
    return $element;
  }

  /**
   * Handle the rebuild of the form and operations.
   */
  protected static function handleFormRebuild(array &$element, FormStateInterface $form_state) : void {
    $trigger_element = $form_state->getTriggeringElement();
    if ($form_state->isRebuilding() && isset($trigger_element['#ui_patterns_slot'])) {
      if ($trigger_element['#ui_patterns_slot_parents'] == $element['#parents']) {
        $value = $form_state->getValue($trigger_element['#ui_patterns_slot_parents']);
        if (isset($trigger_element['#ui_patterns_slot_operation']) && $slot_operation = $trigger_element['#ui_patterns_slot_operation']) {
          switch ($slot_operation) {
            case 'add':
              $value['sources'][] = [
                'source_id' => $trigger_element['#source_id'] ?? ($trigger_element["#value"] ?? NULL),
                'source' => [],
              ];
              break;
          }
        }
        $element['#default_value'] = $value;
      }
    }
  }

  /**
   * Build single slot form.
   */
  public static function buildForm(array &$element, FormStateInterface $form_state): array {
    static::handleFormRebuild($element, $form_state);
    $slot_id = self::getSlotId($element);
    $component = static::getComponent($element);
    if ($component !== NULL) {
      $slots = $component->metadata->slots;
      $definition = $slots[$slot_id];
    }
    else {
      /** @var \Drupal\ui_patterns\PropTypePluginManager $prop_type_manager */
      $prop_type_manager = \Drupal::service("plugin.manager.ui_patterns_prop_type");
      $definition = [
        'ui_patterns' => [
          "type_definition" => $prop_type_manager->createInstance('slot', []),
        ],
      ];
    }
    $wrapper_id = static::getElementId($element, 'ui-patterns-slot-wrapper-' . $slot_id);
    $element['#tree'] = TRUE;
    $element['#table_title'] = $element['#title'];
    $element['#title_in_component'] = $element['#title'];
    $element['#title'] = '';
    $element['sources'] = static::buildSourcesForm($element, $form_state, $definition, $wrapper_id);
    if ($element['#cardinality_multiple'] === TRUE ||
      (!isset($element['#default_value']['sources']) || count($element['#default_value']['sources']) === 0)) {
      $element['add_more_button'] = static::buildAddSourceButton($element, $definition, $wrapper_id);
    }
    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';
    return $element;
  }

  /**
   * Removes the first occurrence of the <thead> element from an HTML string.
   *
   * @param string $html
   *   The HTML string.
   *
   * @return string
   *   The modified HTML string without the first <thead> element.
   */
  public static function removeFirstThead($html) {
    // Load the HTML into a DOMDocument object.
    $document = Html::load($html);

    // Find the first <thead> element.
    $thead = $document->getElementsByTagName('thead')->item(0);

    // If a <thead> element is found, remove it.
    if ($thead) {
      $thead->parentNode->removeChild($thead);
    }

    // Serialize the modified DOM back into a string.
    return Html::serialize($document);
  }

  /**
   * Alters the rendered form to simulate input forgery.
   *
   * It's necessary to alter the rendered form here because Mink does not
   * support manipulating the DOM tree.
   *
   * @param string $rendered_form
   *   The rendered form.
   *
   * @return string
   *   The modified rendered form.
   *
   * @see \Drupal\Tests\system\Functional\Form\FormTest::testInputForgery()
   */
  public static function postRenderSlotTable($rendered_form) {
    return static::removeFirstThead($rendered_form);
  }

  /**
   * Build single slot's sources form.
   */
  protected static function buildSourcesForm(array $element, FormStateInterface $form_state, array $definition, string $wrapper_id): array {
    $configuration = $element['#default_value'] ?? [];
    $form = [
      '#theme' => 'field_multiple_value_form',
      '#title' => $element['#table_title'] ?? '',
      '#cardinality_multiple' => $element['#cardinality_multiple'],
      '#post_render' => [
        [self::class, 'postRenderSlotTable'],
      ],
    ];
    // Add fake #field_name to avoid errors from
    // template_preprocess_field_multiple_value_form.
    $form['#field_name'] = "foo";
    if (!isset($configuration['sources'])) {
      return $form;
    }
    $slot_id = self::getSlotId($element);
    $n_sources = count($configuration['sources']);
    foreach ($configuration['sources'] as $delta => $source_configuration) {
      $form[$delta] = static::buildSourceForm(
            array_merge($element, [
              "#default_value" => $source_configuration,
              "#array_parents" => array_merge($element["#array_parents"], [$delta]),
            ]), $form_state, $definition, $source_configuration);
      if ($element['#display_remove'] ?? TRUE) {
        $form[$delta]['_remove'] = static::buildRemoveSourceButton($element, $slot_id, $wrapper_id, $delta);
      }
      if ($element['#display_weight'] ?? TRUE) {
        $form[$delta]['_weight'] = static::buildSlotWeight($source_configuration, $delta, $n_sources);
      }
    }
    return $form;
  }

  /**
   * Add slot weight.
   */
  protected static function buildSlotWeight(array $configuration, int $delta, int $weight_delta): array {
    return [
      '#type' => 'weight',
      '#title' => t(
        'Weight for row @number',
        ['@number' => $delta + 1]
      ),
      '#title_display' => 'invisible',
      '#delta' => $weight_delta,
      '#default_value' => $configuration['_weight'] ?? $delta,
      '#weight' => 100,
    ];
  }

  /**
   * Build single source form.
   */
  public static function buildSourceForm(array $element, FormStateInterface $form_state, array $definition, array $configuration): array {
    $slot_id = self::getSlotId($element);
    if (!isset($element['#default_value'])) {
      $element['#default_value'] = $configuration;
    }
    $wrapper_id = static::getElementId($element, 'ui-patterns-slot-item-' . $slot_id);
    $sources = static::getSources($slot_id, $definition, $element);
    $selected_source = static::getSelectedSource($configuration, $sources);
    $source_selector = static::buildSourceSelector($sources, $selected_source, $wrapper_id);
    $form = [
      'source_id' => $source_selector,
      'source' => $selected_source ? static::getSourcePluginForm($form_state, $selected_source, $wrapper_id) : [
        '#type' => 'container',
        '#attributes' => [
          'id' => $wrapper_id,
        ],
      ],
    ];
    if (empty($slot_id)) {
      return $form;
    }
    $form = static::addRequired($form, $slot_id);
    if (!($element['#wrap'] ?? TRUE)) {
      $form = static::addTitleAndDescription($form);
    }
    return $form;
  }

  /**
   * Build widget to remove source.
   */
  protected static function buildRemoveSourceButton(array $element, string $slot_id, string $wrapper_id, int $delta): array {
    $id = implode('-', $element['#array_parents']);
    $remove_action = [
      '#type' => 'submit',
      '#name' => strtr($slot_id, '-', '_') . $id . '_' . $delta . '_remove',
      '#value' => t('Remove'),
      '#submit' => [
        static::class . '::rebuildForm',
      ],
      '#access' => TRUE,
      '#delta' => $delta,
      '#ui_patterns_slot' => TRUE,
      '#ui_patterns_slot_operation' => 'remove',
      '#ui_patterns_slot_parents' => $element['#parents'],
      '#ui_patterns_slot_array_parents' => $element['#array_parents'],
      // We used to have an empty array, but that caused issues with
      // handleErrorsWithLimitedValidation in FormValidator.
      '#limit_validation_errors' => FALSE,
      '#ajax' => [
        'callback' => [static::class, 'refreshForm'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];
    return [
      '#type' => 'container',
      'dropdown_actions' => [
        static::expandComponentButton($element, $remove_action),
      ],
    ];
  }

  /**
   * Build source selector.
   */
  protected static function buildAddSourceButton(array $element, array $definition, string $wrapper_id): array {
    $slot_id = self::getSlotId($element);
    $sources = static::getSources($slot_id, $definition, $element);
    $options = static::sourcesToOptions($sources);
    return [
      "#type" => "select",
      "#empty_option" => t("- Select a source to add -"),
      "#options" => $options,
      '#submit' => [
        static::class . '::rebuildForm',
      ],
      '#access' => TRUE,
      '#ui_patterns_slot_operation' => 'add',
      '#ui_patterns_slot' => TRUE,
      '#ui_patterns_slot_parents' => $element['#parents'],
      '#ui_patterns_slot_array_parents' => $element['#array_parents'],
      '#ajax' => [
        'callback' => [
          static::class,
          'refreshForm',
        ],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Expand button base array into a paragraph widget action button.
   *
   * @param array $element
   *   Element.
   * @param array $button_base
   *   Button base render array.
   *
   * @return array
   *   Button render array.
   */
  protected static function expandComponentButton(array $element, array $button_base): array {
    // Do not expand elements that do not have submit handler.
    if (empty($button_base['#submit'])) {
      return $button_base;
    }

    $button = $button_base + [
      '#type' => 'submit',
    ];

    // Html::getId will give us '-' char in name but we want '_' for now so
    // we use strtr to search&replace '-' to '_'.
    $button['#name'] = strtr(Html::getId($button_base['#name']), '-', '_');
    $button['#id'] = static::getElementId($element, $button['#name']);

    if (isset($button['#ajax'])) {
      $button['#ajax'] += [
        'effect' => 'fade',
        // Since a normal throbber is added inline, this has the potential to
        // break a layout if the button is located in dropbuttons. Instead,
        // it's safer to just show the fullscreen progress element instead.
        'progress' => ['type' => 'fullscreen'],
      ];
    }

    return static::expandAjax($button);
  }

  /**
   * Ajax submit handler: trigger rebuild of the sources form.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public static function rebuildForm(array $form, FormStateInterface $form_state) : void {
    $form_state->setRebuild();
  }

  /**
   * Ajax handler: Refresh sources form.
   */
  public static function refreshForm(array $form, FormStateInterface $form_state) : mixed {
    $triggering_element = $form_state->getTriggeringElement();
    $wrapper_id = $triggering_element['#ajax']['wrapper'];
    $parents = $triggering_element['#ui_patterns_slot_array_parents'];
    $form_state->setRebuild(TRUE);
    $returned = NestedArray::getValue($form, $parents);
    $response = new AjaxResponse();
    $returned["#prefix"] = "";
    $returned["#suffix"] = "";
    $response->addCommand(new HtmlCommand('#' . $wrapper_id, $returned));
    if (isset($triggering_element["#ui_patterns_slot_operation"]) && $triggering_element["#ui_patterns_slot_operation"] === "add") {
      $selector = "#" . $triggering_element["#id"];
      if (!isset($returned['#cardinality_multiple']) || $returned['#cardinality_multiple'] !== FALSE) {
        $response->addCommand(new InvokeCommand($selector, "val", [""]));
      }
    }
    return $response;
  }

}
