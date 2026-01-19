<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Component;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\ComponentPluginManager as UiPatternsComponentPluginManager;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;
use Drupal\ui_patterns\PropTypeInterface;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourcePluginManager;
use Psr\Log\LoggerInterface;

/**
 * Component render element builder.
 */
class ComponentElementBuilder implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['build'];
  }

  /**
   * Constructs a ComponentElementBuilder.
   */
  public function __construct(
    protected SourcePluginManager $sourcesManager,
    protected ComponentPluginManager $componentPluginManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Build component data provided to the SDC element.
   */
  public function build(array $element): array {
    if (!isset($element['#ui_patterns']) || !isset($element['#component'])) {
      return $element;
    }
    $this->moduleHandler->alter('ui_patterns_component_pre_build', $element);
    $configuration = $element['#ui_patterns'];
    $contexts = $element['#source_contexts'] ?? [];
    $component = $this->componentPluginManager->find($element['#component']);
    $element = $this->buildProps($element, $component, $configuration, $contexts);
    $element = $this->buildSlots($element, $component, $configuration, $contexts);
    $element['#propsAlter'] = [];
    $element['#slotsAlter'] = [];
    return $element;
  }

  /**
   * Add props to the renderable.
   */
  protected function buildProps(array $build, Component $component, array $configuration, array $contexts): array {
    $props = $component->metadata->schema['properties'] ?? [];
    foreach ($props as $prop_id => $prop_definition) {
      if ($prop_id === 'variant') {
        $prop_configuration = $configuration['variant_id'] ?? [];
      }
      else {
        $prop_configuration = $configuration['props'][$prop_id] ?? [];
      }
      $build = $this->buildProp($build, $prop_id, $prop_definition, $prop_configuration, $contexts);
    }
    return $build;
  }

  /**
   * Add a single prop to the renderable.
   */
  protected function buildProp(array $build, string $prop_id, array $definition, array $configuration, array $source_contexts): array {
    if (isset($build['#props'][$prop_id])) {
      // Keep existing props. No known use case yet.
      return $build;
    }
    return $this->buildSource($build, $prop_id, $definition, $configuration, $source_contexts);
  }

  /**
   * Add data to a prop or a slot.
   */
  protected function addDataToComponent(array &$build, string $prop_or_slot_id, PropTypeInterface $prop_type, mixed $data): void {
    if ($prop_type instanceof SlotPropType) {
      if ($data !== NULL && Element::isRenderArray($data)) {
        if ($this->isSingletonRenderArray($data)) {
          $data = array_values($data)[0];
        }
        $build['#slots'][$prop_or_slot_id][] = $data;
      }
    }
    else {
      if (!static::isPropValueEmpty($data, $prop_type)) {
        $build['#props'][$prop_or_slot_id] = $data;
      }
    }
  }

  /**
   * Determine if a prop value is empty.
   *
   * @param mixed $data
   *   The prop value.
   * @param \Drupal\ui_patterns\PropTypeInterface $prop_type
   *   Target prop type.
   *
   * @return bool
   *   TRUE if the prop value is empty, FALSE otherwise.
   */
  protected static function isPropValueEmpty(mixed $data, PropTypeInterface $prop_type): bool {
    // For JSON Schema validator, empty value is not the same as missing
    // value, and we want to prevent some of the prop types rules to be
    // applied on empty values: string pattern, string format,
    // enum, number min/max...
    // However, we don't remove empty attributes to avoid an error with
    // Drupal\Core\Template\TwigExtension::createAttribute() when themers
    // forget to use the default({}) filter.
    // For boolean values, we only remove NULL values.
    return match ($prop_type->getPluginId()) {
      'attributes' => FALSE,
      'boolean', 'number' => ($data === NULL),
      default => empty($data),
    };
  }

  /**
   * Update the build array for a configured source on a prop/slot.
   *
   * @param array $build
   *   The build array.
   * @param string $prop_or_slot_id
   *   Prop ID or slot ID.
   * @param array $definition
   *   Definition.
   * @param array $configuration
   *   Configuration.
   * @param array $contexts
   *   Source contexts.
   *
   * @return mixed
   *   The updated build array.
   */
  public function buildSource(array $build, string $prop_or_slot_id, array $definition, array $configuration, array $contexts) : mixed {
    try {
      if (empty($configuration['source_id'])) {
        return $build;
      }
      $source = $this->sourcesManager->getSource($prop_or_slot_id, $definition, $configuration, $contexts);
      if (!$source) {
        return $build;
      }
      /** @var \Drupal\ui_patterns\PropTypeInterface $prop_type */
      $prop_type = $source->getPropDefinition()['ui_patterns']['type_definition'];
      // Alter the build array before getting the value.
      $build = $source->alterComponent($build);
      // Get the value from the source.
      $data = $source->getValue($prop_type);
      // Alter the value by hook implementations.
      $this->moduleHandler->alter('ui_patterns_source_value', $data, $source, $configuration);
      $this->addDataToComponent($build, $prop_or_slot_id, $prop_type, $data);
    }
    catch (ContextException $e) {
      // ContextException is thrown when a required context is missing.
      // We don't want to break the render process, so we just ignore the prop.
      $error_message = t("Context error for '@prop_id' in component '@component_id': @message", [
        '@prop_id' => $prop_or_slot_id,
        '@component_id' => $build['#component'] ?? '',
        '@message' => $e->getMessage(),
      ]);
      $this->logger->error($error_message);
    }
    return $build;
  }

  /**
   * Add slots to the renderable.
   */
  protected function buildSlots(array $build, Component $component, array $configuration, array $contexts): array {
    $slots = $component->metadata->slots ?? [];

    foreach ($slots as $slot_id => $slot_definition) {
      $slot_configuration = $configuration['slots'][$slot_id] ?? [];
      $build = $this->buildSlot($build, $slot_id, $slot_definition, $slot_configuration, $contexts);
    }
    return $build;
  }

  /**
   * Add a single slot to the renderable.
   */
  protected function buildSlot(array $build, string $slot_id, array $definition, array $configuration, array $contexts): array {
    if (empty($configuration['sources'])) {
      return $build;
    }

    // Preserve existing slot content if any.
    $original_slot = $build['#slots'][$slot_id] ?? NULL;

    // Initialize slot array.
    $build['#slots'][$slot_id] = [];

    // Process all sources.
    foreach ($configuration['sources'] as $source_configuration) {
      $build = $this->buildSource($build, $slot_id, $definition, $source_configuration, $contexts);
    }

    // Merge original slot content if it exists.
    if ($original_slot !== NULL) {
      $build['#slots'][$slot_id][] = $original_slot;
    }

    // Simplify single-element arrays.
    // Weird hack, we take care of sequences injected in single sub value.
    if ($this->isSingletonRenderArray($build['#slots'][$slot_id]) &&
      count(Element::children($build['#slots'][$slot_id][0])) !== 0
    ) {
      $build['#slots'][$slot_id] = $build['#slots'][$slot_id][0];
    }

    return $build;
  }

  /**
   * Check if the render array is a singleton.
   *
   * @param array $candidate
   *   The render array to check.
   *
   * @return bool
   *   TRUE if the render array is a singleton, FALSE otherwise.
   */
  protected function isSingletonRenderArray(array $candidate): bool {
    if (count($candidate) !== 1) {
      return FALSE;
    }
    $key = array_key_first($candidate);
    return (is_int($key) || ($key === '') || $key[0] !== '#');
  }

  /**
   * Calculate a component dependencies.
   *
   * @param string|null $component_id
   *   Component ID.
   * @param array $configuration
   *   Component Configuration.
   * @param array $contexts
   *   Contexts.
   *
   * @return array
   *   An array of dependencies keyed by the type of dependency.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  public function calculateComponentDependencies(?string $component_id = NULL, array $configuration = [], array $contexts = []) : array {
    $dependencies = [];
    try {
      $component = $this->componentPluginManager->find($component_id ?? $configuration['component_id']);
      if ($this->componentPluginManager instanceof UiPatternsComponentPluginManager) {
        SourcePluginBase::mergeConfigDependencies($dependencies, $this->componentPluginManager->calculateDependencies($component));
      }
      SourcePluginBase::mergeConfigDependencies($dependencies, $this->calculateComponentDependenciesProps($component, $configuration, $contexts));
      SourcePluginBase::mergeConfigDependencies($dependencies, $this->calculateComponentDependenciesSlots($component, $configuration, $contexts));
    }
    catch (\Throwable $exception) {
      // During install mergeConfigDependencies can lead to
      // unexpected configuration states. We can ignore it.
    }
    return $dependencies;
  }

  /**
   * Calculate a component dependencies for props.
   *
   * @param \Drupal\Core\Plugin\Component $component
   *   Component instance.
   * @param array $configuration
   *   Component Configuration.
   * @param array $contexts
   *   Contexts.
   *
   * @return array
   *   An array of dependencies keyed by the type of dependency.
   */
  protected function calculateComponentDependenciesProps(Component $component, array $configuration = [], array $contexts = []) : array {
    $dependencies = [];
    $props = $component->metadata->schema['properties'] ?? [];
    foreach ($props as $prop_id => $definition) {
      if ($prop_id === 'variant') {
        continue;
      }
      if ($source = $this->sourcesManager->getSource($prop_id, $definition, $configuration['props'][$prop_id] ?? [], $contexts)) {
        SourcePluginBase::mergeConfigDependencies($dependencies, $source->calculateDependencies());
      }
    }
    return $dependencies;
  }

  /**
   * Calculate a component dependencies for slots.
   *
   * @param \Drupal\Core\Plugin\Component $component
   *   Component instance.
   * @param array $configuration
   *   Component Configuration.
   * @param array $contexts
   *   Contexts.
   *
   * @return array
   *   An array of dependencies keyed by the type of dependency.
   */
  protected function calculateComponentDependenciesSlots(Component $component, array $configuration = [], array $contexts = []) : array {
    $dependencies = [];
    $slots = $component->metadata->slots ?? [];
    foreach ($slots as $slot_id => $definition) {
      $slot_configuration = $configuration['slots'][$slot_id] ?? [];
      if (!isset($slot_configuration['sources']) || !is_array($slot_configuration['sources'])) {
        continue;
      }
      foreach ($slot_configuration['sources'] as $source_configuration) {
        if ($source = $this->sourcesManager->getSource($slot_id, $definition, $source_configuration, $contexts)) {
          SourcePluginBase::mergeConfigDependencies($dependencies, $source->calculateDependencies());
        }
      }
    }
    return $dependencies;
  }

}
