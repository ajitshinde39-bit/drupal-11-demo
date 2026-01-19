<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for source plugins.
 */
interface PluginSettingsInterface {

  /**
   * Defines the default settings for this plugin.
   *
   * @return array
   *   A list of default settings, keyed by the setting name.
   */
  public function defaultSettings(): array;

  /**
   * Returns the value of a setting, or its default value if absent.
   *
   * @param string $key
   *   The setting name.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting(string $key): mixed;

  /**
   * Sets the settings for the plugin.
   *
   * @param array $settings
   *   The array of settings, keyed by setting names. Missing settings will be
   *   assigned their default values.
   *
   * @return $this
   */
  public function setSettings(array $settings): PluginSettingsInterface;

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
  public function settingsForm(array $form, FormStateInterface $form_state): array;

  /**
   * Returns a short summary for the current settings.
   *
   * @return array<string|\Stringable>
   *   A short summary of the settings.
   */
  public function settingsSummary(): array;

}
