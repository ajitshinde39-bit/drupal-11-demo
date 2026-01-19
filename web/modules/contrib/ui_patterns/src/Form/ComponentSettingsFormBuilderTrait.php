<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Builder for setting forms like field formatter.
 */
trait ComponentSettingsFormBuilderTrait {

  use ComponentFormBuilderTrait;

  /**
   * Adapter function for plugin settings/options.
   *
   * Overwrite to return settings/options of the
   * current plugin.
   *
   * @return mixed
   *   The plugin settings/options.
   */
  abstract protected function getComponentSettings(): array;

  /**
   * {@inheritdoc}
   */
  protected function getComponentConfiguration(string $configuration_id = 'ui_patterns'): array {
    return $this->getComponentSettings()[$configuration_id] ?? [];
  }

  /**
   * Returns the form for the component settings.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array<string, \Drupal\Core\Plugin\Context\ContextInterface> $source_contexts
   *   The source contexts.
   * @param string $configuration_id
   *   The configuration id.
   *
   * @return array
   *   The form.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function componentSettingsForm(
    array $form,
    FormStateInterface $form_state,
    array $source_contexts = [],
    string $configuration_id = 'ui_patterns',
  ): array {
    return $this->buildComponentsForm($form_state, $source_contexts, NULL, TRUE, TRUE, $configuration_id);
  }

}
