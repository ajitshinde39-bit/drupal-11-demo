<?php

/**
 * @file
 * API file.
 */

declare(strict_types=1);

use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;

/**
 * Alter group form row.
 *
 * @param array $row
 *   The group row.
 * @param \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay $display
 *   The component form display.
 * @param array $group
 *   The group for this row.
 *
 * @SuppressWarnings("PHPMD.UnusedFormalParameter")
 */
function hook_component_form_display_group_row_alter(array &$row, ComponentFormDisplay $display, array $group) {
  $row['human_name']['#markup'] = $group['label'];
}

/**
 * Groups for display.
 *
 * @param \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay $display
 *   The component form display.
 */
function hook_component_form_display_groups(ComponentFormDisplay $display) {
  return $display->getThirdPartySettings('my_module');
}
