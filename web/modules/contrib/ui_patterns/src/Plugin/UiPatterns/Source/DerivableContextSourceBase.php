<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ui_patterns\DerivableContextPluginBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourceWithChoicesInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ready to use base class for source plugins using DerivableContexts.
 */
abstract class DerivableContextSourceBase extends SourcePluginBase implements SourceWithChoicesInterface {
  /**
   * The source plugin manager.
   *
   * @var \Drupal\ui_patterns\SourcePluginManager
   */
  protected $sourcePluginManager;


  /**
   * The derivable context manager.
   *
   * @var \Drupal\ui_patterns\DerivableContextPluginManager
   */
  protected $derivableContextManager;


  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface
   */
  protected $contextHandler;

  /**
   * Sources.
   *
   * @var array|null
   */
  protected ?array $derivableContexts = NULL;

  /**
   * The source plugins rendered.
   *
   * @var array<\Drupal\ui_patterns\SourceInterface>
   */
  protected $sourcePlugins = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $plugin = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $plugin->sourcePluginManager = $container->get('plugin.manager.ui_patterns_source');
    $plugin->contextHandler = $container->get('context.handler');
    $plugin->derivableContextManager = $container->get('plugin.manager.ui_patterns_derivable_context');
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      "derivable_context" => NULL,
      "source" => [],
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPropValue(): mixed {
    $definition = $this->propDefinition;
    $prop_type = $definition['ui_patterns']['type_definition'];
    $source_plugins = $this->getSourcePlugins();
    if (!$this->isSlot()) {
      $source_plugin = (count($source_plugins) > 0) ? $source_plugins[0] : NULL;
      return ($source_plugin) ? $source_plugin->getValue($prop_type) : NULL;
    }
    $returned = [];
    foreach ($source_plugins as $source_plugin) {
      $returned[] = $source_plugin->getValue($prop_type);
    }
    return empty($returned) ? NULL : $returned;
  }

  /**
   * Get the context to pass to the derivable context plugin.
   *
   * @return array
   *   The context.
   */
  protected function getContextForDerivation(): array {
    return $this->context;
  }

  /**
   * Get Derived contexts.
   *
   * @param string $derivable_context
   *   The derivable context plugin id.
   *
   * @return array
   *   The derived contexts.
   */
  protected function getDerivedContexts(string $derivable_context) : array {
    /** @var \Drupal\ui_patterns\DerivableContextInterface $derivable_context_plugin */
    $derivable_context_plugin = $this->derivableContextManager->createInstance($derivable_context, DerivableContextPluginBase::buildConfiguration($this->getContextForDerivation()));
    if (!$derivable_context_plugin) {
      return [];
    }
    return $derivable_context_plugin->getDerivedContexts() ?? [];
  }

  /**
   * Set the source plugin according to configuration.
   *
   * @return array<\Drupal\ui_patterns\SourceInterface>
   *   Source plugins
   */
  private function getSourcePlugins(): array {
    $this->sourcePlugins = [];
    $derivable_context = $this->getSetting('derivable_context') ?? NULL;
    if (!$derivable_context) {
      return $this->sourcePlugins;
    }
    $derived_contexts = $this->getDerivedContexts((string) $derivable_context);
    if (empty($derived_contexts)) {
      return $this->sourcePlugins;
    }
    $sources = $this->getSetting($derivable_context) ?? [];
    if (!is_array($sources) || !array_key_exists("value", $sources)) {
      return $this->sourcePlugins;
    }
    $sources = $sources["value"];

    if ($this->isSlot()) {
      if (isset($sources["sources"])) {
        $source_configuration = array_values($sources["sources"])[0];
      }
    }
    else {
      $source_configuration = $sources;
    }

    if (!isset($source_configuration["source_id"])) {
      return $this->sourcePlugins;
    }

    foreach ($derived_contexts as $derived_context) {
      $target_plugin_configuration = array_merge($source_configuration["source"] ?? [], [
        "context" => $derived_context,
      ]);
      $this->sourcePlugins[] = $this->createSourcePlugin($source_configuration["source_id"], $target_plugin_configuration, $derived_context);
    }
    return $this->sourcePlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $this->buildDerivableContextSelectorForm($form, $form_state);
    return $form;
  }

