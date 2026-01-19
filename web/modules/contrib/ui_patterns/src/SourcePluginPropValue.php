<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

/**
 * This class covers a specific case of Source plugins.
 *
 * The key 'value' is used in the plugin settings (Form's settings).
 * settings["value"] stores the prop value.
 * the default property from the JSON schema can be used.
 */
abstract class SourcePluginPropValue extends SourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return ["value" => $this->getDefaultFromPropDefinition()];
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting(string $key): mixed {
    $value = parent::getSetting($key);
    if (("value" === $key) && (NULL === $value)) {
      return $this->getDefaultFromPropDefinition();
    }

    return $value;
  }

  /**
   * Return default value from prop definition.
   *
   * @return mixed
   *   Default value from prop definition if relevant.
   */
  protected function getDefaultFromPropDefinition(): mixed {
    if (is_array($this->propDefinition) &&
      array_key_exists("default", $this->propDefinition)) {
      // Inside the prop definition,
      // the default value is in the prop type's format.
      return $this->convertPropValueToStoredValue($this->propDefinition["default"]);
    }
    return NULL;
  }

  /**
   * Convert from prop type' format to stored settings' format.
   *
   * @param mixed $propValue
   *   Prop value in the format described in the prop type.
   *
   * @return mixed
   *   Value as stored in the settings.
   */
  protected function convertPropValueToStoredValue(mixed $propValue): mixed {
    return $propValue;
  }

  /**
   * Merges default settings values into $settings.
   */
  protected function mergeDefaults() : void {
    $defaultSettings = $this->defaultSettings();
    // -> we prefer the prop definition default value.
    if (array_key_exists("value", $defaultSettings)) {
      $defaultValueProp = $this->getDefaultFromPropDefinition();
      if (NULL !== $defaultValueProp) {
        $defaultSettings["value"] = $defaultValueProp;
      }
    }
    $this->settings += $defaultSettings;
    $this->defaultSettingsMerged = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    return $this->getSetting('value');
  }

}
