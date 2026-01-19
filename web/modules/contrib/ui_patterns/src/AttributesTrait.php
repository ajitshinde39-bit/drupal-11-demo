<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Utility\Html;

/**
 * Trait for plugins (sources and prop types) handling attributes.
 */
trait AttributesTrait {

  /**
   * Build regular expression pattern.
   *
   * See https://html.spec.whatwg.org/#attributes-2
   */
  protected static function buildAttributesRegexPattern(): string {
    // Attribute names are a mix of ASCII lower and upper alphas.
    $attr_name = "[a-zA-Z\-]+";
    // Discard double quotes which are used for delimiting.
    $double_quoted_value = '[^"]*';
    $space = "\s*";
    $attr = sprintf("%s=\"%s\"%s", $attr_name, $double_quoted_value, $space);
    // Start and end delimiters are not expected here, they will be added:
    // - by \Drupal\Core\Render\Element\FormElementBase::validatePattern for
    //   server side validation
    // - in the HTML5 pattern attribute, for client side validation
    // https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/pattern
    return $space . "(" . $attr . ")*";
  }

  /**
   * Build regular expression pattern.
   */
  protected static function buildClassRegexPattern(): string {
    // Each classname cannot start with a hyphen followed by a digit or a digit.
    $class_name = "(?!(?:\\d|[-]\\d))[\\S]+";
    // Pattern for valid CSS class names.
    return "\\s*(" . $class_name . "(\\s+" . $class_name . ")*)?\\s*";
  }

  /**
   * Check if the value is for attributes.
   *
   * @param string $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the value is for attributes, FALSE otherwise.
   */
  protected static function isValueForAttributes(string $value): bool {
    return str_contains($value, '=');
  }

  /**
   * Convert a string to an attribute mapping.
   *
   * @param mixed $value
   *   The string to convert.
   *
   * @return array
   *   Attributes mapping.
   */
  protected static function convertValueToAttributesMapping(mixed $value): array {
    if (empty($value) || !is_string($value)) {
      return [];
    }
    if (static::isValueForAttributes($value)) {
      return static::convertStringToAttributesMapping($value);
    }
    return static::convertStringToAttributesMapping(sprintf('class="%s"', $value));
  }

  /**
   * Convert a string to an attribute mapping.
   *
   * @param string $value
   *   The string to convert.
   *
   * @return array
   *   Attributes mapping.
   */
  protected static function convertStringToAttributesMapping(string $value): array {
    $parse_html = '<div ' . $value . '></div>';
    $attributes = [];
    foreach (Html::load($parse_html)->getElementsByTagName('div') as $div) {
      foreach ($div->attributes as $attr) {
        $attributes[$attr->nodeName] = $attr->nodeValue;
      }
    }
    return $attributes;
  }

}