  /**
   * Returns true for slots.
   */
  private function isSlot(): bool {
    $type_definition = $this->getPropDefinition()['ui_patterns']['type_definition'];
    return $type_definition instanceof SlotPropType ? TRUE : FALSE;
  }

  /**
   * Build the form to select and create a source.
   *
   * @param array $form
   *   Input form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Returned form
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  private function buildDerivableContextSelectorForm(array &$form, FormStateInterface $form_state) : array {
    $derivableContexts = $this->listCompatibleDerivableContexts();
    $wrapper_id = Html::getId(implode("_", $this->formArrayParents ?? []) . "_derivable_context_selector");
    $options_derivable_contexts = $this->getDerivableContextsOptions();
    $form = [
      '#type' => 'container',
      "#tree" => TRUE,
    ];
    if (empty($options_derivable_contexts)) {
      $form["derivable_context"] = [
        "#markup" => $this->t("Not available"),
      ];
      return $form;
    }
    $derivable_context = $this->getSelectedDerivableContext($form_state, $options_derivable_contexts);
    $form["derivable_context"] = [
      "#type" => "select",
      "#title" => $this->t("Context"),
      "#options" => $options_derivable_contexts,
      '#default_value' => $derivable_context,
      '#ajax' => [
        'callback' => [__CLASS__, 'onDerivableContextChange'],
        'wrapper' => $wrapper_id,
        'method' => 'replaceWith',
      ],
      '#executes_submit_callback' => FALSE,
      '#empty_value' => NULL,
      '#empty_option' => $this->t('- Select -'),
      '#required' => TRUE,
    ];
    $source_container = [
      '#type' => 'container',
      '#attributes' => ["id" => $wrapper_id, "class" => ["derivable-context-source-wrapper"]],
      '#tree' => TRUE,
    ];
    if (!$derivable_context || !array_key_exists($derivable_context, $derivableContexts)) {
      $form[$derivable_context ?? ''] = $source_container;
      return $form;
    }
    $source = $this->getSetting($derivable_context) ?? [];
    $source = $source["value"] ?? [];
    /** @var \Drupal\ui_patterns\DerivableContextInterface $derivable_context_plugin */
    $derivable_context_plugin = $this->derivableContextManager->createInstance($derivable_context, DerivableContextPluginBase::buildConfiguration($this->context));
    $derived_contexts = $derivable_context_plugin->getDerivedContexts();
    $form[$derivable_context] = $source_container;
    if (count($derived_contexts) === 0) {
      return $form;
    }
    $derivable_context_form = &$form[$derivable_context];
    $derived_context = reset($derived_contexts);
    $component_id = isset($derived_context["component_id"]) ? $derived_context["component_id"]->getContextValue() : NULL;
    $is_slot = $this->isSlot();
    // When option is within a group.
    $group_label = $this->getDerivableContextsOptionGroupLabel($options_derivable_contexts, $derivable_context);
    if (!empty($group_label)) {
      $derivable_context_form["group_label"] = [
        "#markup" => $this->t('From') . ' <span class="plugin-name">' . $group_label . '</span><hr/>',
      ];
    }
    $derivable_context_form["value"] = [
      '#type' => $is_slot ? 'component_slot_form' : 'component_prop_form',
      '#component_id' => $component_id,
      '#title' => '',
      '#cardinality_multiple' => FALSE,
      '#display_remove' => FALSE,
      "#wrap" => FALSE,
      '#' . ($is_slot ? 'slot_id' : 'prop_id') => $this->getPropId(),
      '#tree' => TRUE,
      '#default_value' => $source,
      '#source_contexts' => $derived_context,
      '#tag_filter' => $this->getSourcesTagFilter(),
    ];
    return $form;
  }

  /**
   * Get the selected derivable context and validate it.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, mixed> $options_derivable_contexts
   *   The options.
   *
   * @return string|null
   *   The selected and validated derivable context.
   */
  private function getSelectedDerivableContext(FormStateInterface $form_state, array $options_derivable_contexts) : ?string {
    $derivable_context = (string) ($this->getSetting('derivable_context') ?? '');
    if (!$derivable_context) {
      $derivable_context = $form_state->getValue('derivable_context') ?? NULL;
    }
    // Validate the selected derivable context.
    if ($derivable_context && !isset($options_derivable_contexts[$derivable_context])) {
      // Reset value, or take the first one if only one is available.
      $derivable_context = (count($options_derivable_contexts) === 1) ? key($options_derivable_contexts) : NULL;
      if ($form_state->isProcessingInput()) {
        $this->cleanUserInput($form_state->getCompleteForm(), $form_state);
      }
    }
    // Set default value to the only available option.
    if (!$derivable_context && count($options_derivable_contexts) === 1) {
      $derivable_context = array_key_first($options_derivable_contexts);
    }
    return $derivable_context;
  }

  /**
   * Clean up user input for the derivable context.
   *
   * This is needed to reset the user input when switching the source.
   * The source is not needed in the user input, as it is derived from the
   * selected derivable context.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function cleanUserInput(array $form, FormStateInterface $form_state): void {
    // Cleanup the input for the derivable context.
    $complete_form = $form_state->getCompleteForm();
    $prop_form = NestedArray::getValue($complete_form, $this->formArrayParents);
    if ($prop_form) {
      $input = $form_state->getUserInput();
      $prop_input = &NestedArray::getValue($input, $prop_form["#parents"] ?? []);
      if (isset($prop_input["source"])) {
        unset($prop_input["source"]);
        // Reset the user input, form state values
        // will be recomputed by FormBuilder.
        $form_state->setUserInput($input);
      }
    }
  }

  /**
   * Get derivable contexts options.
   *
   * @return array<string, mixed>
   *   The options.
   */
  protected function getDerivableContextsOptions() : array {
    $choices = $this->getChoices();
    $options_derivable_contexts = [];
    foreach ($choices as $choice_id => $choice) {
      $options_derivable_contexts[$choice_id] = $choice["label"];
    }
    asort($options_derivable_contexts);
    return $options_derivable_contexts;
  }

  /**
   * Get option group label.
   *
   * @param array $options_derivable_contexts
   *   The options, eventually with groups.
   * @param string $derivable_context
   *   The derivable context.
   *
   * @return string|null
   *   The label.
   */
  protected function getDerivableContextsOptionGroupLabel(array $options_derivable_contexts, string $derivable_context) : ?string {

    if (isset($options_derivable_contexts[$derivable_context])) {
      return NULL;
    }
    foreach ($options_derivable_contexts as $group_key => $grouped_option_keys) {
      if (!is_array($grouped_option_keys)) {
        continue;
      }
      if (isset($grouped_option_keys[$derivable_context])) {
        return $group_key;
      }
    }
    return NULL;
  }

  /**
   * Specifies some tags filter array for source selection.
   */
  protected function getSourcesTagFilter(): array {
    return [];
  }

  /**
   * Gets the plugin for this component.
   *
   * @param string $plugin_id
   *   The plugin ID.
   * @param array<mixed> $configuration
   *   The block configuration.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts to set on the plugin.
   * @param array<string> $form_array_parents
   *   Form array parents.
   *
   * @return \Drupal\ui_patterns\SourceInterface|null
   *   The plugin.
   */
  private function createSourcePlugin($plugin_id, array $configuration, array $contexts = [], array $form_array_parents = []) {
    if (!$plugin_id) {
      return NULL;
    }
    try {
      // Field formatter trick.
      $configuration["settings"] = $configuration["settings"] ?? [];
      /** @var \Drupal\ui_patterns\SourceInterface $plugin */
      $plugin = $this->sourcePluginManager->createInstance(
        $plugin_id,
        SourcePluginBase::buildConfiguration($this->propId, $this->propDefinition, ["source" => $configuration], $contexts, $form_array_parents)
      );
      // If ($contexts && $plugin instanceof ContextAwarePluginInterface) {
      // $this->contextHandler->applyContextMapping($plugin, $contexts);
      // }.
      return $plugin;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Ajax callback for fields with AJAX callback to update form substructure.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The replaced form substructure.
   */
  public static function onDerivableContextChange(array $form, FormStateInterface $form_state): array {
    $triggeringElement = $form_state->getTriggeringElement();
    // Dynamically return the dependent ajax for elements based on the
    // triggering element. This shouldn't be done statically because
    // settings forms may be different, e.g. for layout builder, core, ...
    if (!empty($triggeringElement['#array_parents'])) {
      $subformKeys = $triggeringElement['#array_parents'];
      array_pop($subformKeys);
      $subformKeys[] = $triggeringElement["#value"];
      $subform = NestedArray::getValue($form, $subformKeys);
      $form_state->setRebuild();
      return $subform;
    }
    return [];
  }

  /**
   * List source definitions.
   *
   * @return array
   *   Definitions of blocks
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function listCompatibleDerivableContexts() : array {
    if ($this->derivableContexts) {
      return $this->derivableContexts;
    }
    $derivable_contexts = $this->listDerivableContexts();
    $source_tag_filter = $this->getSourcesTagFilter();
    $prop_type_plugin_id = $this->propDefinition['ui_patterns']['type_definition']->getPluginId();
    $this->derivableContexts = array_filter($derivable_contexts, function ($derivable_context, $derivable_context_id) use ($source_tag_filter, $prop_type_plugin_id) {
      /** @var \Drupal\ui_patterns\DerivableContextInterface $derivable_context_plugin */
      $derivable_context_plugin = $this->derivableContextManager->createInstance($derivable_context_id, DerivableContextPluginBase::buildConfiguration($this->context));
      $derived_contexts = $derivable_context_plugin->getDerivedContexts();
      if (count($derived_contexts) === 0) {
        return FALSE;
      }
      $derived_context = reset($derived_contexts);
      $sources = $this->sourcePluginManager->getDefinitionsForPropType($prop_type_plugin_id, $derived_context, $source_tag_filter);
      return (count($sources) > 0);
    }, ARRAY_FILTER_USE_BOTH);
    return $this->derivableContexts;
  }

  /**
   * List source definitions.
   *
   * @return array
   *   Definitions of blocks
   */
  protected function listDerivableContexts() : array {
    return $this->derivableContextManager->getDefinitionsMatchingContextsAndTags($this->context, $this->getDerivationTagFilter());
  }

  /**
   * {@inheritdoc}
   */
  public function getChoices(): array {
    $derivableContexts = $this->listCompatibleDerivableContexts();
    $choices = [];
    foreach ($derivableContexts as $derivable_context_plugin_id => $derivable_context) {
      $metadata = $derivable_context['metadata'] ?? [];
      $choice = [
        "label" => $derivable_context["label"],
        "original_id" => $derivable_context_plugin_id,
        "group" => $metadata["group"] ?? NULL,
        "provider" => $metadata['provider'] ?? NULL,
      ];
      if ($choice['label'] instanceof MarkupInterface) {
        $choice['label'] = (string) $choice['label'];
      }
      if ($choice['group'] instanceof MarkupInterface) {
        $choice['group'] = (string) $choice['group'];
      }
      $choices[$derivable_context_plugin_id] = $choice;
    }
    return $choices;
  }

  /**
   * {@inheritdoc}
   */
  public function getChoiceSettings(string $choice_id): array {
    return [
      'derivable_context' => $choice_id,
      $choice_id => [
        'value' => [],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getChoice(array $settings): string {
    return $settings['derivable_context'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    $derivable_context = $this->getSetting('derivable_context') ?? NULL;
    if (!$derivable_context) {
      return $dependencies;
    }
    /** @var \Drupal\ui_patterns\DerivableContextInterface $derivable_context_plugin */
    $derivable_context_plugin = $this->derivableContextManager->createInstance($derivable_context, DerivableContextPluginBase::buildConfiguration($this->context));
    if (!$derivable_context_plugin) {
      return $dependencies;
    }
    SourcePluginBase::mergeConfigDependencies($dependencies, $this->getPluginDependencies($derivable_context_plugin));
    $source_plugins = $this->getSourcePlugins();
    foreach ($source_plugins as $source_plugin) {
      SourcePluginBase::mergeConfigDependencies($dependencies, $this->getPluginDependencies($source_plugin));
    }
    return $dependencies;
  }

  /**
   * Get tag filter for plugin derivation.
   *
   * @return array|null
   *   Tag filter or NULL.
   */
  protected function getDerivationTagFilter(): ?array {
    return NULL;
  }

}
