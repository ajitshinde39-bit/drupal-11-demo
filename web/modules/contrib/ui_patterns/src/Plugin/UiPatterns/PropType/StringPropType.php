<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'string' PropType.
 */
#[PropType(
  id: 'string',
  label: new TranslatableMarkup('String'),
  description: new TranslatableMarkup('Strings of text. May contain Unicode characters.'),
  default_source: 'textfield',
  convert_from: ['number', 'url', 'identifier'],
  schema: ['type' => 'string'],
  priority: 1,
  typed_data: ['string']
)]
class StringPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function convertFrom(string $prop_type, mixed $value): mixed {
    return match ($prop_type) {
      'boolean' => (string) $value,
      'number' => (string) $value,
      'url' => $value,
      'identifier' => $value,
      'string' => $value,
      default => (string) $value,
    };
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $definition): array {
    $summary = parent::getSummary($definition);
    if (isset($definition['maxLength'])) {
      $summary[] = $this->t("Max length: @length", ["@length" => $definition['maxLength']]);
    }
    if (isset($definition['minLength'])) {
      $summary[] = $this->t("Min length: @length", ["@length" => $definition['minLength']]);
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): mixed {
    $value = static::normalizer()->convertToString($value);
    $contentMediaType = $definition['contentMediaType'] ?? NULL;
    return ($contentMediaType === 'text/plain') ? strip_tags($value) : $value;
  }

  /**
   * {@inheritdoc}
   */
  public static function preprocess(mixed $value, ?array $definition = NULL): mixed {
    $value = parent::preprocess($value, $definition);
    $contentMediaType = $definition['contentMediaType'] ?? NULL;
    if ($contentMediaType !== 'text/plain') {
      return Markup::create($value);
    }
    return $value;
  }

}
