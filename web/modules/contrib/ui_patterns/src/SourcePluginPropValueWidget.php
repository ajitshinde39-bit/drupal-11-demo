<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * Base Class for all widgets with value key.
 *
 * This class covers all widgets with a single "value"
 * setting and a related form element "value".
 * It supports widget settings.
 */
abstract class SourcePluginPropValueWidget extends SourcePluginPropValue implements PluginWidgetSettingsInterface {

  use WidgetSettingTrait;

}
