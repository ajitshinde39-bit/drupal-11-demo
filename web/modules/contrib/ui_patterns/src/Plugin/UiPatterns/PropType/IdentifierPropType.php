<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'Machine name' PropType.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/ident
 */
#[PropType(
  id: 'identifier',
  label: new TranslatableMarkup('Identifier'),
  description: new TranslatableMarkup('A string with restricted characters, suitable for an HTML ID.'),
  default_source: 'textfield',
  convert_from: ['string'],
  schema: ['type' => 'string', 'pattern' => '(?:--|-?[A-Za-z_\x{00A0}-\x{10FFFF}])[A-Za-z0-9-_\x{00A0}-\x{10FFFF}\.]*'],
  priority: 100
)]
class IdentifierPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    $value = strip_tags(static::normalizer()->convertToString($value));
    // Clean the value.
    $value = preg_replace('/[^A-Za-z0-9-_\x{00A0}-\x{10FFFF}\.]/u', '-', $value);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFrom(string $prop_type, mixed $value): mixed {
    // We allow conversion from string,
    // normalize() will partially sanitize the value.
    // Invalid identifiers throw exceptions.
    return match ($prop_type) {
      'string' => (string) $value,
    };
  }

}
