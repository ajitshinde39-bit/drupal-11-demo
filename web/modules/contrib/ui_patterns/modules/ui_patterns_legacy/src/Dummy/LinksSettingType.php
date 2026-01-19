<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_legacy\Dummy;

use Drupal\ui_patterns\Plugin\UiPatterns\PropType\LinksPropType;

/**
 * Component converter.
 */
class LinksSettingType {

  /**
   * Dummy call to normalize method.
   *
   * Some theme were doing explicit direct calls to this method because the
   * normalize method was not part of any interface and not triggered by the
   * ComponentElementAlter yet.
   */
  public static function normalize(mixed $value, ?array $definition = NULL): array {
    $message = t("Deprecated call to LinksSettingType. If you manage some specific logic with it, replace it by LinksPropType. Otherwise, you can remove it because the render element is now taking care of the normalization.");
    \Drupal::logger('ui_patterns_legacy')->warning($message);
    \Drupal::service('messenger')->addWarning($message);
    $value = LinksPropType::normalize($value, $definition);
    return $value;
  }

}
