<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Template;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;
use Drupal\ui_patterns\PropTypeAdapterPluginManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension providing UI Patterns-specific functionalities.
 *
 * @package Drupal\ui_patterns\Template
 */
class TwigExtension extends AbstractExtension {

  use AttributesFilterTrait;

  /**
   * Creates TwigExtension.
   *
   * @param \Drupal\ui_patterns\ComponentPluginManager $componentManager
   *   The component plugin manager.
   * @param \Drupal\ui_patterns\PropTypeAdapterPluginManager $adapterManager
   *   The prop type adapter plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(
    protected ComponentPluginManager $componentManager,
    protected PropTypeAdapterPluginManager $adapterManager,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return 'ui_patterns';
  }

  /**
   * {@inheritdoc}
   */
  public function getNodeVisitors(): array {
    return [
      new ModuleNodeVisitorBeforeSdc($this->componentManager),
      new ModuleNodeVisitorAfterSdc($this->componentManager),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      // For ComponentNodeVisitorBeforeSdc.
      new TwigFunction('_ui_patterns_normalize_props', [$this, 'normalizeProps'], ['needs_context' => TRUE]),
      // For ComponentNodeVisitorAfterSdc.
      new TwigFunction('_ui_patterns_preprocess_props', [$this, 'preprocessProps'], ['needs_context' => TRUE]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('add_class', [$this, 'addClass']),
      new TwigFilter('set_attribute', [$this, 'setAttribute']),
    ];
  }

  /**
   * Normalize props (and slots).
   *
   * This function must not be used by the templates authors. In a perfect
   * world, it would not be necessary to set such a function. We did that to be
   * compatible with SDC's ComponentNodeVisitor, in order to execute props
   * normalization before SDC's validate_component_props Twig function.
   *
   * See ModuleNodeVisitorBeforeSdc.
   *
   * @param array $context
   *   The context provided to the component.
   * @param string $component_id
   *   The component ID.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function normalizeProps(array &$context, string $component_id): void {
    $component = $this->componentManager->find($component_id);
    $props = $component->metadata->schema['properties'] ?? [];
    foreach ($context as $variable => $value) {
      if (isset($component->metadata->slots[$variable])) {
        $context[$variable] = SlotPropType::normalize($value);
        continue;
      }
      if (!isset($props[$variable]['ui_patterns'])) {
        continue;
      }
      $prop_type = $props[$variable]['ui_patterns']['type_definition'];
      $context[$variable] = $prop_type->normalize($value, $props[$variable]);
      if ($context[$variable] === NULL) {
        unset($context[$variable]);
      }
      if (isset($props[$variable]['ui_patterns']['prop_type_adapter'])) {
        $prop_type_adapter_id = $props[$variable]['ui_patterns']['prop_type_adapter'];
        /** @var \Drupal\ui_patterns\PropTypeAdapterInterface $prop_type_adapter */
        $prop_type_adapter = $this->adapterManager->createInstance($prop_type_adapter_id);
        $context[$variable] = $prop_type_adapter->transform($context[$variable]);
      }
    }
    // Attributes prop must never be empty, to avoid the processing of SDC's
    // ComponentsTwigExtension::mergeAdditionalRenderContext() which is adding
    // an Attribute PHP object before running the validator.
    // Attribute PHP object are casted as string by the validator and trigger
    // '[attributes] String value found, but an object is required' error.
    $context['attributes'] = $context['attributes'] ?? [];
    $context['attributes']['data-component-id'] = $component->getPluginId();
  }

  /**
   * Preprocess props.
   *
   * This function must not be used by the templates authors. In a perfect
   * world, it would not be necessary to set such a function. We did that to be
   * compatible with SDC's ComponentNodeVisitor, in order to execute props
   * preprocessing after SDC's validate_component_props Twig function.
   *
   * See ModuleNodeVisitorAfterSdc.
   *
   * @param array $context
   *   The context provided to the component.
   * @param string $component_id
   *   The component ID.
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
   */
  public function preprocessProps(array &$context, string $component_id): void {
    $component = $this->componentManager->find($component_id);
    $props = $component->metadata->schema['properties'] ?? [];
    foreach ($context as $variable => $value) {
      if (!isset($props[$variable]['ui_patterns'])) {
        continue;
      }
      $prop_type = $props[$variable]['ui_patterns']['type_definition'];
      $context[$variable] = $prop_type->preprocess($value, $props[$variable]);
    }
  }

}
