<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\Render\RendererInterface;

/**
 * Handles conversion and normalization of values.
 */
class UiPatternsNormalizer implements UiPatternsNormalizerInterface {

  /**
   * Constructs a UiPatternsNormalizer.
   */
  public function __construct(
    protected RendererInterface $renderer,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function convertToScalar(mixed &$value, bool $strip_tags_from_render_arrays = TRUE) : void {
    if ($value instanceof RenderableInterface) {
      $value = $value->toRenderable();
    }
    elseif (($value instanceof MarkupInterface) || ($value instanceof \Stringable)) {
      $value = (string) $value;
    }
    elseif (is_object($value) && method_exists($value, 'toString')) {
      $value = $value->toString();
    }
    if (is_array($value)) {
      $value = $this->convertArrayToScalar($value, $strip_tags_from_render_arrays);
    }
  }

  /**
   * Convert an array to scalar.
   *
   * @param array $array
   *   The array to convert.
   * @param bool $strip_tags_from_render_arrays
   *   Whether to strip tags from render arrays.
   *
   * @return mixed
   *   The converted array.
   */
  protected function convertArrayToScalar(array $array, bool $strip_tags_from_render_arrays = TRUE) : mixed {
    if (empty($array)) {
      return NULL;
    }
    if (!empty(Element::properties($array))) {
      $value = (string) $this->renderer->renderInIsolation($array);
      if ($strip_tags_from_render_arrays) {
        $value = strip_tags($value);
      }
      return $value;
    }
    foreach ($array as $value) {
      if ($value === NULL) {
        continue;
      }
      $this->convertToScalar($value, $strip_tags_from_render_arrays);
      return $value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function convertToString(mixed $value) : string {
    if ($value === NULL) {
      return '';
    }
    $this->convertToScalar($value, FALSE);
    if (is_array($value)) {
      return json_encode($value, 0, 3) ?: "";
    }
    return is_string($value) ? $value : (string) $value;
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeEnumValues(mixed $values, ?array $enum = NULL): array {
    if ($values === NULL) {
      return [];
    }
    if (!is_array($values)) {
      $values = [$values];
    }
    $values = array_map(function ($item) use ($enum) {
      return $this->normalizeEnumValue($item, $enum);
    }, $values);
    $values = array_filter($values, function ($item) {
          return $item !== NULL;
    });
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeEnumValue(mixed $value, ?array $enum = NULL): mixed {
    if ($value !== NULL) {
      $this->convertToScalar($value);
    }
    if (!is_array($enum) || empty($enum)) {
      return $value;
    }
    // We try to match first without casting.
    if (in_array($value, $enum, TRUE)) {
      return $value;
    }
    // We try to cast the value and retry to match.
    $value = $this->convertValueToEnumType($value, $enum);
    if (in_array($value, $enum, TRUE)) {
      return $value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function convertValueToEnumType(mixed $value, array $enum): mixed {
    if (!is_scalar($value)) {
      return $value;
    }
    return match (TRUE) {
      in_array($value, $enum, TRUE) => $value,
        in_array((string) $value, $enum, TRUE) => (string) $value,
        in_array((int) $value, $enum, TRUE)  => (int) $value,
        in_array((float) $value, $enum, TRUE) => (float) $value,
        default => $value,
    };
  }

}
