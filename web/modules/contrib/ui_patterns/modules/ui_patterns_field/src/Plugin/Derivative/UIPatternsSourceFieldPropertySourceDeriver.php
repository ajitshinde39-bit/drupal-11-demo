<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ui_patterns\Plugin\Derivative\EntityFieldSourceDeriverBase;

/**
 * Provides Plugin for every field property of type ui_patterns_source.
 */
class UIPatternsSourceFieldPropertySourceDeriver extends EntityFieldSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityStorageField(string $entity_type_id, string $field_name, array $base_plugin_derivative): void {
    $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      $entity_type_id,
      $field_name,
    ]);
    $field_type = $this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name]["metadata"]["type"];
    if ($field_type === "ui_patterns_source") {
      $this->derivatives[$id] = array_merge(
        $base_plugin_derivative,
        [
          "id" => $id,
          "tags" => array_merge($base_plugin_derivative["tags"], ["ui_patterns_source"]),
        ]);
      $field_storage_data = $this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name];
      $bundle_context_for_properties = (new ContextDefinition('string'))
        ->setRequired()
        ->setLabel("Bundle")
        ->addConstraint('AllowedValues', array_merge($field_storage_data["bundles"] ?? [], [""]));
      $this->derivatives[$id]["context_definitions"]["bundle"] = $bundle_context_for_properties;
    }

  }

}
