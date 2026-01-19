<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Render\Element;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;

/**
 * Our additions to the SDC render element.
 */
class ComponentElementAlter implements TrustedCallbackInterface {

  /**
   * Constructs a ComponentElementAlter.
   */
  public function __construct(protected ComponentPluginManager $componentPluginManager) {}

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['alter'];

  }

  /**
   * Alter SDC component element.
   *
   * The ::normalizeProps() methods logic has been moved to
   * TwigExtension::normalizeProps() in order to be executed also when
   * components are loaded from Twig include or embed.
   */
  public function alter(array $element): array {
    $element = $this->normalizeSlots($element);
    $element = $this->processAttributesRenderProperty($element);
    return $element;
  }

  /**
   * Normalize slots.
   */
  public function normalizeSlots(array $element): array {

    foreach ($element["#slots"] as $slot_id => $slot) {
      // Because SDC validator is sometimes confused by a null slot.
      if (is_null($slot)) {
        unset($element['#slots'][$slot_id]);
        continue;
      }
      $slot = SlotPropType::normalize($slot);
      // Because SDC validator is sometimes confused by an empty slot.
      // We check the current slot render element.
      if (is_array($slot) && self::isSlotEmpty($slot)) {
        self::mergeSlotBubbleableMetadata($element, $slot, 1);
        unset($element['#slots'][$slot_id]);
        continue;
      }
      $element["#slots"][$slot_id] = $slot;
    }
    return $element;
  }

  /**
   * Process #attributes render property.
   *
   * #attributes property is an universal property of the Render API, used by
   * many Drupal mechanisms from Core and Contrib, but not processed by SDC
   * render element.
   *
   * @todo Move this to Drupal Core.
   */
  public function processAttributesRenderProperty(array $element): array {
    if (!isset($element["#attributes"])) {
      return $element;
    }
    if (is_a($element["#attributes"], '\Drupal\Core\Template\Attribute')) {
      $element["#attributes"] = $element["#attributes"]->toArray();
    }
    // Like \Drupal\Core\Template\Attribute::merge(), we use
    // NestedArray::mergeDeep().
    // This function is similar to PHP's array_merge_recursive() function, but
    // it handles non-array values differently. When merging values that are
    // not both arrays, the latter value replaces the former rather than
    // merging with it.
    $element["#props"]["attributes"] = NestedArray::mergeDeep(
      $element["#attributes"],
      $element["#props"]["attributes"] ?? []
    );
    return $element;
  }

  /**
   * Merge slots metadata to the component recursive.
   */
  protected static function mergeSlotBubbleableMetadata(array &$element, array $slot, int $max_level = 1, int $level = 0): void {
    if ($level < $max_level) {
      foreach (Element::getVisibleChildren($slot) as $child) {
        self::mergeSlotBubbleableMetadata($element, $slot[$child], $max_level, $level + 1);
      }
    }
    $elementMetadata = BubbleableMetadata::createFromRenderArray($element);
    $elementMetadata->merge(BubbleableMetadata::createFromRenderArray($slot));
    $elementMetadata->applyTo($element);
  }

  /**
   * Checks the given render element for emptiness.
   *
   * The method calls Element::isEmpty recursive until max level is reached.
   *
   * @param array $slot
   *   The render array.
   * @param int $max_level
   *   The level of recursion.
   * @param int $level
   *   Internal used level.
   *
   * @return bool
   *   Returns true for empty.
   */
  public static function isSlotEmpty(array $slot, int $max_level = 5, int $level = 0): bool {
    if (is_array($slot) && empty($slot)) {
      return TRUE;
    }
    if ($level < $max_level) {
      foreach (Element::children($slot) as $child) {
        if (self::isSlotEmpty($slot[$child], $max_level, $level + 1) === FALSE) {
          return FALSE;
        }
        else {
          unset($slot[$child]);
        }
      }
    }

    return self::checkSlotEmpty($slot);
  }

  /**
   * Advanced indicates whether the given element is empty.
   *
   * Before using Element::isEmpty($slot) the slot values are trimmed
   * to catch more empty cases.
   *
   * @param array $slot
   *   The slot.
   *
   * @return bool
   *   Whether the given element is empty.
   */
  private static function checkSlotEmpty(array $slot):bool {
    foreach (['#markup', '#plain_text'] as $key) {
      if (array_key_exists($key, $slot) && empty($slot[$key])) {
        unset($slot[$key]);
      }
    }
    if (isset($slot['#access']) && is_string($slot['#access'])) {
      // This fix is for isVisibleElement() to work properly.
      $slot['#access'] = (bool) $slot['#access'];
    }
    if (Element::isEmpty($slot) || Element::isVisibleElement($slot) === FALSE) {
      return TRUE;
    }
    return FALSE;
  }

}
