<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_layouts\Element;

use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Alter the rendering of a component element.
 */
class ComponentAlterer implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['processLayoutBuilderRegions'];
  }

  /**
   * Process layout builder regions.
   *
   * Layout builder adds blocks to the regions after the component is built.
   * Let's move them to slots.
   *
   * @param array $element
   *   Render array.
   *
   * @return array
   *   Render array.
   */
  public static function processLayoutBuilderRegions(array $element) {
    if (!isset($element["#layout"])) {
      return $element;
    }
    foreach (Element::children($element) as $region_id) {
      // Example of blocks found here: layout_builder_add_block & region_label.
      foreach (Element::children($element[$region_id]) as $block_id) {
        $element["#slots"][$region_id][$block_id] = $element[$region_id][$block_id];
      }
      // Support for drag&drop and other attributes manipulation in preview.
      if ($element['#layout']->isInPreview() && isset($element[$region_id]['#attributes']) && isset($element['#slots'][$region_id])) {
        $element['#slots'][$region_id] = [
          '#type' => 'html_tag',
          '#tag' => 'div',
          '#attributes' => $element[$region_id]['#attributes'],
          'content' => $element['#slots'][$region_id],
        ];
      }
      unset($element[$region_id]);
    }
    return $element;
  }

}
