<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\ContextHandler;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Url;

/**
 * Component form builder trait.
 */
trait ComponentFormBuilderTrait {

  /**
   * Prefills the context mapping select.
   *
   * Hides the context mapping selection if $context_mapping_value is available
   * and set $context_mapping_value as value.
   *
   * @param array $element
   *   The render element.
   * @param string $context_mapping_value
   *   The default value of the context mapping.
   *
   * @return array
   *   The adjusted element.
   */
  protected function componentsAdjustContextEntitySelection(array $element, string $context_mapping_value): array {
    if (is_array($element) && isset($element['entity']) && isset($element['entity']['#options'][$context_mapping_value])) {
      $element["entity"]['#access'] = FALSE;
      $element["entity"]['#value'] = $context_mapping_value;
    }
    return $element;
  }

  /**
   * Adapter function to get plugin configuration.
   *
   * Overwrite to return settings/options of the
   * current plugin.
   *
   * @param string $configuration_id
   *   The configuration id.
   *
   * @return array
   *   The plugin settings/options.
   */
  protected function getComponentConfiguration(string $configuration_id = 'ui_patterns'): array {
    return $this->configuration[$configuration_id] ?? [];
  }

  /**
   * Adapter function to set plugin configuration.
   *
   * Overwrite to return settings/options of the
   * current plugin.
   *
   * @param mixed $configuration
   *   The configuration to store.
   * @param string $configuration_id
   *   The configuration id.
   */
  private function setComponentConfiguration($configuration, string $configuration_id = 'ui_patterns'): void {
    $this->configuration[$configuration_id] = $configuration;
  }

  /**
   * Get component form default.
   *
   *  To use with:
   *  - PluginSettingsInterface::defaultSettings
   *  - ConfigurableInterface::defaultConfiguration
   *  - views/PluginBase::setOptionDefaults
   *  - ...
   *
   * @return array<string, array<string, mixed> >
   *   The default settings.
   */
  public static function getComponentFormDefault() : array {
    return [
      "ui_patterns" => [
        "component_id" => NULL,
        "variant_id" => NULL,
        "slots" => [],
        "props" => [],
      ],
    ];
  }

  /**
   * Returns the ajax url.
   *
   * Some integrations plugins like the views plugin provide there
   * ajax urls for ajax form interaction. To support
   * those plugins overwrite this function.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Url|null
   *   The ajax url.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function getAjaxUrl(FormStateInterface $form_state): ?Url {
    return NULL;
  }

  /**
   * Build the complete form.
   *
   * The form contains.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array|null $source_contexts
   *   Source contexts.
   * @param string|null $initial_component_id
   *   The initial_component_id. If provided the component is changeable.
   * @param bool $render_slots
   *   TRUE if slots are editable.
   * @param bool $render_props
   *   TRUE if props are editable.
   * @param string $configuration_id
   *   The configuration id.
   * @param array $form_element_overrides
   *   The additional settings.
   */
  protected function buildComponentsForm(
    FormStateInterface $form_state,
    ?array $source_contexts = [],
    ?string $initial_component_id = NULL,
    bool $render_slots = TRUE,
    bool $render_props = TRUE,
    string $configuration_id = 'ui_patterns',
    array $form_element_overrides = [],
  ): array {
    $form_state = $form_state instanceof SubformState ? $form_state->getCompleteFormState() : $form_state;
    $form = array_merge([
      '#type' => 'component_form',
      '#component_id' => $initial_component_id,
      '#ajax_url' => $this->getAjaxUrl($form_state),
      '#source_contexts' => $source_contexts,
      '#default_value' => $this->getComponentConfiguration($configuration_id),
      '#render_slots' => $render_slots,
      '#render_props' => $render_props,
    ], $form_element_overrides);
    self::moduleHandler()->alter('ui_patterns_form', $form, $form_state);
    return $form;
  }

  /**
   * Submit the component form.
   *
   * The form contains.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param string $form_element_key
   *   The form key of the component form element.
   * @param string $configuration_id
   *   The configuration id to store.
   */
  public function submitComponentsForm(FormStateInterface $form_state, string $form_element_key = 'ui_patterns', string $configuration_id = 'ui_patterns'): void {
    $this->setComponentConfiguration($form_state->getValue($form_element_key), $configuration_id);
  }

  /**
   * Build component renderable (a SDC render element).
   *
   * @param string|null $component_id
   *   The component id.
   * @param array<string, \Drupal\Core\Plugin\Context\ContextInterface> $source_contexts
   *   The source contexts.
   * @param string $configuration_id
   *   The configuration id.
   *
   * @return array
   *   The renderable array.
   */
  public function buildComponentRenderable(?string $component_id = NULL, array $source_contexts = [], string $configuration_id = 'ui_patterns'): array {
    $configuration = $this->getComponentConfiguration($configuration_id);
    return [
      '#type' => 'component',
      '#component' => $component_id ?? $configuration['component_id'],
      '#ui_patterns' => $configuration,
      '#source_contexts' => $source_contexts,
    ];
  }

  /**
   * Wraps the component element builder.
   *
   * @return \Drupal\ui_patterns\Element\ComponentElementBuilder
   *   The component element builder.
   */
  protected function componentElementBuilder() {
    return \Drupal::service("ui_patterns.component_element_builder");
  }

  /**
   * Wraps the context repository.
   *
   * @return \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   *   The context repository.
   */
  protected function contextRepository(): ContextRepositoryInterface {
    return \Drupal::service('context.repository');
  }

  /**
   * Wraps the module handler.
   */
  protected function moduleHandler(): ModuleHandlerInterface {
    return \Drupal::moduleHandler();
  }

  /**
   * Wraps the context handler.
   *
   * @return \Drupal\Core\Plugin\Context\ContextHandler
   *   The context handler.
   */
  protected function contextHandler(): ContextHandler {
    return \Drupal::service('context.handler');
  }

  /**
   * Calculate a component dependencies.
   *
   * @param string|null $component_id
   *   Component ID.
   * @param array<string, mixed> $source_contexts
   *   Source contexts.
   * @param string $configuration_id
   *   The configuration id.
   *
   * @return array
   *   The dependencies.
   */
  public function calculateComponentDependencies(?string $component_id = NULL, array $source_contexts = [], $configuration_id = 'ui_patterns'): array {
    return $this->componentElementBuilder()->calculateComponentDependencies($component_id, $this->getComponentConfiguration($configuration_id), $source_contexts);
  }

}
