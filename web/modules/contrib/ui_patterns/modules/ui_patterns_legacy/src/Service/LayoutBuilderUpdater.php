<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy\Service;

use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\layout_builder\Section;
use Drupal\ui_patterns\PropTypeInterface;

/**
 * Service to update Layout Builder sections using ui_patterns.
 */
class LayoutBuilderUpdater implements LayoutBuilderUpdaterInterface {

  public function __construct(
    protected ComponentPluginManager $componentPluginManager,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings("PHPMD.CyclomaticComplexity")
   */
  public function updateLayout(Section $section): FALSE|Section {
    $layoutId = $section->getLayoutId();

    if (!\str_starts_with($layoutId, $this::PATTERN_PREFIX)) {
      return FALSE;
    }

    $patternId = \substr($layoutId, \strlen($this::PATTERN_PREFIX));
    if (!$patternId) {
      return FALSE;
    }

    $componentId = $this->getNamespacedId($patternId);
    // No matching component found.
    // @todo Should we remove the section?
    if ($patternId == $componentId) {
      return FALSE;
    }

    // No method to get all third party settings directly.
    $thirdPartySettings = [];
    foreach ($section->getThirdPartyProviders() as $provider) {
      $thirdPartySettings[$provider] = $section->getThirdPartySettings($provider);
    }

    // Not possible to change the section plugin ID, need to create a new one.
    $newSection = new Section(
      $this::COMPONENT_PREFIX . $componentId,
      [],
      $section->getComponents(),
      $thirdPartySettings,
    );

    $layoutSettings = $section->getLayoutSettings();
    if (!isset($layoutSettings['pattern']) || !\is_array($layoutSettings['pattern'])) {
      return $newSection;
    }

    // Prepare settings in new format.
    $layoutSettings['ui_patterns'] = [
      'component_id' => $componentId,
      'variant_id' => NULL,
      'slots' => [],
      'props' => [
        'attributes' => [
          'source_id' => 'attributes',
          'source' => [
            'value' => '',
          ],
        ],
      ],
    ];

    /** @var array $component */
    $component = $this->componentPluginManager->getDefinition($componentId);
    foreach ($component['props']['properties'] as $propKey => $prop) {
      $propType = $component['props']['properties'][$propKey]['ui_patterns']['type_definition'];

      if ($propKey == 'variant') {
        $layoutSettings['ui_patterns']['variant_id'] = $this->convertProp(
          (string) ($layoutSettings['pattern']['variant'] ?? ''),
          NULL,
          $propType
        );
      }
      elseif (\array_key_exists($propKey, $layoutSettings['pattern']['settings'] ?? [])) {
        $layoutSettings['ui_patterns']['props'][$propKey] = $this->convertProp(
          (string) $layoutSettings['pattern']['settings'][$propKey],
          (string) ($layoutSettings['pattern']['settings'][$propKey . '_token'] ?? ''),
          $propType
        );
      }
    }

    unset($layoutSettings['pattern']);
    $newSection->setLayoutSettings($layoutSettings);
    return $newSection;
  }

  /**
   * Convert old setting configuration to new one.
   *
   * @code YAML output for standard variant
   * variant_id:
   *   source_id: attributes|checkbox|checkboxes|menu|breadcrumb|textfield|number|radios|select|url|path
   *   source:
   *     value: !string
   *
   * @endcode
   *
   *  @code YAML output for tokenized variant
   *  variant_id:
   *    source_id: token
   *    source:
   *      value: !string
   *
   * @endcode
   */
  protected function convertProp(string $value, ?string $tokenValue, ?PropTypeInterface $propType): array {
    $settings = [
      'source_id' => $propType?->getDefaultSourceId() ?? 'select',
      'source' => ['value' => $value],
    ];
    if (!empty($tokenValue)) {
      $settings['source_id'] = 'token';
      $settings['source']['value'] = $tokenValue;
    }
    return $settings;
  }

  /**
   * Get namespaced (SDC style) component ID from UI Patterns 1.x ID.
   *
   * @param string $componentId
   *   The component ID.
   *
   * @return string
   *   The UIP2 component namespace.
   */
  protected function getNamespacedId(string $componentId): string {
    $parts = \explode(':', $componentId);
    if (\count(\array_filter($parts)) === $this::COMPONENT_NAMESPACE_PARTS) {
      // Already namespaced.
      return $componentId;
    }
    if (\count(\array_filter($parts)) > $this::COMPONENT_NAMESPACE_PARTS) {
      // Unexpected situation.
      return $componentId;
    }
    $components = $this->componentPluginManager->getAllComponents();

    // Return the first found result.
    foreach ($components as $component) {
      $definition = $component->getPluginDefinition();
      $machine_name = \is_array($definition) ? $definition['machineName'] : ($definition->machineName ?? NULL);
      if ($machine_name === $componentId) {
        return $component->getPluginId();
      }
    }
    return $componentId;
  }

}
