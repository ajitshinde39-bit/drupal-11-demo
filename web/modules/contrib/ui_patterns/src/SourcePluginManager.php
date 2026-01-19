<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\Plugin\Context\RequirementsContextDefinition;

/**
 * Source plugin manager.
 */
class SourcePluginManager extends DefaultPluginManager implements ContextAwarePluginManagerInterface, ContextMatcherPluginManagerInterface {

  use ContextAwarePluginManagerTrait;
  use ContextMatcherPluginManagerTrait;

  /**
   * Constructs the object.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected PropTypePluginManager $propTypeManager,
    protected ContextHandlerInterface $context_handler,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct(
      'Plugin/UiPatterns/Source',
      $namespaces,
      $module_handler,
      SourceInterface::class,
      Source::class
    );
    $this->alterInfo('ui_patterns_source_info');
    $this->setCacheBackend($cache_backend, 'ui_patterns_source_plugins', $this->getInvalidationCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  protected function contextHandler() : ContextHandlerInterface {
    return $this->context_handler;
  }

  /**
   * Get the cache tags to invalidate.
   */
  protected function getInvalidationCacheTags() : array {
    $tags = [];
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    foreach ($entity_type_definitions as $entity_type_definition) {
      if (!$entity_type_definition->entityClassImplements(FieldableEntityInterface::class)) {
        // Skip entity not using fields.
        continue;
      }
      $bundle_entity_type = $entity_type_definition->getBundleEntityType();
      if ($bundle_entity_type) {
        $tags[] = sprintf("config:%s_list", $bundle_entity_type);
      }
    }
    return $tags;
  }

  /**
   * Refines source plugin definition.
   *
   *  It allows for example, to add new context definitions
   *  to the plugin definition, using a static method inside sources plugins.
   *  Very useful for views for example, where each views plugin would
   *  declare a required context with a view.
   *
   * @param array $definition
   *   Plugin definition to process.
   * @param string $plugin_id
   *   Plugin Id.
   */
  public function processDefinition(&$definition, $plugin_id): void {
    parent::processDefinition($definition, $plugin_id);
    if (array_key_exists("context_requirements", $definition) && count($definition["context_requirements"]) > 0) {
      $definition["context_definitions"]["context_requirements"] = RequirementsContextDefinition::fromRequirements($definition["context_requirements"]);
    }
  }

  /**
   * Returns source definitions for a prop type.
   *
   * There is also the method getNativeDefinitionsForPropType()
   * that returns only natively compatible source definitions.
   * There is also the method getConvertibleDefinitionsForPropType()
   * that returns only convertible source definitions.
   *
   * @param string $prop_type_id
   *   The prop type id.
   * @param array|null $contexts
   *   The contexts or null if not using contexts.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *   The array keys are the tags, and the values are boolean.
   *   If the value is TRUE, the tag is required.
   *   If the value is FALSE, the tag is forbidden.
   *
   * @return array<string, mixed>
   *   Source definitions, keyed by source id.
   */
  public function getDefinitionsForPropType(string $prop_type_id, ?array $contexts = [], array $tag_filter = []): array {
    // No useful source plugins can be guessed
    // if the prop type is unknown. Let's return
    // no sources to hide the prop form.
    if ($prop_type_id === 'unknown') {
      return [];
    }
    $definitions = $this->getNativeDefinitionsForPropType($prop_type_id, $contexts, $tag_filter);
    foreach ($definitions as &$definition) {
      $definition["tags"][] = "prop_type_compatibility:native";
    }
    $convertibleDefinitions = $this->getConvertibleDefinitionsForPropType($prop_type_id, $contexts, $tag_filter);
    foreach ($convertibleDefinitions as &$definition) {
      $definition["tags"][] = "prop_type_compatibility:converted";
    }
    return array_merge($definitions, $convertibleDefinitions);
  }

