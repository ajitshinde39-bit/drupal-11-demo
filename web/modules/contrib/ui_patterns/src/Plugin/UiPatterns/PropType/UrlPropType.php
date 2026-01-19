<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\PropType;

use Drupal\Core\Render\RenderableInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_patterns\Attribute\PropType;
use Drupal\ui_patterns\PropTypePluginBase;

/**
 * Provides a 'Url' PropType.
 */
#[PropType(
  id: 'url',
  label: new TranslatableMarkup('Url'),
  description: new TranslatableMarkup('Either a URI or a relative-reference. Can be internationalized.'),
  default_source: 'url',
  schema: ['type' => 'string', 'format' => 'iri-reference'],
  priority: 10,
  typed_data: ['uri']
)]
class UrlPropType extends PropTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public static function normalize(mixed $value, ?array $definition = NULL): string {
    if (is_object($value)) {
      $value = self::convertObject($value);
    }
    if (!is_string($value)) {
      return "";
    }

    if ($value == "<front>") {
      $value = "internal:/";
    }
    elseif ($value == "<none>") {
      $value = "internal:";
    }
    // We don't use filter_var($value, FILTER_VALIDATE_URL) because too
    // restrictive: catch only "scheme://foo".
    if (preg_match("/^[a-z]+\:/", $value)) {
      return self::resolveUrl($value);
    }
    // Any other string is considered as a valid path.
    // This is not our work to do further checks and transformations.
    return $value;
  }

  /**
   * Normalize URL.
   */
  protected static function resolveUrl(string $value): string {
    // PHP_URL_SCHEME works with "scheme://foo" and "scheme:foo".
    $scheme = parse_url($value, PHP_URL_SCHEME);
    if (in_array($scheme, ['public', 'private', 'temp'])) {
      /** @var \Drupal\Core\File\FileUrlGeneratorInterface $generator */
      $generator = \Drupal::service('file_url_generator');
      $value = $generator->generateAbsoluteString($value);
      return Url::fromUri($value)->toString();
    }
    if (in_array($scheme, ['internal', 'entity', 'route'])) {
      return Url::fromUri($value)->toString();
    }
    return $value;
  }

  /**
   * Convert PHP objects to render array.
   */
  protected static function convertObject(object $value): mixed {
    if (is_a($value, '\Drupal\Core\Url')) {
      return $value->toString();
    }
    if ($value instanceof RenderableInterface) {
      $value = $value->toRenderable();
    }
    if (is_array($value) && isset($value['#url'])) {
      $value = $value['#url']->toString();
    }
    static::normalizer()->convertToScalar($value, TRUE);
    return $value;
  }

}
