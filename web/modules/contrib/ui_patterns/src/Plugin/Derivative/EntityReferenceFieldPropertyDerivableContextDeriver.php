<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;

/**
 * Provides derivable context for every field referencing an entity.
 */
class EntityReferenceFieldPropertyDerivableContextDeriver extends EntityFieldSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityBundleField(string $entity_type_id, string $bundle, string $field_name, array $base_plugin_derivative): void {
    // Check if the field is an entity reference field and has no storage.
    // Fields with storage are handled by
    // `self::getDerivativeDefinitionsForEntityStorageFieldProperty()`.
    if (
      !empty($this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name]) ||
      empty($this->entityFieldsMetadata[$entity_type_id]["bundles"][$bundle]["fields"][$field_name]["entity_reference"])) {
      return;
    }
    $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      $entity_type_id,
      $bundle,
      $field_name,
      "target_id",
    ]);
    $this->derivatives[$id] = array_merge(
      $base_plugin_derivative,
      [
        "id" => $id,
        "label" => $this->t("[Field item] ➜ Referenced [Entity]"),
        "context_requirements" => array_merge($base_plugin_derivative["context_requirements"], ["field_granularity:item"]),
        "tags" => array_merge($base_plugin_derivative["tags"], ['context_switcher']),
      ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityStorageFieldProperty(string $entity_type_id, string $field_name, string $property, array $base_plugin_derivative): void {
    $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
      $entity_type_id,
      $field_name,
      $property,
    ]);
    if (!$this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name]["properties"][$property]['entity_reference']) {
      return;
    }
    $this->derivatives[$id] = array_merge(
        $base_plugin_derivative,
        [
          "id" => $id,
          "label" => $this->t("[Field item] ➜ Referenced [Entity]"),
          "context_requirements" => array_merge($base_plugin_derivative["context_requirements"], ["field_granularity:item"]),
          "tags" => array_merge($base_plugin_derivative["tags"], ['context_switcher']),
        ]);
  }

}
