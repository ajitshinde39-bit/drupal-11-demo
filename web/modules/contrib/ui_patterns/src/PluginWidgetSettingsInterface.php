<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for source plugins.
 */
interface PluginWidgetSettingsInterface {

  /**
   * Defines the default settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public function defaultWidgetSettings(): array;

  /**
   * Returns the value of a widget setting, or its default value if absent.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getWidgetSetting(string $key): mixed;

  /**
   * Sets the widget settings for the plugin.
   *
   * @param array $settings
   *   The array of settings, keyed by setting names. Missing settings will be
   *   assigned their default values.
   *
   * @return $this
   */
  public function setWidgetSettings(array $settings): PluginWidgetSettingsInterface;

  /**
   * Returns a form to configure settings for the source plugins.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form elements for the source settings.
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Returns a short summary for the current widget settings.
   *
   * @return array
   *   A short summary of the widget settings.
   */
  public function widgetSettingsSummary(): array;

}
