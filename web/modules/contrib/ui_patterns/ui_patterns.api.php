<?php

/**
 * @file
 * API file.
 */

declare(strict_types=1);

/**
 * Alter Hook for SDC Component definition.
 *
 * @param array $definitions
 *   SDC Component definitions.
 *
 * @see \Drupal\ui_patterns\ComponentPluginManager
 */
function hook_component_info_alter(array &$definitions) {
  $definitions['COMPONENT_ID']['slots']['slot_name']["title"] = 'demo';
}

/**
 * Alter Hook for UI Patterns source values.
 *
 * @param mixed $value
 *   Value produced by the source.
 * @param \Drupal\ui_patterns\SourceInterface $source
 *   The source object which has produced the value.
 * @param array $source_configuration
 *   The full raw configuration used to build the source.
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
function hook_ui_patterns_source_value_alter(mixed &$value, \Drupal\ui_patterns\SourceInterface $source, array &$source_configuration) : void {
  $type_definition = $source->getPropDefinition()['ui_patterns']['type_definition'];
  if ($type_definition instanceof \Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType) {
    if (is_array($value)) {
      $value['#cache']['tags'][] = 'custom_cache_tag';
    }
  }
}

/**
 * Prepare Hook for UI Patterns render element.
 *
 * Use this hook to prepare the render element
 * before it's passed to the renderer pipeline.
 *
 * @param array $element
 *   The render element.
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
function hook_ui_patterns_component_pre_build_alter(array &$element): void {

}
