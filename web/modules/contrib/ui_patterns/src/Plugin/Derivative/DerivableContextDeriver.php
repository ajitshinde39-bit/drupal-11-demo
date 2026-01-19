<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides derivable context for every field.
 */
class DerivableContextDeriver extends EntityFieldSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityBundleField(string $entity_type_id, string $bundle, string $field_name, array $base_plugin_derivative): void {
    $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      $entity_type_id,
      $bundle,
      $field_name,
    ]);
    unset($base_plugin_derivative["context_definitions"]["field_name"]);
    $this->derivatives[$id] = array_merge(
      $base_plugin_derivative,
      ["id" => $id]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityStorageField(string $entity_type_id, string $field_name, array $base_plugin_derivative): void {
    $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      $entity_type_id,
      "",
      $field_name,
    ]);
    unset($base_plugin_derivative["context_definitions"]["field_name"]);
    $this->derivatives[$id] = array_merge(
      $base_plugin_derivative,
      ["id" => $id]);
  }

}
