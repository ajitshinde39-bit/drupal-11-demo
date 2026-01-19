<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Url;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'Attributes' PropType.
 */
#[PropType(
  id: 'attributes',
  label: new TranslatableMarkup('Attributes'),
  description: new TranslatableMarkup('HTML attributes as a a mapping.'),
  default_source: 'attributes',
  schema: [
    'type' => 'object',
    'patternProperties' => [
      '.+' => [
        'anyOf' => [
          ['type' => ['string', 'number']],
          [
            'type' => 'array',
            'items' => [
              'anyOf' => [
              ['type' => 'number'],
              ['type' => 'string'],
              ],
            ],
          ],
        ],
      ],
    ],
  ],
  priority: 10
)]
class AttributesPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): array {
    /*
    Attributes are defined as a mapping ('object' in JSON schema). So, source
    plugins are expected to return a mapping to not break SDC prop validation
    against the prop type schema.
     */
    if (is_array($value) && !empty($value)) {
      // Attribute::createAttributeValue() is already normalizing some stuff:
      // - 'class' attribute must be a list
      // - MarkupInterface values must be resolved.
      $value = (new Attribute($value))->toArray();
    }
    elseif (is_a($value, '\Drupal\Core\Template\Attribute')) {
      // Attribute PHP objects are rendered as strings by SDC ComponentValidator
      // this is raising an error: "InvalidComponentException: String value
      // found, but an object is required".
      $value = $value->toArray();
    }
    else {
      return [];
    }
    foreach ($value as $attr => $attr_value) {
      $value[$attr] = static::normalizeAttrValue($attr_value);
    }
    return $value;
  }

  /**
   * Normalize attribute value.
   */
  protected static function normalizeAttrValue(mixed $value): mixed {
    if (is_object($value)) {
      return static::normalizeObject($value);
    }
    if (is_array($value) && array_is_list($value)) {
      return static::normalizeList($value);
    }
    if (is_array($value) && !array_is_list($value)) {
      return static::normalizeMapping($value);
    }
    // We don't allow markup in attribute value.
    return strip_tags((string) $value);
  }

  /**
   * Normalize list item.
   */
  protected static function normalizeListItem(mixed $value): mixed {
    if (is_object($value)) {
      return static::normalizeObject($value);
    }
    if (is_array($value) && array_is_list($value)) {
      // We encode to JSON because we don't know how deep is the nesting.
      return json_encode($value, 0, 3) ?: "";
    }
    if (is_array($value) && !array_is_list($value)) {
      return static::normalizeRenderArray($value);
    }
    // Integer and number are always allowed values.
    if (is_int($value) || is_float($value)) {
      return $value;
    }
    // We don't allow markup in attribute value.
    return strip_tags((string) $value);
  }

  /**
   * Normalize object attribute value.
   */
  protected static function normalizeObject(object $value): array|string {
    if ($value instanceof Url) {
      return $value->toString();
    }
    if ($value instanceof RenderableInterface) {
      return static::normalizeRenderArray($value->toRenderable());
    }
    if ($value instanceof MarkupInterface) {
      return (string) $value;
    }
    if ($value instanceof \Stringable) {
      return (string) $value;
    }
    // Instead of keeping an unexpected object, we return PHP namespace.
    // It will be valid and can inform the component user about its mistake.
    return get_class($value);
  }

  /**
   * Normalize list attribute value.
   */
  protected static function normalizeList(array $value): array {
    foreach ($value as $index => $item) {
      $value[$index] = static::normalizeListItem($item);
    }
    return $value;
  }

  /**
   * Normalize mapping attribute value.
   */
  protected static function normalizeMapping(array $value): array|string {
    if (!empty(Element::properties($value))) {
      return static::normalizeRenderArray($value);
    }
    return static::normalizeList(array_values($value));
  }

  /**
   * Normalize render array.
   */
  protected static function normalizeRenderArray(array $value): string {
    if (!empty(Element::properties($value))) {
      $markup = (string) \Drupal::service('renderer')->render($value);
      return strip_tags($markup);
    }
    // We encode to JSON because we don't know how deep is the nesting.
    return json_encode($value, 0, 3) ?: "";
  }

  /**
   * {@inheritdoc}
   */
  public static function preprocess(mixed $value, ?array $definition = NULL): mixed {
    /*
    However, when they land in the template, it is safer to have them as
    Attribute objects:
    - if the template use create_attribute(), it will not break thanks to
    "#3403331: Prevent TypeError when using create_attribute Twig function"
    - if the template directly calls object methods, it will work because it
    is already an object
    - ArrayAccess interface allows manipulation as an array.
     */
    if (is_a($value, '\Drupal\Core\Template\Attribute')) {
      return $value;
    }
    if (is_array($value)) {
      return new Attribute($value);
    }
    return new Attribute();
  }

}
