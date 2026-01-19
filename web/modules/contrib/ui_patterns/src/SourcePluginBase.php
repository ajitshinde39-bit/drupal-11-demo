<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\ui_patterns\Entity\SampleEntityGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for source plugins.
 */
abstract class SourcePluginBase extends PluginBase implements
  SourceInterface,
  ContainerFactoryPluginInterface {

  use ContextAwarePluginAssignmentTrait;
  use ContextAwarePluginTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;
  use PluginDependencyTrait;


  /**
   * Definition of the targeted prop.
   */
  protected array $propDefinition;

  /**
   * ID of the targeted prop.
   */
  protected string $propId;

  /**
   * The provided plugin contexts.
   *
   * @var array
   */
  protected $context = [];

  /**
   * All gathered plugin contexts.
   *
   * @var array
   */
  protected $gatheredContexts = [];

  /**
   * The plugin settings.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultSettingsMerged = FALSE;


  /**
   * Use for settings form, to know where the form is used exactly (Optional)
   *
   * @var array<string>|null
   */
  protected $formArrayParents = NULL;

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns_prop_type'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('ui_patterns.sample_entity_generator'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('ui_patterns.normalizer')
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\PropTypePluginManager $propTypeManager
   *   The prop type manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\ui_patterns\Entity\SampleEntityGeneratorInterface $sampleEntityGenerator
   *   The sample entity generator service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   * @param \Drupal\ui_patterns\UiPatternsNormalizerInterface $normalizer
   *   The normalizer service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected PropTypePluginManager $propTypeManager,
    protected ContextRepositoryInterface $contextRepository,
    protected RouteMatchInterface $routeMatch,
    protected SampleEntityGeneratorInterface $sampleEntityGenerator,
    protected ModuleHandlerInterface $moduleHandler,
    protected Token $token,
    protected UiPatternsNormalizerInterface $normalizer,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->setDefinedContextValues();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $key): mixed {
    // Merge defaults if we have no value for the key.
    if (!$this->defaultSettingsMerged && !array_key_exists($key, $this->settings)) {
      $this->mergeDefaults();
    }

    return $this->settings[$key] ?? NULL;
  }

  /**
   * Merges default settings values into $settings.
   */
  protected function mergeDefaults() : void {
    $this->settings += $this->defaultSettings();
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings): PluginSettingsInterface {
    $this->settings = $settings;
    $this->defaultSettingsMerged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function label(bool $with_context = FALSE): string {
    if ($with_context && $this instanceof SourceWithChoicesInterface) {
      $choice_id = $this->getChoice($this->settings);
      if ($choice_id) {
        $choices = $this->getChoices();
        if (isset($choices[$choice_id]) && isset($choices[$choice_id]['label'])) {
          // Return the label of the selected choice.
          return (string) $choices[$choice_id]['label'];
        }
      }
    }
    // Cast the label to a string since it is a TranslatableMarkup object.
    return ($this->pluginDefinition instanceof PluginDefinitionInterface) ? $this->pluginDefinition->id() : (string) ($this->pluginDefinition["label"] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getPropValue(): mixed;

  /**
   * {@inheritdoc}
   */
  public function getValue(?PropTypeInterface $prop_type = NULL): mixed {
    $data = $this->getPropValue();
    if (!$prop_type) {
      return $data;
    }
    $source_plugin_definition = $this->getPluginDefinition();
    $prop_type_id = $prop_type->getPluginId();
    $source_prop_types = ($source_plugin_definition instanceof PluginDefinitionInterface) ? NULL : $source_plugin_definition['prop_types'];
    if (!is_array($source_prop_types) || in_array($prop_type_id, $source_prop_types)) {
      return $data;
    }
    $convertible_props = $this->propTypeManager->getConvertibleProps($prop_type_id);
    // Iter over the prop types declared in the source
    // to find a conversion path from expected prop type.
    // @todo select the shortest conversion path?
    foreach ($source_prop_types as $convertible_prop_type) {
      if (!array_key_exists($convertible_prop_type, $convertible_props)) {
        continue;
      }
      // We start a conversion.
      $data = $this->getPropValue();
      // A path exists, we follow the conversion path.
      $conversion_path = $convertible_props[$convertible_prop_type];
      $conversion_path = array_reverse($conversion_path);
      $conversion_path_size = count($conversion_path);
      for ($conversion_iter = 0; $conversion_iter < $conversion_path_size - 1; $conversion_iter++) {
        try {
          $to_prop_class = $this->propTypeManager->getDefinition($conversion_path[$conversion_iter + 1])["class"];
          $data = $to_prop_class::convertFrom($conversion_path[$conversion_iter], $data);
        }
        catch (\UnhandledMatchError $e) {
          // Do nothing.
          throw $e;
        }
      }
      // We stop when we found a conversion path.
      break;
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) : void {
    if (isset($configuration['prop_definition'])) {
      $this->propDefinition = $configuration['prop_definition'];
    }
    if (isset($configuration['prop_id'])) {
      $this->propId = $configuration['prop_id'];
    }
    if (isset($configuration['settings']['context_mapping'])) {
      $configuration['context_mapping'] = $configuration['settings']['context_mapping'];
      unset($configuration['settings']['context_mapping']);
    }
    if (isset($configuration['settings'])) {
      $this->setSettings($configuration['settings']);
    }
    if (isset($configuration['contexts'])) {
      $this->context = $configuration['contexts'];
    }

    if (isset($configuration['form_array_parents']) && is_array($configuration['form_array_parents'])) {
      $this->formArrayParents = $configuration['form_array_parents'];
    }

    $this->configuration = $configuration;
  }

  /**
   * Build plugin configuration.
   */
  public static function buildConfiguration(string $prop_id, array $prop_definition, array $settings, array $source_contexts, ?array $form_array_parents = NULL): array {
    $context_mapping = $settings['source']['context_mapping'] ?? [];
    unset($settings['source']['context_mapping']);
    $widget_settings = $settings['widget_settings'] ?? [];
    return [
      'prop_id' => $prop_id,
      'prop_definition' => $prop_definition,
      'contexts' => $source_contexts,
      'settings' => $settings['source'] ?? [],
      'widget_settings' => array_merge([
        'title_display' => 'invisible',
        'title' => $prop_definition['title'] ?? '',
        'description_display' => 'invisible',
        'description' => $prop_definition['description'] ?? '',
        'required' => ($prop_definition["ui_patterns"]["required"] ?? FALSE) == TRUE,
      ], $widget_settings),
      'context_mapping' => $context_mapping,
      'form_array_parents' => $form_array_parents,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropId(): string {
    return $this->propId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropDefinition(): array {
    return $this->propDefinition;
  }

  /**
   * Set values for the defined contexts of this plugin.
   */
  private function setDefinedContextValues() : void {
    $configuration = $this->getConfiguration();
    // Fetch the available contexts.
    $available_contexts = $this->contextRepository->getAvailableContexts();

    $available_runtime_contexts = $this->context;
    // Ensure that the contexts have data by getting corresponding runtime
    // contexts.
    $available_runtime_contexts += $this->contextRepository->getRuntimeContexts(
      array_keys($available_contexts)
    );
    $plugin_context_definitions = $this->getContextDefinitions();
    $this->gatheredContexts = $available_runtime_contexts;
    foreach ($plugin_context_definitions as $name => $plugin_context_definition) {
      // Identify and fetch the matching runtime context, with the plugin's
      // context definition.
      $matches = $this->contextHandler()
        ->getMatchingContexts(
          $available_runtime_contexts,
          $plugin_context_definition
        );
      $matching_context = NULL;
      $context_mapping = $configuration['context_mapping'] ?? [];
      if (isset($context_mapping[$name]) && isset($matches[$context_mapping[$name]])) {
        $matching_context = $matches[$context_mapping[$name]];
      }
      if ($matching_context) {
        $this->setContextValue($name, $matching_context->getContextValue());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterComponent(array $element): array {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomPluginMetadata(string $key): mixed {
    $plugin_definition = $this->getPluginDefinition();
    if (empty($plugin_definition) ||
      !is_array($plugin_definition) ||
      !is_array($plugin_definition['metadata']) ||
      !array_key_exists($key, $plugin_definition['metadata'])) {
      return NULL;
    }
    return $plugin_definition['metadata'][$key];
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() : array {
    // This will get data from 'config_dependencies' inside plugin definition.
    // return $this->getPluginDependencies($this);
    $plugin_definition = $this->getPluginDefinition();
    $dependencies = [];
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      $dependencies = ($plugin_definition instanceof DependentPluginDefinitionInterface) ? $plugin_definition->getConfigDependencies() : [];
    }
    else {
      $dependencies = $plugin_definition["config_dependencies"] ?? [];
    }
    static::mergeConfigDependencies($dependencies, ["module" => ['ui_patterns']]);
    return $dependencies;
  }

  /**
   * Merge config dependencies to an existing set.
   *
   * @todo use NestedArray::mergeDeep ?
   *
   * @param array<string, array<string> > $dependencies
   *   Existing dependencies.
   * @param array<string, array<string> > $new_dependencies
   *   Dependencies to merge.
   */
  public static function mergeConfigDependencies(array &$dependencies, array $new_dependencies) : void {
    foreach ($new_dependencies as $type => $list) {
      foreach ($list as $name) {
        if (empty($dependencies[$type])) {
          $dependencies[$type] = [$name];
          if (count($dependencies) > 1) {
            // Ensure a consistent order of type keys.
            ksort($dependencies);
          }
        }
        elseif (!in_array($name, $dependencies[$type])) {
          $dependencies[$type][] = $name;
          // Ensure a consistent order of dependency names.
          sort($dependencies[$type], SORT_FLAG_CASE);
        }
      }
    }
  }

  /**
   * Add required property and title.
   *
   * @param array $form_element
   *   The form element.
   *
   * @see \Drupal\ui_patterns\Element\ComponentPropForm::addRequired()
   */
  protected function addRequired(&$form_element): void {
    $form_element['#title'] = $this->getConfiguration()['widget_settings']['title'];
    $form_element['#title_display'] = $this->getConfiguration()['widget_settings']['title_display'];
    $form_element['#description'] = $this->getConfiguration()['widget_settings']['description'];
    $form_element['#description_display'] = $this->getConfiguration()['widget_settings']['description_display'];
    $form_element['#required'] = $this->getConfiguration()['widget_settings']['required'];
  }

  /**
   * Return the token type for the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The token type.
   */
  protected function getTokenTypeForEntity(EntityInterface $entity): string {
    $entity_type_id = $entity->getEntityTypeId();
    // Use token module service when available.
    if ($this->moduleHandler->moduleExists('token')) {
      // @phpstan-ignore-next-line
      return \Drupal::service('token.entity_mapper')->getTokenTypeForEntityType($entity_type_id);
    }
    // Emulate token module service.
    return str_starts_with($entity_type_id, 'taxonomy_') ? str_replace('taxonomy_', '', $entity_type_id) : $entity_type_id;
  }

  /**
   * Get token data.
   *
   * @return array
   *   An array of token data.
   */
  protected function getTokenData(): array {
    try {
      $entity = $this->getContextValue('entity');
      if ($entity instanceof EntityInterface) {
        return [
          $this->getTokenTypeForEntity($entity) => $entity,
        ];
      }
    }
    catch (ContextException) {
    }
    return [];
  }

  /**
   * Add a token tree link to a form.
   *
   * @param array $form
   *   The form.
   * @param string $form_key
   *   The form key to use.
   */
  protected function addTokenTreeLink(array &$form, string $form_key = "help"): void {
    if ($this->moduleHandler->moduleExists('token')) {
      $form[$form_key] = [
        '#theme' => 'token_tree_link',
        '#token_types' => array_keys($this->getTokenData()),
      ];
    }
  }

  /**
   * Replace tokens.
   *
   * @param mixed $value
   *   The string to replace tokens in.
   * @param bool $markup
   *   Whether to return markup (TRUE) or plain text (FALSE).
   * @param \Drupal\Core\Render\BubbleableMetadata|null $bubbleable_metadata
   *   Bubbleable metadata to populate.
   *
   * @return mixed
   *   The string with tokens replaced or original value.
   */
  protected function replaceTokens(mixed $value, bool $markup = FALSE, ?BubbleableMetadata $bubbleable_metadata = NULL): mixed {
    if (!static::isConvertibleToString($value)) {
      return $value;
    }
    $this->normalizer->convertToScalar($value);
    if (empty($value)) {
      return "";
    }
    // If the entity is new, we are probably in a preview system and there can
    // be side effects. Determine if we need to skip rendering?
    $tokenData = $this->getTokenData();
    return $markup ?
        $this->token->replace($value, $tokenData, ['clear' => TRUE], $bubbleable_metadata) :
        $this->token->replacePlain($value, $tokenData, ['clear' => TRUE], $bubbleable_metadata);
  }

  /**
   * Check if a prop value would be naturally a string.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is naturally convertible to string.
   */
  protected static function isConvertibleToString(mixed $value) : bool {
    return (is_string($value) || is_object($value) || is_array($value));
  }

}
