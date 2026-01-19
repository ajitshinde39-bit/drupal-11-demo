<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\EnumTrait;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'Variant' PropType.
 */
#[PropType(
  id: 'variant',
  label: new TranslatableMarkup('Variant'),
  description: new TranslatableMarkup('Prop type for component variants.'),
  default_source: 'select',
  convert_from: ['string'],
  schema: ['type' => ['string'], 'enum' => []],
  priority: 1
)]
class VariantPropType extends PropTypePluginBase {

  use EnumTrait;

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    // First get the enum array.
    $enum = (!is_array($definition)) ? [] : ($definition['enum'] ?? []);
    if (!is_array($enum)) {
      $enum = [];
    }
    return static::normalizer()->normalizeEnumValue($value, $enum);
  }

  /**
   * {@inheritdoc}
   */
  public static function convertFrom(string $prop_type, mixed $value): mixed {
    return match ($prop_type) {
      'string' => (string) $value,
    };
  }

}
