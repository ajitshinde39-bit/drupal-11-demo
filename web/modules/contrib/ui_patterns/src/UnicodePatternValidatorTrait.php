<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait for validating Unicode patterns.
 */
trait UnicodePatternValidatorTrait {

  /**
   * Validate pattern.
   *
   * #element_validate callback for #pattern_unicode form element property.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *    generic form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @see https://www.drupal.org/project/drupal/issues/2633550
   * @see https://www.drupal.org/project/webform/issues/3002374
   */
  public static function validateUnicodePattern(array &$element, FormStateInterface $form_state) : void {
    if ($element['#value'] !== '') {
      if (!static::pregMatchUnicodePattern($element['#pattern_unicode'], $element['#value'])) {
        if (!empty($element['#pattern_error'])) {
          $form_state->setError($element, $element['#pattern_error']);
        }
        else {
          $form_state->setError($element, t('%name field is not in the right format.', ['%name' => $element['#title']]));
        }
      }
    }
  }

  /**
   * Match a string against a Unicode pattern.
   *
   * @param string $pattern
   *   Regular expression pattern.
   * @param string $subject
   *   The string to match.
   *
   * @return bool
   *   TRUE if the pattern matches the subject, FALSE otherwise.
   */
  public static function pregMatchUnicodePattern(string $pattern, string $subject): bool {
    $pattern = '{^(?:' . static::convertRegexToPcreFormat($pattern) . ')$}u';
    return (bool) preg_match($pattern, $subject);
  }

  /**
   * JavaScript-escaped Unicode characters to PCRE escape sequence format.
   *
   * @param string $pattern
   *   Regular expression pattern.
   *
   * @return string
   *   PCRE format pattern.
   */
  public static function convertRegexToPcreFormat(string $pattern): string {
    // JavaScript-escaped Unicode characters to PCRE escape sequence format.
    return preg_replace('/\\\\u([a-fA-F0-9]{4})/', '\\x{\\1}', $pattern);
  }

}