  /**
   * Returns natively compatible source definitions for a prop type.
   *
   * There is also the method getConvertibleDefinitionsForPropType()
   * that returns convertible source definitions.
   *
   * @param string $prop_type_id
   *   The prop type id.
   * @param array|null $contexts
   *   The contexts or null if not using contexts.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *   The array keys are the tags, and the values are boolean.
   *   If the value is TRUE, the tag is required.
   *   If the value is FALSE, the tag is forbidden.
   *
   * @return array<string, mixed>
   *   Source definitions, keyed by source id.
   */
  public function getNativeDefinitionsForPropType(string $prop_type_id, ?array $contexts = [], array $tag_filter = []): array {
    $definitions = $this->getDefinitionsForField($contexts, $tag_filter);
    $narrowed_definitions_for_field = ($definitions !== NULL);
    if (!$narrowed_definitions_for_field) {
      $definitions = $this->getDefinitions();
    }
    // Filter by prop type.
    $definitions = $this->filterDefinitionsByPropType($definitions, $prop_type_id);
    // Filter by context.
    if ($contexts !== NULL) {
      // Even when context is an empty array,
      // we need to filter sources that require missing
      // pieces of context.
      $definitions = $this->getDefinitionsMatchingContextsAndTags($contexts, [], $definitions);
    }
    // Filter by tags.
    if ((count($tag_filter) > 1) || (!$narrowed_definitions_for_field && count($tag_filter) > 0)) {
      // In the case of narrowed definitions for field,
      // field filter has already been checked,
      // so we filter only if other filters are requested.
      $definitions = static::filterDefinitionsByTags($definitions, $tag_filter);
    }
    foreach ($definitions as &$definition) {
      $definition["tags"] = array_merge(array_key_exists("tags", $definition) ? $definition["tags"] : [], ["prop_type_matched:" . $prop_type_id]);
    }
    unset($definition);
    return $definitions;
  }

  /**
   * Filters definitions by prop type.
   *
   * @param array<string, array<string, mixed> > $definitions
   *   The definitions.
   * @param string $prop_type_id
   *   The prop type id.
   *
   * @return array
   *   The filtered definitions.
   */
  protected function filterDefinitionsByPropType(array $definitions, string $prop_type_id): array {
    return array_filter($definitions, static function ($definition) use ($prop_type_id) {
      $supported_prop_types = array_key_exists("prop_types", $definition) ? $definition['prop_types'] : [];
      return !(is_array($supported_prop_types) && (count($supported_prop_types) > 0) && !in_array($prop_type_id, $supported_prop_types));
    });
  }

  /**
   * Returns convertible source definitions for a prop type.
   *
   * There is also the method getNativeDefinitionsForPropType()
   * that returns natively compatible source definitions.
   *
   * @param string $prop_type_id
   *   The prop type id.
   * @param array|null $contexts
   *   The contexts or null if not using contexts.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *   The array keys are the tags, and the values are boolean.
   *   If the value is TRUE, the tag is required.
   *   If the value is FALSE, the tag is forbidden.
   *
   * @return array<string, mixed>
   *   Source definitions, keyed by source id.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getConvertibleDefinitionsForPropType(string $prop_type_id, ?array $contexts = [], array $tag_filter = []): array {
    $definitions = [];
    $convertible_sources_by_prop_id = $this->getConvertibleDefinitionsPerPropertyId($prop_type_id, $contexts, $tag_filter);
    foreach ($convertible_sources_by_prop_id as $convertible_sources) {
      foreach ($convertible_sources as $source_id => $source) {
        $definitions[$source_id] = $source;
      }
    }
    return $definitions;
  }

  /**
   * Source definitions for prop type, by convertible prop id.
   *
   * Internal usage only.
   *
   * @param string $prop_type_id
   *   The prop type id.
   * @param array|null $contexts
   *   The contexts or null if not using contexts.
   * @param array<string, bool>|null $tag_filter
   *   Filter results by tags.
   *   The array keys are the tags, and the values are boolean.
   *   If the value is TRUE, the tag is required.
   *   If the value is FALSE, the tag is forbidden.
   *
   * @return array<string, array<string, mixed>>
   *   Source definitions, keyed by convertible prop id.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getConvertibleDefinitionsPerPropertyId(string $prop_type_id, ?array $contexts = [], ?array $tag_filter = []): array {
    $definitions = [];
    if (!is_array($tag_filter)) {
      $tag_filter = [];
    }
    $tag_filter = array_merge($tag_filter, ["widget:dismissible" => FALSE]);
    $convertible_props = $this->propTypeManager->getConvertibleProps($prop_type_id);
    foreach (array_keys($convertible_props) as $convertible_prop_id) {
      $convertible_sources = $this->getNativeDefinitionsForPropType($convertible_prop_id, $contexts, $tag_filter);
      $definitions[$convertible_prop_id] = $convertible_sources;
    }
    return $definitions;
  }

  /**
   * Returns the default source identifier for a prop type.
   *
   * @param string $prop_type_id
   *   The prop type id.
   * @param array $contexts
   *   The contexts.
   * @param array<string, bool> $tag_filter
   *   Filter results by tags.
   *   The array keys are the tags, and the values are boolean.
   *   If the value is TRUE, the tag is required.
   *   If the value is FALSE, the tag is forbidden.
   *
   * @return string|null
   *   The source plugin identifier.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getPropTypeDefault(string $prop_type_id, array $contexts = [], array $tag_filter = []): ?string {
    // First try with prop type default source.
    $prop_type_definition = $this->propTypeManager->getDefinition($prop_type_id);
    $default_source_id = $prop_type_definition["default_source"] ?? NULL;
    $default_source_applicable = $default_source_id && $this->isApplicable($default_source_id, $contexts);
    if (!$tag_filter && $default_source_applicable) {
      return $default_source_id;
    }
    $definitions = $this->getDefinitionsForPropType($prop_type_id, $contexts, $tag_filter);
    if ($tag_filter && $default_source_applicable && array_key_exists($default_source_id, $definitions)) {
      return $default_source_id;
    }
    $source_ids = array_keys($definitions);
    foreach ($source_ids as $source_id) {
      if ($this->isApplicable($source_id, $contexts)) {
        return $source_id;
      }
    }
    return NULL;
  }

  /**
   * Creates a plugin instances with the same configuration.
   *
   * @param array $plugin_ids
   *   The source plugin identifiers.
   * @param array $configuration
   *   An array of configuration.
   *
   * @return array
   *   A list of fully configured plugin instances.
   */
  public function createInstances(array $plugin_ids, array $configuration): array {
    return array_map(
      function ($plugin_id) use ($configuration) {
        return $this->createInstance($plugin_id, $configuration);
      },
      $plugin_ids,
    );
  }

