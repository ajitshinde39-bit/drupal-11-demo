<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\UiPatterns\Source;

use Drupal\ui_patterns\Plugin\UiPatterns\Source\FieldFormatterSource as BaseFieldFormatterSource;

/**
 * Backward compatibility class.
 *
 * @deprecated in ui_patterns:2.0.10 and is removed from ui_patterns:3.0.0. Use
 *  \Drupal\ui_patterns\Plugin\UiPatterns\Source\FieldFormatterSource instead
 * @see https://www.drupal.org/project/ui_patterns/issues/3545507
 */
class FieldFormatterSource extends BaseFieldFormatterSource {
}
