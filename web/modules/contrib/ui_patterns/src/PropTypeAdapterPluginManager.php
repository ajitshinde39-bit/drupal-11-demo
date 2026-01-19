<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\ui_patterns\Attribute\PropTypeAdapter;
use Drupal\ui_patterns\SchemaManager\CompatibilityChecker;

/**
 * PropTypeAdapter plugin manager.
 */
final class PropTypeAdapterPluginManager extends DefaultPluginManager implements SchemaGuesserInterface {

  /**
   * Constructs PropTypeAdapterPluginManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\ui_patterns\SchemaManager\CompatibilityChecker $compatibilityChecker
   *   The compatibility checker.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    protected CompatibilityChecker $compatibilityChecker,
  ) {
    parent::__construct('Plugin/UiPatterns/PropTypeAdapter', $namespaces, $module_handler, PropTypeAdapterInterface::class, PropTypeAdapter::class);
    $this->alterInfo('prop_type_adapter_info');
    $this->setCacheBackend($cache_backend, 'prop_type_adapter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function guessFromSchema(array $prop_schema): ?PropTypeAdapterInterface {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      if ($this->compatibilityChecker->isCompatible($prop_schema, $definition['schema'])) {
        /** @var \Drupal\ui_patterns\PropTypeAdapterInterface */
        return $this->createInstance($definition['id'], []);
      }
    }
    return NULL;
  }

}