  /**
   * Check if the source is matching the specified context.
   *
   * @param string $source_id
   *   The source plugin identifier.
   * @param array $contexts
   *   An array of contexts.
   *
   * @return bool
   *   Is the source applicable.
   */
  public function isApplicable(string $source_id, array $contexts): bool {
    // @todo use a method of the plugin instead?
    $definitions = $this->getDefinitionsMatchingContextsAndTags($contexts);
    return isset($definitions[$source_id]);
  }

  /**
   * Get a source plugin Instance.
   *
   * A source instance is always related to a prop or a slot.
   * That's why we pass first the prop or slot id and the associated definition.
   * If definition is empty, the slot will be automatically assumed.
   * The configuration passed is the source configuration.
   * It has a key 'source_id' that is the source plugin identifier.
   * When no source_id is provided,
   * the default source for the prop type is used.
   * The source contexts are the contexts currently in use,
   * maybe needed for that source or not.
   * The form array parents are the form array parents, needed
   * when dealing with the source settingsForm.
   *
   * @param string $prop_or_slot_id
   *   Prop ID or slot ID.
   * @param array $definition
   *   Definition (if empty, slot will be automatically set).
   * @param array $configuration
   *   Configuration for the source.
   * @param array $source_contexts
   *   Source contexts.
   * @param array $form_array_parents
   *   Form array parents.
   *
   * @return \Drupal\ui_patterns\SourceInterface|null
   *   The source found and instantiated or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSource(string $prop_or_slot_id, array $definition, array $configuration, array $source_contexts = [], array $form_array_parents = []) : ?SourceInterface {
    if (empty($definition)) {
      // We consider a slot if no definition is provided.
      $definition = ['ui_patterns' => ['type_definition' => $this->getSlotPropType()]];
    }
    $source_id = $configuration['source_id'] ?? NULL;
    if (!$source_id && isset($definition['ui_patterns']['type_definition'])) {
      $source_id = $this->getPropTypeDefault($definition['ui_patterns']['type_definition']->getPluginId(), $source_contexts);
    }
    if (!$source_id) {
      return NULL;
    }
    /** @var \Drupal\ui_patterns\SourceInterface $source */
    $source = $this->createInstance(
      $source_id,
      SourcePluginBase::buildConfiguration($prop_or_slot_id, $definition, $configuration, $source_contexts, $form_array_parents)
    );
    return $source;
  }

  /**
   * Get the slot prop type.
   *
   * @return \Drupal\ui_patterns\PropTypeInterface
   *   The slot prop type.
   */
  public function getSlotPropType() : PropTypeInterface {
    /** @var \Drupal\ui_patterns\PropTypeInterface $slot_prop_type */
    $slot_prop_type = $this->propTypeManager->createInstance('slot');
    return $slot_prop_type;
  }

}
