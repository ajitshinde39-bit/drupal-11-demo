<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\PropTypeInterface;

/**
 * Configuration converter.
 */
class ConfigurationConverter {

  /**
   * Mapping between 1.x modules and 2.x ones.
   */
  public const LEGACY_MODULES_MAPPING = [
    'ui_patterns_ds' => 'ui_patterns_ds',
    'ui_patterns_entity_links' => 'ui_patterns',
    'ui_patterns_field_formatters' => 'ui_patterns_field_formatters',
    'ui_patterns_field_group' => 'ui_patterns_field_group',
    'ui_patterns_layouts' => 'ui_patterns_layouts',
    'ui_patterns_library' => NULL,
    'ui_patterns_pattern_block' => 'ui_patterns_blocks',
    'ui_patterns_settings' => NULL,
    'ui_patterns_views_style' => 'ui_patterns_views',
    'ui_patterns_views' => 'ui_patterns_views',
  ];

  public function __construct(
    protected RenderableConverter $renderableConverter,
    protected ComponentPluginManager $componentPluginManager,
    protected ModuleHandlerInterface $moduleHandler,
    protected ThemeHandlerInterface $themeHandler,
    protected EntityFieldManagerInterface $entityFieldManager,
  ) {}

  /**
   * Convert configuration definition.
   */
  public function convert(array $source): array {
    $target = $source;
    $target = $this->convertFieldLayout($target);
    $target = $this->convertDisplaySuite($target);
    $target = $this->convertFieldGroup($target);
    $target = $this->convertLayoutBuilder($target);
    $target = $this->convertFieldFormatters($target);
    $target = $this->convertViewsStyle($target);
    return $this->convertViewsRow($target);
  }

  /**
   * Convert field layout related configuration.
   *
   * @code YAML input
   * third_party_settings:
   *   field_layout:
   *     id: !string
   *     settings:
   *       label: ''
   *       pattern:
   *         field_templates: !string
   *         variant: !string
   *         variant_token: !string
   *         settings: !object
   *
   * @endcode
   *
   * @code YAML expected output
   * third_party_settings:
   *   field_layout:
   *     id: !string
   *     settings:
   *       label: ''
   *       ui_patterns:
   *         component_id: !string
   *         variant_id: !object
   *         props: !object
   *         slots: {  }
   *
   * @endcode
   */
  protected function convertFieldLayout(array $target): array {
    if (empty($target['third_party_settings']['field_layout']['settings']['pattern'])) {
      return $target;
    }

    $legacyPatternId = $target['third_party_settings']['field_layout']['id'];
    if (!\str_starts_with($legacyPatternId, 'pattern_')) {
      return $target;
    }

    $legacyPatternId = \str_replace('pattern_', '', $legacyPatternId);
    $legacyPatternSettings = $target['third_party_settings']['field_layout']['settings']['pattern'];

    $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);

