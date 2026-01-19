<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\CategorizingPluginManagerInterface;
use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Plugin\CategorizingPluginManagerTrait;
use Drupal\Core\Plugin\Component;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Theme\Component\SchemaCompatibilityChecker;
use Drupal\Core\Theme\ComponentNegotiator;
use Drupal\Core\Theme\ComponentPluginManager as CoreComponentPluginManager;
use Drupal\Core\Theme\ComponentPluginManager as SdcPluginManager;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\ui_patterns\SchemaManager\ReferencesResolver;

/**
 * UI Patterns extension of SDC component plugin manager.
 */
class ComponentPluginManager extends SdcPluginManager implements CategorizingPluginManagerInterface {

  use CategorizingPluginManagerTrait;

  /**
   * The prop type plugin manager.
   */
  protected PropTypePluginManager $propTypePluginManager;

  /**
   * The prop type adapter plugin manager.
   */
  protected PropTypeAdapterPluginManager $propTypeAdapterPluginManager;

  /**
   * The reference resolver.
   */
  protected CoreComponentPluginManager $componentPluginManager;

  /**
   * The reference resolver.
   */
  protected ReferencesResolver $referencesSolver;

  /**
   * Constructs ComponentPluginManager object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cacheBackend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $themeManager
   *   The theme manager.
   * @param \Drupal\Core\Theme\ComponentNegotiator $componentNegotiator
   *   The component negotiator.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system service.
   * @param \Drupal\Core\Theme\Component\SchemaCompatibilityChecker $compatibilityChecker
   *   The compatibility checker.
   * @param \Drupal\Core\Theme\Component\ComponentValidator $componentValidator
   *   The component validator.
   * @param string $appRoot
   *   The application root.
   */
  public function __construct(
    ModuleHandlerInterface $module_handler,
    ThemeHandlerInterface $themeHandler,
    CacheBackendInterface $cacheBackend,
    ConfigFactoryInterface $configFactory,
    ThemeManagerInterface $themeManager,
    ComponentNegotiator $componentNegotiator,
    FileSystemInterface $fileSystem,
    SchemaCompatibilityChecker $compatibilityChecker,
    ComponentValidator $componentValidator,
    string $appRoot,
  ) {
    parent::__construct(
      $module_handler,
      $themeHandler,
      $cacheBackend,
      $configFactory,
      $themeManager,
      $componentNegotiator,
      $fileSystem,
      $compatibilityChecker,
      $componentValidator,
      $appRoot);
    $this->alterInfo('component_info');
    $this->setCacheBackend($cacheBackend, 'component_plugins');
  }

  /**
   * Sets the prop type plugin manager.
   */
  public function setPropTypePluginManager(PropTypePluginManager $propTypePluginManager): void {
    $this->propTypePluginManager = $propTypePluginManager;
  }

  /**
   * Sets the prop type adapter plugin manager.
   */
  public function setPropTypePluginAdapter(PropTypeAdapterPluginManager $propTypeAdapterPluginManager): void {
    $this->propTypeAdapterPluginManager = $propTypeAdapterPluginManager;
  }

  /**
   * Sets reference resolver.
   */
  public function setReferenceSolver(ReferencesResolver $referencesSolver): void {
    $this->referencesSolver = $referencesSolver;
  }

