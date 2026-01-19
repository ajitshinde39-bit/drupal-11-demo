<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_library;

/**
 * Stories syntax converter.
 *
 * Stories slots may skip the "#" prefix in render arrays for readability.
 * Let's put them back.
 *
 * Before: ["type" => "component", "component" => "example:card"]
 * After: ["#type" => "component", "#component" => "example:card"]
 */
class StoriesSyntaxConverter {

  /**
   * An array with one (and only one) of those keys may be a render array.
   */
  public const RENDER_KEYS = [
    'markup',
    'plain_text',
    'theme',
    'item_list',
    'type',
    '#markup',
    '#plain_text',
    '#theme',
    '#item_list',
    '#type',
  ];

  public const KNOWN_PROPERTIES = [
    'type' => [
      'item_list' => [
        'list_type',
        'items',
        '#list_type',
        '#items',
      ],
      'html_tag' => [
        'attached',
        'attributes',
        'tag',
        'type',
        'value',
        '#attached',
        '#attributes',
        '#tag',
        '#type',
        '#value',
      ],
    ],
    'theme' => [
      'layout' => [
        'attached',
        'attributes',
        'theme',
        'settings',
        '#attached',
        '#attributes',
        '#theme',
        '#settings',
      ],
    ],
  ];

  /**
   * List of render properties which should have been children instead.
   */
  public const PROPERTIES_INSTEAD_OF_CHILDREN = [
    'type' => [
      'component' => [
        'slots',
        '#slots',
      ],
    ],
    'theme' => [
      'status_messages' => [
        'message_list',
        '#message_list',
      ],
      'item_list' => [
        'items',
        '#items',
      ],
      'table' => [
        'header',
        'rows',
        'footer',
        'empty',
        'caption',
        '#header',
        '#rows',
        '#footer',
        '#empty',
        '#caption',
      ],
    ],
  ];

  /**
   * Process stories slots.
   */
  public function convertSlots(array $array): array {
    if ($this->isRenderArray($array)) {
      return $this->convertRenderArray($array);
    }
    foreach ($array as $index => $value) {
      if (!\is_array($value)) {
        continue;
      }
      $array[$index] = $this->convertSlots($value);
    }
    return $array;
  }

  /**
   * Convert a render array.
   *
   * @param array $renderable
   *   The render array being processed.
   *
   * @return array
   *   The processed render array.
   */
  protected function convertRenderArray(array $renderable): array {
    $renderable = $this->prepareRenderArray($renderable);

    // Weird detection.
    if (isset($renderable['type'], self::PROPERTIES_INSTEAD_OF_CHILDREN['type'][$renderable['type']])) {
      return $this->convertWeirdRenderArray($renderable, self::PROPERTIES_INSTEAD_OF_CHILDREN['type'][$renderable['type']]);
    }
    if (isset($renderable['theme'])) {
      $baseThemeHook = \explode('__', $renderable['theme'])[0];
      if (isset(self::PROPERTIES_INSTEAD_OF_CHILDREN['theme'][$baseThemeHook])) {
        return $this->convertWeirdRenderArray($renderable, self::PROPERTIES_INSTEAD_OF_CHILDREN['theme'][$baseThemeHook]);
      }
    }

    // Normal with special case detection.
    if (isset($renderable['type'], self::KNOWN_PROPERTIES['type'][$renderable['type']])) {
      return $this->convertNormalRenderArray($renderable, self::KNOWN_PROPERTIES['type'][$renderable['type']]);
    }
    if (isset($renderable['theme'])) {
      $baseThemeHook = \explode('__', $renderable['theme'])[0];
      if (isset(self::KNOWN_PROPERTIES['theme'][$baseThemeHook])) {
        return $this->convertNormalRenderArray($renderable, self::KNOWN_PROPERTIES['theme'][$baseThemeHook]);
      }
    }

    return $this->convertNormalRenderArray($renderable, []);
  }

  /**
   * Add property prefix.
   *
   * @param array $renderable
   *   The renderable array.
   * @param mixed $property
   *   The property.
   *
   * @return array
   *   The array with prefixed property.
   */
  protected function convertProperty(array $renderable, mixed $property): array {
    if (!\is_string($property)) {
      return $renderable;
    }
    if (\str_starts_with($property, '#')) {
      return $renderable;
    }
    $renderable['#' . $property] = $renderable[$property];
    unset($renderable[$property]);
    return $renderable;
  }

  /**
   * To convert "normal" render array.
   *
   * A "normal" render arrays is an array:
   * - where properties (key starts with a '#') are not renderables
   * - children (key does not start with a '#') are only renderables.
   *
   * Examples:
   * - html_tag which is forbidding renderables in #value
   * - layout where every region is a child.
   *
   * @param array $renderable
   *   The renderable array.
   * @param array $knownProperties
   *   The list of know properties.
   *
   * @return array
   *   The converted array.
   */
  protected function convertNormalRenderArray(array $renderable, array $knownProperties): array {
    foreach ($renderable as $property => $value) {
      if (empty($knownProperties) && \is_string($property)) {
        // Default to add the prefix to every entry.
        $renderable = $this->convertProperty($renderable, $property);
      }
      elseif (\in_array($property, $knownProperties, TRUE)) {
        // We add # prefix only to known properties.
        $renderable = $this->convertProperty($renderable, $property);
      }
      elseif (\is_array($value)) {
        // Other keys may have children, so let's drill.
        $renderable[$property] = $this->convertSlots($value);
      }
    }
    return $renderable;
  }

  /**
   * The "weird" render arrays, where renderables are found only in properties.
   *
   * Examples: component with #slots, table with #rows...
   *
   * @param array $renderable
   *   The renderable array.
   * @param array $propertiesWithRenderables
   *   The list of properties to look for.
   *
   * @return array
   *   The updated renderable array.
   */
  protected function convertWeirdRenderArray(array $renderable, array $propertiesWithRenderables): array {
    foreach ($renderable as $property => $value) {
      if (\in_array($property, $propertiesWithRenderables, TRUE) && \is_array($value)) {
        $renderable[$property] = $this->convertSlots($value);
      }
      // There are no children, so we add a # everywhere.
      $renderable = $this->convertProperty($renderable, $property);
    }
    return $renderable;
  }

  /**
   * Is the array a render array?
   *
   * @param array $array
   *   The array being processed.
   *
   * @return bool
   *   True if a render array.
   */
  protected function isRenderArray(array $array): bool {
    if (\array_is_list($array)) {
      return FALSE;
    }
    // An array needs one, and only one, of those properties to be a render
    // array.
    $intersect = \array_intersect(\array_keys($array), self::RENDER_KEYS);
    if (\count($intersect) != 1) {
      return FALSE;
    }
    // This property has to be a string value.
    $property = $intersect[\array_key_first($intersect)];
    if (!\is_string($array[$property])) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Prepare a render array.
   *
   * Remove # on type and theme, will re-add it later.
   *
   * @param array $renderable
   *   The render array.
   *
   * @return array
   *   The prepared render array.
   */
  protected function prepareRenderArray(array $renderable): array {
    if (isset($renderable['#type']) && !isset($renderable['type'])) {
      $renderable['type'] = $renderable['#type'];
      unset($renderable['#type']);
    }
    if (isset($renderable['#theme']) && !isset($renderable['theme'])) {
      $renderable['theme'] = $renderable['#theme'];
      unset($renderable['#theme']);
    }
    return $renderable;
  }

}
