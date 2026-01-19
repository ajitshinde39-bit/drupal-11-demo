<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;
use Twig\Markup as TwigMarkup;

/**
 * Provides a 'Slot' PropType.
 */
#[PropType(
  id: 'slot',
  label: new TranslatableMarkup('Slot'),
  description: new TranslatableMarkup('A placeholder inside a component that can be filled with renderables.'),
  default_source: 'component',
  convert_from: ['string'],
  schema: [],
  priority: 10
)]
class SlotPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    if (is_object($value)) {
      return self::convertObject($value);
    }
    if (is_string($value)) {
      return ['#children' => Markup::create($value)];
    }
    if (!is_array($value)) {
      return empty($value) ? ['#cache' => []] : ['#plain_text' => (string) $value];
    }
    return self::cleanRenderArray($value);
  }

  /**
   * Clean a render array.
   *
   * @param array $value
   *   The render array to clean.
   *
   * @return array
   *   The cleaned render array.
   */
  protected static function cleanRenderArray(array $value): mixed {
    if (empty($value)) {
      // Element::isRenderArray() returns FALSE for empty arrays.
      return ['#cache' => []];
    }
    $element_properties = Element::properties($value);
    if (empty($element_properties)) {
      // Twig `is sequence` and `is mapping `tests are not useful when a list
      // of renderables has mapping keys (non consecutive, strings) instead of
      // sequence (integer, consecutive) keys. For example a list of blocks
      // from page layout or layout builder: each block is keyed by its UUID.
      // So, transform this list of renderables to a proper Twig sequence.
      return array_map(static fn($item) => static::normalize($item), array_is_list($value) ? $value : array_values($value));
    }
    foreach ($value as $key => & $item) {
      if (!in_array($key, $element_properties, TRUE)) {
        $item = static::normalize($item);
      }
    }
    return $value;
  }

  /**
   * Convert PHP objects to render array.
   */
  protected static function convertObject(object $value): mixed {
    if ($value instanceof RenderableInterface) {
      $value = $value->toRenderable();
    }
    if (($value instanceof TwigMarkup) ||
      ($value instanceof MarkupInterface)) {
      return ['#children' => $value];
    }
    if ($value instanceof \Stringable) {
      return [
        '#plain_text' => (string) $value,
      ];
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFrom(string $prop_type, mixed $value): mixed {
    return match ($prop_type) {
      'string' => ($value instanceof MarkupInterface) ? ["#children" => $value] : ["#plain_text" => $value],
    };
  }

}