    $target['third_party_settings']['field_layout']['id'] = 'ui_patterns:' . $componentId;
    unset($target['third_party_settings']['field_layout']['settings']['pattern']);
    $target['third_party_settings']['field_layout']['settings']['ui_patterns'] = [
      'component_id' => $componentId,
      'variant_id' => $this->convertVariant($legacyPatternSettings['variant'] ?? '', $legacyPatternSettings['variant_token'] ?? NULL),
      'props' => $this->convertSettingsToProps($legacyPatternSettings['settings'] ?? [], $componentId),
      // Slots are always empty in this case because mapping is done at the
      // field level.
      'slots' => [],
    ];

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_layouts', [$componentId]);
    return $target;
  }

  /**
   * Convert display suite related configuration.
   */
  protected function convertDisplaySuite(array $target): array {
    if (empty($target['third_party_settings']['ds']['layout']['settings']['pattern'])) {
      return $target;
    }

    $legacyPatternId = $target['third_party_settings']['ds']['layout']['id'];
    if (!\str_starts_with($legacyPatternId, 'pattern_')) {
      return $target;
    }

    $legacyPatternId = \str_replace('pattern_', '', $legacyPatternId);
    $legacyPatternSettings = $target['third_party_settings']['ds']['layout']['settings']['pattern'];

    $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);

    $target['third_party_settings']['ds']['layout']['id'] = 'ui_patterns:' . $componentId;
    unset($target['third_party_settings']['ds']['layout']['settings']['pattern']);
    $target['third_party_settings']['ds']['layout']['settings']['label'] = '';
    $target['third_party_settings']['ds']['layout']['settings']['ui_patterns'] = [
      'component_id' => $componentId,
      'variant_id' => $this->convertVariant($legacyPatternSettings['variant'] ?? '', $legacyPatternSettings['variant_token'] ?? NULL),
      'props' => $this->convertSettingsToProps($legacyPatternSettings['settings'] ?? [], $componentId),
      // Slots are always empty in this case because mapping is done at the
      // regions level.
      'slots' => [],
    ];

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_layouts', [$componentId]);
    return $target;
  }

  /**
   * Convert field group related configuration.
   *
   * @code YAML input
   * third_party_settings:
   *   field_group:
   *     !group_name:
   *       # ...
   *       format_type: pattern_formatter
   *       format_settings:
   *         pattern: !string
   *         pattern_mapping: !object
   *           !source_name:
   *             destination: !string
   *             weight: !int
   *             plugin: !string
   *             source: !string
   *         pattern_settings: !object
   *         show_empty_fields: !bool
   *         pattern_variant: !string
   *
   * @endcode
   *
   * @code YAML expected output
   * third_party_settings:
   *   field_group:
   *     !group_name:
   *       # ...
   *       format_type: component_formatter
   *       format_settings:
   *         ui_patterns: !object
   *           component_id: !string
   *           variant_id: !object
   *           props: !object
   *           slots: !object
   *         show_empty_fields: !bool
   *         label_as_html: !bool
   *
   * @endcode
   */
  protected function convertFieldGroup(array $target): array {
    if (empty($target['third_party_settings']['field_group'])) {
      return $target;
    }

    $context = [];
    $context['usage'] = 'field_group';

    $componentsIds = [];
    foreach ($target['third_party_settings']['field_group'] as &$field_group) {
      if ($field_group['format_type'] !== 'pattern_formatter') {
        continue;
      }

      $field_group['format_type'] = 'component_formatter';
      $legacySettings = $field_group['format_settings'];
      $legacyPatternId = $legacySettings['pattern'];
      $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);
      $componentsIds[] = $componentId;

      // Keep settings unrelated to UI Patterns.
      $newSettings = \array_diff_key($legacySettings, \array_flip([
        'pattern',
        'pattern_variant',
        'pattern_mapping',
        'pattern_settings',
        'variants',
        'variants_token',
      ]));
      // Build new UI Patterns settings.
      $newSettings['ui_patterns'] = [
        'component_id' => $componentId,
        'variant_id' => $this->convertVariant($field_group['format_settings']['pattern_variant'] ?? '', $field_group['format_settings']['variants_token'][$legacyPatternId] ?? NULL),
        'props' => $this->convertSettingsToProps($field_group['format_settings']['pattern_settings'][$legacyPatternId], $componentId),
        'slots' => $this->convertMappingToSlots($field_group['format_settings']['pattern_mapping'], $context),
      ];
      $field_group['format_settings'] = $newSettings;
    }

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_field_group', $componentsIds);
    return $target;
  }

  /**
   * Convert layout builder related configuration.
   */
  protected function convertLayoutBuilder(array $target): array {
    if (!isset($target['third_party_settings']['layout_builder']['sections']) || empty($target['third_party_settings']['layout_builder']['sections'])) {
      return $target;
    }

    foreach ($target['third_party_settings']['layout_builder']['sections'] as &$section) {
      // Section components.
      $this->convertSectionComponents($target, $section);

      // Section.
      if (!\str_starts_with($section['layout_id'], 'pattern_')) {
        continue;
      }

      $legacyPatternId = \str_replace('pattern_', '', $section['layout_id']);
      $legacyPatternSettings = $section['layout_settings']['pattern'];

      $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);

      $section['layout_id'] = 'ui_patterns:' . $componentId;
      unset($section['layout_settings']['pattern']);
      $section['layout_settings']['ui_patterns'] = [
        'component_id' => $componentId,
        'variant_id' => $this->convertVariant($legacyPatternSettings['variant'] ?? '', $legacyPatternSettings['variant_token'] ?? NULL),
        'props' => $this->convertSettingsToProps($legacyPatternSettings['settings'] ?? [], $componentId),
        // Slots are always empty in this case because mapping is done at the
        // component level.
        'slots' => [],
      ];

      $section['layout_settings']['context_mapping'] = $section['layout_settings']['context_mapping'] ?? [];
      $section['layout_settings']['context_mapping']['entity'] = 'layout_builder.entity';

      $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_layouts', [$componentId]);
    }

    return $target;
  }

  /**
   * Convert section components.
   */
  protected function convertSectionComponents(array $target, array &$section): void {
    if (isset($section['components'])) {
      foreach ($section['components'] as &$component) {
        // UI Patterns Pattern Block.
        if (isset($component['configuration']['provider']) && $component['configuration']['provider'] == 'ui_patterns_pattern_block') {
          $legacyPatternId = \str_replace('pattern_block:', '', $component['configuration']['id']);
          $legacyPatternSettings = $component['configuration']['pattern'];

          $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);

          $component['configuration']['id'] = 'ui_patterns_entity:' . $componentId;
          $component['configuration']['provider'] = 'ui_patterns_blocks';
          $component['configuration']['context_mapping']['entity'] = 'layout_builder.entity';
          unset($component['configuration']['pattern']);
          $component['configuration']['ui_patterns'] = [
            'component_id' => $componentId,
            'variant_id' => $this->convertVariant($legacyPatternSettings['variant'] ?? '', $legacyPatternSettings['variant_token'] ?? NULL),
            'props' => $this->convertSettingsToProps($legacyPatternSettings['settings'] ?? [], $componentId),
            // Slots are always empty in this case because
            // ui_patterns_pattern_block only dealt with settings.
            'slots' => [],
          ];

          $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_pattern_block', [$componentId]);
        }

        // Field blocks with UI Patterns field formatter.
        if (isset($component['configuration']['formatter'])) {
          $field_config = &$component['configuration']['formatter'];
          $componentsIds = [];
          $context = [];

          $parsedID = explode(':', $component["configuration"]["id"]);
          if ($parsedID[0] == 'field_block') {
            $context['entity_type_id'] = $parsedID[1];
            $context['entity_bundle'] = $parsedID[2];
            $context['field_name'] = $parsedID[3];
          }

          $this->convertFieldFormatter($field_config, $componentsIds, $context);
          $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_field_formatters', $componentsIds);
        }
      }
    }
  }

  /**
   * Convert field formatters related configuration.
   *
   * @code YAML input
   *  content:
   *    !field_name:
   *      type: pattern_all_formatter|pattern_each_formatter
   *      label: !string
   *      settings:
   *        type: !string
   *        settings: !object
   *        pattern: !string
   *        pattern_variant: !string
   *        pattern_mapping: !object
   *          !source_name:
   *            weight: !int
   *            destination: !string
   *            plugin: !string
   *            source: !string
   *        pattern_settings: !object
   *        variants_token: !object
   *      third_party_settings: {  }
   *      weight: !int
   *      region: !string
   *
   * @endcode
   *
   * @code YAML expected output
   *  content:
   *    !field_name:
   *      type: ui_patterns_component|ui_patterns_component_per_item
   *      label: !string
   *      settings:
   *        ui_patterns:
   *          component_id: !string
   *          variant_id: !string
   *          props: !object
   *            !prop_name:
   *              source_id: !string
   *              source: !object
   *          slots: !object
   *            !slot_name: !object
   *              sources: !array
   *                -
   *                  source_id: !string
   *                  source: !object
   *                    type: !string
   *                    settings: !object
   *                  _weight: !int
   *        third_party_settings: !object
   *        weight: !int
   *        region: !string
   *
   * @endcode
   */
  protected function convertFieldFormatters(array $target): array {
    if (empty($target['content'])) {
      return $target;
    }

    $componentsIds = [];
    foreach ($target['content'] as $field_machine_name => &$field_config) {
      if (!isset($field_config['type'])) {
        continue;
      }

      $context = [
        'field_name' => $field_machine_name,
      ];
      if (isset($target['targetEntityType'])) {
        $context['entity_type_id'] = $target['targetEntityType'];
      }
      if (isset($target['bundle'])) {
        $context['entity_bundle'] = $target['bundle'];
      }

      $this->convertFieldFormatter($field_config, $componentsIds, $context);
    }

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_field_formatters', $componentsIds);
    return $target;
  }

  /**
   * Convert field formatter settings.
   */
  protected function convertFieldFormatter(array &$fieldConfig, array &$componentsIds, array $context = []): void {
    $newType = match ($fieldConfig['type']) {
      'pattern_all_formatter' => 'ui_patterns_component',
      'pattern_each_formatter' => 'ui_patterns_component_per_item',
      default => NULL,
    };

    // If cardinality is 1, not possible to get 'ui_patterns_component'.
    if ($newType == 'ui_patterns_component'
      && isset($context['entity_type_id'], $context['field_name'])
    ) {
      $definitions = $this->entityFieldManager->getFieldStorageDefinitions($context['entity_type_id']);
      if (isset($definitions[$context['field_name']]) && $definitions[$context['field_name']]->getCardinality() == 1) {
        $newType = 'ui_patterns_component_per_item';
      }
    }

    if ($newType === NULL) {
      return;
    }
    $context['usage'] = 'field_formatter';
    $fieldConfig['type'] = $newType;

    $legacySettings = $fieldConfig['settings'];
    $legacyPatternId = $legacySettings['pattern'];
    $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);
    $componentsIds[] = $componentId;

    // Move sub formatter settings to the appropriate mapping to prepare for
    // conversion.
    if (!empty($legacySettings['pattern_mapping']['field_meta_properties:_formatted'])) {
      $legacySettings['pattern_mapping']['field_meta_properties:_formatted']['type'] = $legacySettings['type'];
      $legacySettings['pattern_mapping']['field_meta_properties:_formatted']['settings'] = $legacySettings['settings'];
    }

    // Keep settings unrelated to UI Patterns.
    $newSettings = \array_diff_key($legacySettings, \array_flip([
      'type',
      'settings',
      'pattern',
      'pattern_variant',
      'pattern_mapping',
      'pattern_settings',
      'variants_token',
    ]));
    // Build new UI Patterns settings.
    $newSettings['ui_patterns'] = [
      'component_id' => $componentId,
      'variant_id' => $this->convertVariant($legacySettings['pattern_variant'] ?? '', $legacySettings['variants_token'][$legacyPatternId] ?? NULL),
      'props' => $this->convertSettingsToProps($legacySettings['pattern_settings'], $componentId),
      'slots' => $this->convertMappingToSlots($legacySettings['pattern_mapping'], $context),
    ];
    $fieldConfig['settings'] = $newSettings;
  }

  /**
   * Convert views styles related configuration.
   *
   * @code YAML input
   *   # ...
   *   display: !object
   *     !display_name: !object
   *       # ...
   *       display_options: !object
   *         # ...
   *         style: !object
   *           type: pattern
   *           options: !object
   *             # ...
   *             pattern: !string
   *             pattern_mapping: !object
   *             pattern_settings: !object
   *             variants_token: !object
   *             pattern_variant: !string
   *
   * @endcode
   *
   * @code YAML expected output
   *   # ...
   *   display: !object
   *     !display_name: !object
   *       # ...
   *       display_options: !object
   *         # ...
   *         style: !object
   *           type: ui_patterns
   *           options: !object
   *             ui_patterns: !object
   *               ui_patterns: !object
   *                 component_id: !string
   *                 variant_id: !object
   *                 props: !object
   *                 slots: !object
   *
   * @endcode
   */
  protected function convertViewsStyle(array $target): array {
    if (empty($target['module']) || $target['module'] !== 'views') {
      return $target;
    }
    $componentsIds = [];
    $context = [];
    $context['usage'] = 'views_style';

    foreach ($target['display'] as &$display) {
      if (empty($display['display_options']['style']['type']) || $display['display_options']['style']['type'] !== 'pattern') {
        continue;
      }

      $legacySettings = $display['display_options']['style']['options'];
      $legacyPatternId = $legacySettings['pattern'];
      $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);
      $componentsIds[] = $componentId;

      // Keep settings unrelated to UI Patterns.
      $newSettings = \array_diff_key($legacySettings, \array_flip([
        'pattern',
        'pattern_variant',
        'pattern_mapping',
        'pattern_settings',
        'variants',
        'variants_token',
      ]));
      // Build new UI Patterns settings.
      // @todo Remove the double array when it's fixed in UIP 2.
      // @see https://www.drupal.org/project/ui_patterns/issues/3540946
      $newSettings['ui_patterns']['ui_patterns'] = [
        'component_id' => $componentId,
        'variant_id' => $this->convertVariant($legacySettings['pattern_variant'] ?? '', $legacySettings['variants_token'][$legacyPatternId] ?? NULL),
        'props' => $this->convertSettingsToProps($legacySettings['pattern_settings'][$legacyPatternId] ?? [], $componentId),
        'slots' => $this->convertMappingToSlots($legacySettings['pattern_mapping'], $context),
      ];
      $display['display_options']['style']['options'] = $newSettings;
      $display['display_options']['style']['type'] = 'ui_patterns';
    }

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_views_style', $componentsIds);
    return $target;
  }

  /**
   * Convert views rows related configuration.
   *
   * @code YAML input
   *   # ...
   *   display: !object
   *     !display_name: !object
   *       # ...
   *       display_options: !object
   *         # ...
   *         row:
   *           type: ui_patterns
   *           options: !object
   *             # ...
   *             pattern: !string
   *             pattern_mapping: !object
   *               !source_name:
   *                 weight: !int
   *                 destination: !string
   *                 plugin: !string
   *                 source: !string
   *             pattern_settings: !object
   *               !pattern_name: !object
   *             variants_token: !object
   *               !pattern_name: !string
   *             pattern_variant: !string
   *
   * @endcode
   *
   * @code YAML expected output
   *   # ...
   *   display: !object
   *     !display_name: !object
   *       # ...
   *       display_options: !object
   *         # ...
   *         row:
   *           type: ui_patterns
   *           options: !object
   *             # ...
   *             ui_patterns: !object
   *               component_id: !string
   *               variant_id: !object
   *               props: !object
   *               slots: !object
   *
   * @endcode
   */
  protected function convertViewsRow(array $target): array {
    if (empty($target['module']) || $target['module'] !== 'views') {
      return $target;
    }
    $componentsIds = [];
    $context = [];
    $context['usage'] = 'views_row';

    foreach ($target['display'] as &$display) {
      if (empty($display['display_options']['row']['type']) || $display['display_options']['row']['type'] !== 'ui_patterns') {
        continue;
      }

      $legacySettings = $display['display_options']['row']['options'];
      $legacyPatternId = $legacySettings['pattern'] ?? NULL;
      if (!$legacyPatternId) {
        continue;
      }
      $componentId = $this->renderableConverter->getNamespacedId($legacyPatternId);
      $componentsIds[] = $componentId;

      // Keep settings unrelated to UI Patterns.
      $newSettings = \array_diff_key($legacySettings, \array_flip([
        'default_field_elements',
        'inline',
        'separator',
        'pattern',
        'pattern_variant',
        'pattern_mapping',
        'pattern_settings',
        'variants',
        'variants_token',
      ]));
      // Build new UI Patterns settings.
      $newSettings['ui_patterns'] = [
        'component_id' => $componentId,
        'variant_id' => $this->convertVariant($legacySettings['pattern_variant'] ?? '', $legacySettings['variants_token'][$legacyPatternId] ?? NULL),
        'props' => $this->convertSettingsToProps($legacySettings['pattern_settings'][$legacyPatternId] ?? [], $componentId),
        'slots' => $this->convertMappingToSlots($legacySettings['pattern_mapping'], $context),
      ];

      if (isset($newSettings['hide_empty'])) {
        $newSettings['hide_empty'] = (bool) $newSettings['hide_empty'];
      }
      $display['display_options']['row']['options'] = $newSettings;
    }

    $target['dependencies'] = $this->convertDependencies($target['dependencies'], 'ui_patterns_views', $componentsIds);
    return $target;
  }

  /**
   * Convert settings array to props array.
   *
   * @code YAML input
   *   settings:
   *     more_url:
   *       input: '[block_content:home_cta_more:url]'
   *     more_text:
   *       input: '[block_content:home_cta_more:title]'
   *     add_mask: 0
   *     add_mask_token: '[block_content:home_header_mask:value]'
   *
   * @endcode
   *
   * @code YAML expected output
   *   props:
   *     attributes:
   *       source_id: attributes
   *       source:
   *         value: ''
   *     type:
   *       source_id: token
   *       source:
   *         value: ''
   *
   * @endcode
   */
  public function convertSettingsToProps(array $settings, string $componentId): array {
    $component = $this->componentPluginManager->getDefinition($componentId);
    $props = [
      'attributes' => [
        'source_id' => 'attributes',
        'source' => [
          'value' => '',
        ],
      ],
    ];

    foreach ($component['props']['properties'] as $propKey => $prop) {
      if (\array_key_exists($propKey, $settings)) {
        $propType = $component['props']['properties'][$propKey]['ui_patterns']['type_definition'];
        $settingToken = $settings[$propKey . '_token'] ?? NULL;
        $props[$propKey] = $this->convertProp($settings[$propKey], (string) $settingToken, $propType);
      }
      // Sometimes settings have a level per pattern.
      elseif (isset($settings[$component['machineName']])
        && is_array($settings[$component['machineName']])
        && \array_key_exists($propKey, $settings[$component['machineName']])
      ) {
        $propType = $component['props']['properties'][$propKey]['ui_patterns']['type_definition'];
        $settingToken = $settings[$component['machineName']][$propKey . '_token'] ?? NULL;
        $props[$propKey] = $this->convertProp($settings[$component['machineName']][$propKey], (string) $settingToken, $propType);
      }
    }

    return $props;
  }

  /**
   * Convert mapping array to slots array.
   *
   * @code YAML input
   *   mapping: !object
   *     !source_id: !object
   *       destination: !string
   *       weight: !int
   *       plugin: !string
   *       source: !string
   *
   * @endcode
   *
   * @code YAML expected output
   *   slots: !object
   *     !slot_name: !object
   *       sources: !array
   *         -
   *           source_id: !string
   *           source: !object
   *           _weight: !int
   *
   * @endcode
   */
  public function convertMappingToSlots(array $mapping, array $context = []): array {
    $slots = [];

    foreach ($mapping as $legacySlot) {
      $slot_machine_name = $legacySlot['destination'];
      if (!isset($slots[$slot_machine_name])) {
        $slots[$slot_machine_name] = ['sources' => []];
      }

      $slot_source_id = $this->convertSlotSourceId($legacySlot['source'], $legacySlot['plugin'], $context);
      $convertedSource = [
        'source_id' => $slot_source_id,
        '_weight' => isset($legacySlot['weight']) ? (string) $legacySlot['weight'] : '0',
      ];

      $slot_source = $this->convertSlotSource($legacySlot, $context);
      if (!empty($slot_source)) {
        $convertedSource['source'] = $slot_source;
      }

      $slots[$slot_machine_name]['sources'][] = $convertedSource;
    }

    return $slots;
  }

  /**
   * Convert old variant configuration to new one.
   *
   * @code YAML output for standard variant
   * variant_id:
   *   source_id: select
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
  public function convertVariant(string $variant, ?string $variant_token = NULL): array {
    $settings = [
      'source_id' => 'select',
      'source' => ['value' => $variant],
    ];
    if (!empty($variant_token)) {
      $settings['source_id'] = 'token';
      $settings['source']['value'] = $variant_token;
    }
    return $settings;
  }

  /**
   * Convert old prop configuration to new one.
   *
   * @code YAML input
   *   !prop_name: !mixed
   *   !prop_name_token: !string
   *
   * @endcode
   *
   *  @code YAML output
   *    !prop_name:
   *      source_id: !string
   *      source: !mixed
   *
   * @endcode
   */
  public function convertProp(mixed $prop, mixed $prop_token = NULL, ?PropTypeInterface $propType = NULL): array {
    // @todo We might want to add more brain power here to adapt source_id to
    //   the legacy setting type.
    $value = $prop;
    if (is_array($value) && isset($value['input'])) {
      $value = $value['input'];
    }

    $defaultSourceID = $propType->getDefaultSourceId();
    $settings = [
      'source_id' => $defaultSourceID ?: 'select',
      'source' => ['value' => (string) $value],
    ];
    if (!empty($prop_token)) {
      $settings['source_id'] = 'token';
      $settings['source']['value'] = $prop_token;
    }
    return $settings;
  }

  /**
   * Convert legacy dependencies to new ones.
   */
  public function convertDependencies(array $dependencies, string $legacyModule, array $componentsIds): array {
    // Init the key in case manipulating a config detected not using module
    // dependency.
    $dependencies['module'] = $dependencies['module'] ?? [];

    // If there were components to convert, ensure the legacy module is in the
    // list before converting dependencies. It fixes a lot of missing
    // dependencies in UI Patterns 1.x.
    if (!empty($componentsIds)) {
      $dependencies['module'][] = $legacyModule;
    }

    // Convert legacy modules to new ones.
    foreach (self::LEGACY_MODULES_MAPPING as $legacyModuleName => $newModule) {
      if (($key = \array_search($legacyModuleName, $dependencies['module'], TRUE)) === FALSE) {
        continue;
      }
      unset($dependencies['module'][$key]);

      if (empty($newModule)) {
        continue;
      }
      $dependencies['module'][] = 'ui_patterns';
      $dependencies['module'][] = $newModule;
    }

    // Add the components' providers to the dependencies.
    foreach ($componentsIds as $componentId) {
      [$dependency] = \explode(':', $componentId);
      $dependencyType = match (TRUE) {
        $this->themeHandler->themeExists($dependency) => 'theme',
        $this->moduleHandler->moduleExists($dependency) => 'module',
        default => throw new UnknownExtensionException("Unable to define the type of the {$dependency} dependency."),
      };
      $dependencies[$dependencyType][] = $dependency;
    }

    // Clean up.
    foreach (['module', 'theme'] as $dependencyType) {
      if (!isset($dependencies[$dependencyType])) {
        continue;
      }

      if (empty($dependencies[$dependencyType])) {
        unset($dependencies[$dependencyType]);
        continue;
      }

      $dependencies[$dependencyType] = \array_unique(\array_filter($dependencies[$dependencyType]));
      \sort($dependencies[$dependencyType]);
    }

    return $dependencies;
  }

  /**
   * Convert old slots sourceId to new one.
   *
   * @SuppressWarnings("PHPMD.CyclomaticComplexity")
   */
  public function convertSlotSourceId(string $legacySlotSourceId = '', string $legacySlotPlugin = '', array $context = []): string {
    if (isset($context['usage'])
      && $context['usage'] === 'field_group'
      && $legacySlotPlugin == 'fields'
    ) {
      return 'field_group_child';
    }
    if ($legacySlotPlugin == 'field_raw_properties'
    && isset($context['entity_type_id'], $context['field_name'])
    ) {
      return 'field_property:' . $context['entity_type_id'] . ':' . $context['field_name'] . ':' . $legacySlotSourceId;
    }
    if ($legacySlotPlugin == 'field_meta_properties'
      && $legacySlotSourceId == '_formatted'
      && isset($context['entity_type_id'], $context['entity_bundle'], $context['field_name'])
    ) {
      return 'field_formatter:' . $context['entity_type_id'] . ':' . $context['entity_bundle'] . ':' . $context['field_name'];
    }
    if ($legacySlotPlugin === 'views_row') {
      return 'view_field';
    }

    $mapping = [
      // @todo Determine the right output sourceId.
      ['legacyPlugin' => 'fields', 'legacySourceId' => '', 'sourceId' => '???'],
      // Not migrated.
      ['legacyPlugin' => 'extra_fields', 'legacySourceId' => '', 'sourceId' => NULL],
      ['legacyPlugin' => 'fieldgroup', 'legacySourceId' => '_label', 'sourceId' => 'field_group_label'],
      ['legacyPlugin' => 'view_style', 'legacySourceId' => 'title', 'sourceId' => 'view_title'],
      ['legacyPlugin' => 'view_style', 'legacySourceId' => 'rows', 'sourceId' => 'view_rows'],
      ['legacyPlugin' => 'field_meta_properties', 'legacySourceId' => '_label', 'sourceId' => 'field_label'],
      // Not migrated.
      ['legacyPlugin' => 'field_meta_properties', 'legacySourceId' => '_field_display_label', 'sourceId' => ''],
      // Not migrated.
      ['legacyPlugin' => 'field_meta_properties', 'legacySourceId' => '_entity_form_field_label', 'sourceId' => ''],
    ];
    foreach ($mapping as $entry) {
      if ($legacySlotSourceId === $entry['legacySourceId'] && $legacySlotPlugin === $entry['legacyPlugin']) {
        return $entry['sourceId'];
      }
    }
    return '';
  }

  /**
   * Convert old slots source to new one.
   *
   * @SuppressWarnings("PHPMD.CyclomaticComplexity")
   */
  public function convertSlotSource(array $legacySlotSource, array $context = []): array {
    $legacySlotPlugin = $legacySlotSource['plugin'] ?? NULL;
    $legacySlotSourceId = $legacySlotSource['source'] ?? NULL;

    if ($legacySlotPlugin == 'view_style' && $legacySlotSourceId == 'title') {
      // Nothing to do in this case.
      return [];
    }
    if ($legacySlotPlugin == 'view_style' && $legacySlotSourceId == 'rows') {
      return [
        'ui_patterns_views_field' => '',
      ];
    }
    if ($legacySlotPlugin == 'views_row') {
      return [
        'ui_patterns_views_field' => $legacySlotSource['source'],
      ];
    }

    if (isset($context['usage'])
      && $context['usage'] === 'field_group'
      && $legacySlotPlugin == 'fields'
    ) {
      return [
        'field_group_child' => $legacySlotSourceId,
      ];
    }

    if (isset($context['usage'])
      && $context['usage'] === 'field_formatter'
      && $legacySlotPlugin == 'field_meta_properties'
      && $legacySlotSourceId == '_formatted'
    ) {
      return [
        'type' => $legacySlotSource['type'],
        'settings' => $legacySlotSource['settings'],
        'third_party_settings' => $legacySlotSource['third_party_settings'] ?? [],
      ];
    }

    return [];
  }

}
