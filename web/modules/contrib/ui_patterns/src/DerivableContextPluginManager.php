<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerInterface;
use Drupal\Core\Plugin\Context\ContextAwarePluginManagerTrait;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ui_patterns\Attribute\DerivableContext;
use Drupal\ui_patterns\Plugin\Context\RequirementsContextDefinition;

/**
 * Derivable Context plugin manager.
 */
class DerivableContextPluginManager extends DefaultPluginManager implements ContextAwarePluginManagerInterface, ContextMatcherPluginManagerInterface {

  use ContextAwarePluginManagerTrait;
  use ContextMatcherPluginManagerTrait;

  /**
   * Constructs the object.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct(
      'Plugin/UiPatterns/DerivableContext',
      $namespaces,
      $module_handler,
      DerivableContextInterface::class,
      DerivableContext::class
    );
    $this->alterInfo('derivable_context_info');
    $this->setCacheBackend($cache_backend, 'ui_patterns_derivable_contexts_plugins', $this->getInvalidationCacheTags());
  }

  /**
   * Refines source plugin definition.
   *
   * It allows for example, to add new context definitions
   * to the plugin definition, using a static method inside sources plugins.
   * Very useful for views for example, where each views plugin would
   * declare a required context with a view.
   *
   * @param array $definition
   *   Plugin definition to process.
   * @param string $plugin_id
   *   Plugin Id.
   */
  public function processDefinition(&$definition, $plugin_id) : void {
    parent::processDefinition($definition, $plugin_id);
    if (array_key_exists("context_requirements", $definition) && count($definition["context_requirements"]) > 0) {
      $definition["context_definitions"]["context_requirements"] = RequirementsContextDefinition::fromRequirements($definition["context_requirements"]);
    }
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

}