  /**
   * Sets module extension list.
   */
  public function setModuleExtensionList(ModuleExtensionList $moduleExtensionList): void {
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * Correct SDC Component definition.
   *
   * @param array $definition
   *   The definition to clean.
   */
  protected function cleanDefinition(array &$definition): void {
    // Name is mandatory, so this precaution should never happen. But we have
    // seen SDC without name property in the wild.
    if (!isset($definition['name'])) {
      $definition['name'] = explode(':', $definition['id'])[1];
    }
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  public function processDefinition(&$definition, $plugin_id): void {
    parent::processDefinition($definition, $plugin_id);
    $this->cleanDefinition($definition);
    $this->processDefinitionCategory($definition);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore-next-line
   */
  protected function processDefinitionCategory(&$definition): void {
    // 'label' and 'category' are expected by CategorizingPluginManagerTrait.
    $this->cleanDefinition($definition);
    $definition['label'] = $definition['name'];
    $definition['category'] = $definition['group'] ?? $this->t('Other');
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinition(array $definition): array {
    // Overriding SDC alterDefinition method.
    $definition = parent::alterDefinition($definition);
    // Adding custom logic.
    $fallback_prop_type_id = $this->propTypePluginManager->getFallbackPluginId("");
    $definition = $this->alterLinks($definition);
    $definition = $this->alterSlots($definition);
    $definition = $this->annotateSlots($definition);
    $definition = $this->annotateProps($definition, $fallback_prop_type_id);
    return $definition;
  }

  /**
   * Alter links.
   */
  protected function alterLinks(array $definition): array {
    if (!isset($definition['links'])) {
      return $definition;
    }
    // Resolve the short notation.
    foreach ($definition['links'] as $delta => $link) {
      if (is_array($link)) {
        continue;
      }
      $definition['links'][$delta] = [
        "url" => (string) $link,
      ];
    }
    return $definition;
  }

  /**
   * Alter slots.
   */
  protected function alterSlots(array $definition): array {
    if (!isset($definition['slots'])) {
      return $definition;
    }
    // Prevent slots without title from breaking.
    foreach ($definition['slots'] as $slot_id => $slot) {
      $definition['slots'][$slot_id]["title"] = $slot["title"] ?? $slot_id;
    }
    return $definition;
  }

  /**
   * Annotate each slot in a component definition.
   */
  protected function annotateSlots(array $definition): array {
    if (empty($definition['slots'])) {
      return $definition;
    }
    $slot_prop_type = $this->propTypePluginManager->createInstance('slot', []);
    foreach ($definition['slots'] as $slot_id => $slot) {
      $slot['ui_patterns']['type_definition'] = $slot_prop_type;
      $definition['slots'][$slot_id] = $slot;
    }
    return $definition;
  }

  /**
   * Annotate each prop in a component definition.
   *
   * This is the main purpose of overriding SDC component plugin manager.
   * We add a 'ui_patterns' object in each prop schema of the definition.
   */
  protected function annotateProps(array $definition, string $fallback_prop_type_id): array {
    // In JSON schema, 'required' is out of the prop definition.
    if (isset($definition['props']['required'])) {
      foreach ($definition['props']['required'] as $prop_id) {
        $definition['props']['properties'][$prop_id]['ui_patterns']['required'] = TRUE;
      }
    }
    if (isset($definition["variants"])) {
      $definition['props']['properties']['variant'] = $this->buildVariantProp($definition);
    }
    $definition['props']['properties'] = $this->addAttributesProp($definition);
    foreach ($definition['props']['properties'] as $prop_id => $prop) {
      $definition['props']['properties'][$prop_id] = $this->annotateProp($prop_id, $prop, $fallback_prop_type_id);
    }
    return $definition;
  }

  /**
   * Annotate a single prop.
   */
  protected function annotateProp(string $prop_id, array $prop, string $fallback_prop_type_id): array {
    $prop["title"] = $prop["title"] ?? $prop_id;

    $this->resolveJsonSchemaReference($prop);
    $prop_type = $this->propTypePluginManager->guessFromSchema($prop);
    if ($prop_type->getPluginId() === $fallback_prop_type_id) {
      // Sometimes, a prop JSON schema is different enough to not be caught by
      // the compatibility checker, but close enough to address the same
      // sources as an existing prop type with only some small unidirectional
      // transformation of the data. So, we need an adapter plugin.
      $prop_type_adapter = $this->propTypeAdapterPluginManager->guessFromSchema($prop);
      if ($prop_type_adapter) {
        $prop_type_id = $prop_type_adapter->getPropTypeId();
        $prop_type = $this->propTypePluginManager->createInstance($prop_type_id);
        $prop['ui_patterns']['prop_type_adapter'] = $prop_type_adapter->getPluginId();
      }
    }
    if (isset($prop['$ref']) && str_starts_with($prop['$ref'], "ui-patterns://")) {
      // Resolve prop schema here, because:
      // - Drupal\Core\Theme\Component\ComponentValidator::getClassProps() is
      //   executed before schema references are resolved, so SDC believe
      //   a reference is a PHP namespace.
      // - It is not possible to propose a patch to SDC because
      //   SchemaStorage::resolveRefSchema() is not recursively resolving
      //   the schemas anyway.
      $prop = $this->referencesSolver->resolve($prop);
    }
    $prop['ui_patterns']['type_definition'] = $prop_type;
    $prop['ui_patterns']["summary"] = ($prop_type instanceof PropTypeInterface) ? $prop_type->getSummary($prop) : "";
    return $prop;
  }

  /**
   * Resolve a JSON schema reference.
   */
  protected function resolveJsonSchemaReference(array &$prop) : void {
    if (isset($prop['$ref']) && str_starts_with($prop['$ref'], "ui-patterns://") === FALSE) {
      // We need to resolve non ui-patterns before guessFromSchema.
      // To load refs including "ui-patterns" leads to wrong type mapping.
      // So we load ui patterns refs in a second step.
      // @todo improve error handling and logging?
      $prop = $this->referencesSolver->resolve($prop);
    }
  }

  /**
   * Add attributes prop.
   *
   * 'attribute' is one of the 2 'magic' props: its name and type are already
   * set. Always available because automatically added by
   * ComponentsTwigExtension::mergeAdditionalRenderContext().
   */
  private function addAttributesProp(array $definition): array {
    // Let's put it at the beginning (for forms).
    return array_merge(
     [
       'attributes' => [
         'title' => 'Attributes',
         '$ref' => "ui-patterns://attributes",
       ],
     ],
      $definition['props']['properties'] ?? [],
    );
  }

  /**
   * Build variant prop.
   *
   * 'variant' is one of the 2 'magic' props: its name and type are already set.
   * Available if at least a variant is set in the component definition.
   */
  private function buildVariantProp(array $definition): array {
    $enums = [];
    $meta_enums = [];
    foreach ($definition["variants"] as $variant_id => $variant) {
      $enums[] = $variant_id;
      $meta_enums[$variant_id] = $variant['title'] ?? $variant_id;
    }
    return [
      'title' => 'Variant',
      '$ref' => "ui-patterns://variant",
      'enum' => $enums,
      'meta:enum' => $meta_enums,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = parent::findDefinitions();
    // Add annotated_name property to distinct components with the same name.
    $labels = array_column($definitions, "name");
    $duplicate_labels = array_unique(array_intersect($labels, array_unique(array_diff_key($labels, array_unique($labels)))));
    foreach ($definitions as $id => $definition) {
      $definitions[$id]["annotated_name"] = $this->getAnnotatedLabel($definition, $duplicate_labels);
    }
    return $definitions;
  }

  /**
   * Add annotation to label when many components share the same name.
   */
  protected function getAnnotatedLabel(array $definition, array $duplicate_labels): string {
    $label = $definition['name'] ?? $definition['machineName'];
    if (!in_array($label, $duplicate_labels)) {
      return $label;
    }
    if (!isset($definition['provider'])) {
      return $label;
    }
    return $label . " (" . $this->getExtensionLabel($definition['provider']) . ")";
  }

  /**
   * Get the extension (module or theme) label.
   */
  protected function getExtensionLabel(string $extension): string {
    if ($this->moduleHandler->moduleExists($extension)) {
      return $this->moduleExtensionList->getName($extension);
    }
    if ($this->themeHandler->themeExists($extension)) {
      return $this->themeHandler->getTheme($extension)->info['name'];
    }
    return $extension;
  }

  /**
   * Calculate dependencies of a component.
   *
   * @param \Drupal\Core\Plugin\Component $component
   *   The component.
   *
   * @return array
   *   Config Dependencies.
   */
  public function calculateDependencies(Component $component) : array {
    $definition = $component->getPluginDefinition();
    $provider = ($definition instanceof PluginDefinitionInterface) ? $definition->getProvider() : (string) ($definition["provider"] ?? '');
    $extension_type = $this->getExtensionType($provider);
    return (empty($provider) || empty($extension_type)) ? [] : [$extension_type => [$provider]];
  }

  /**
   * Returns the negotiated (replaces) definition.
   */
  public function negotiateDefinition(string $component_id): array {
    $negotiated_component_id = $this->componentNegotiator->negotiate($component_id, $this->getDefinitions());
    $found_id = $negotiated_component_id ?? $component_id;
    $original_definition = $this->getDefinition($component_id, FALSE);
    $definition = $this->getDefinition($found_id, FALSE);
    if ($found_id !== $component_id) {
      // We need to reset id machineName and provider to the original values
      // because the replaced component must behave like the original.
      $definition['replaced_by'] = $definition['id'];
      $definition['id'] = $component_id;
      unset($definition['replaces']);
      $definition['machineName'] = $original_definition['machineName'];
      $definition['provider'] = $original_definition['provider'];
      if (isset($original_definition['noUi'])) {
        $definition['noUi'] = $original_definition['noUi'];
      }
    }
    return $definition;
  }

  /**
   * Determine if a definition is hidden.
   *
   * @param array $definition
   *   The definition to check.
   * @param bool $include_replaces
   *   Whether to include definitions that replace others.
   *
   * @return bool
   *   TRUE if the definition is hidden, FALSE otherwise.
   */
  protected function isHiddenDefinition(array $definition, bool $include_replaces = FALSE): bool {
    if (!empty($definition['replaces']) && $include_replaces === FALSE) {
      return TRUE;
    }
    return $definition['noUi'] ?? FALSE;
  }

  /**
   * Returns the negotiated sorted definitions.
   */
  public function getNegotiatedSortedDefinitions(?array $definitions = NULL, string $label_key = 'label', bool $include_replaces = FALSE): array {
    $definitions = $this->getSortedDefinitions($definitions, $label_key);
    $negotiated_definitions = [];
    foreach ($definitions as $id => $definition) {
      $negotiated_definition = $this->negotiateDefinition($id);

      if ($this->isHiddenDefinition($negotiated_definition, $include_replaces)) {
        continue;
      }
      $negotiated_definitions[$id] = $negotiated_definition;
      if (!empty($definition['replaces']) && $include_replaces === TRUE) {
        $suffix = ' (don\'t use. Use: ' . $definition['replaces'] . ')';
        if ($negotiated_definitions[$id]['annotated_name']) {
          $negotiated_definitions[$id]['annotated_name'] .= $suffix;
        }
      }
    }
    return $negotiated_definitions;
  }

  /**
   * Returns the negotiated sorted grouped definitions.
   */
  public function getNegotiatedGroupedDefinitions(?array $definitions = NULL, string $label_key = 'label', bool $include_replaces = FALSE): array {
    $definitions = $this->getNegotiatedSortedDefinitions($definitions ?? $this->getDefinitions(), $label_key, $include_replaces);
    $grouped_definitions = [];
    foreach ($definitions as $id => $definition) {
      $grouped_definitions[(string) $definition['category']][$id] = $definition;
    }
    return $grouped_definitions;
  }

  /**
   * Get extension type (theme or module).
   */
  protected function getExtensionType(string $extension): string {
    if ($this->moduleHandler->moduleExists($extension)) {
      return 'module';
    }
    if ($this->themeHandler->themeExists($extension)) {
      return 'theme';
    }
    return '';
  }

}
