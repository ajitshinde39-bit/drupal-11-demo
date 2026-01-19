<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;

/**
 * Provides derivable context for every field from referenced entities.
 */
class EntityReferencedDerivableContextDeriver extends EntityFieldSourceDeriverBase {

  /**
   * {@inheritdoc}
   */
  protected function getDerivativeDefinitionsForEntityBundleField(string $entity_type_id, string $bundle, string $field_name, array $base_plugin_derivative): void {
    $entity_reference_data = $this->getReferenceableEntityData($entity_type_id, $bundle, $field_name);
    if (empty($entity_reference_data)) {
      return;
    }
    $target_entity_type_id = $entity_reference_data["entity_type_id"];
    $target_bundles = $entity_reference_data["bundles"];
    if (count($target_bundles) > 1) {
      $target_bundles[] = "";
    }
    unset($base_plugin_derivative["context_definitions"]["field_name"]);
    if (!isset($base_plugin_derivative['tags']) || !is_array($base_plugin_derivative['tags'])) {
      $base_plugin_derivative['tags'] = [];
    }
    $base_plugin_derivative['tags'][] = "entity_referenced";
    $base_plugin_derivative['tags'] = array_filter($base_plugin_derivative['tags'], function ($tag) {
      return $tag !== "field";
    });
    $this->generateDefinitionsForReferencedEntityTypes($entity_type_id, $bundle, $field_name, $base_plugin_derivative, $target_entity_type_id, $target_bundles);
  }

  /**
   * Get referenceable entity data for a field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return array|null
   *   The entity reference data or NULL.
   */
  private function getReferenceableEntityData(string $entity_type_id, string $bundle, string $field_name): ?array {
    $entity_reference_data = $this->entityFieldsMetadata[$entity_type_id]["bundles"][$bundle]["fields"][$field_name]["entity_reference"];
    if (!is_array($entity_reference_data) || (count($entity_reference_data) === 0) ||
      !isset($entity_reference_data["fieldable"]) || !$entity_reference_data["fieldable"] ||
      !isset($entity_reference_data["bundles"]) || (count($entity_reference_data["bundles"]) === 0)) {
      return NULL;
    }
    return $entity_reference_data;
  }

  /**
   * Get entity bundle labels.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $bundles
   *   The bundles.
   *
   * @return array
   *   The entity bundle labels.
   */
  private function getEntityBundleLabels(string $entity_type_id, array $bundles): array {
    $target_bundle_to_label = [];
    foreach ($bundles as $target_bundle) {
      $target_entity_type_fields_data = $this->entityFieldsMetadata[$entity_type_id];
      $target_bundle_data = [];
      if (isset($target_entity_type_fields_data["bundles"]) && is_array($target_entity_type_fields_data["bundles"])) {
        $target_bundle_data = $target_entity_type_fields_data["bundles"][$target_bundle] ?? [];
      }
      $target_bundle_to_label[$target_bundle] = $target_bundle_data["label"] ?? $this->entityFieldsMetadata[$entity_type_id]["label"];
    }
    return $target_bundle_to_label;
  }

  /**
   * Generate definitions for referenced entity types.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param array $base_plugin_derivative
   *   The base plugin derivative.
   * @param string $target_entity_type_id
   *   The target entity type id.
   * @param array $target_bundles
   *   The target bundles.
   */
  private function generateDefinitionsForReferencedEntityTypes(string $entity_type_id, string $bundle, string $field_name, array $base_plugin_derivative, string $target_entity_type_id, array $target_bundles): void {
    $target_bundle_to_label = $this->getEntityBundleLabels($target_entity_type_id, $target_bundles);
    $base_label = $base_plugin_derivative["label"];
    $target_entity_label = $this->entityFieldsMetadata[$target_entity_type_id]["label"] ?? "";
    $no_bundle_context = (new ContextDefinition('string'))
      ->setRequired()
      ->setLabel("Bundle")
      ->addConstraint('AllowedValues', [""]);
    $entity_context = EntityContextDefinition::fromEntityTypeId($entity_type_id)
      ->setRequired()
      ->setLabel((string) ($this->entityFieldsMetadata[$entity_type_id]["label"] ?? ""));
    foreach ($target_bundle_to_label as $target_bundle => $entity_bundle_label) {
      $id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
        $entity_type_id,
        $bundle,
        $field_name,
        $target_entity_type_id,
        $target_bundle,
      ]);
      $bundle_label = ($target_bundle !== "" && (count($target_bundle_to_label) > 1)) ? $entity_bundle_label : sprintf("%s (%s)", $entity_bundle_label, $target_entity_label);
      $this->derivatives[$id] = array_merge($base_plugin_derivative, [
        "id" => $id,
        "label" => $this->t("@bundle referenced by @field", [
          "@field" => $base_label,
          "@bundle" => ($target_bundle == "") ? $target_entity_label : $bundle_label,
        ]),
      ]);
      // Check plugin exists for host entity type without bundle.
      $id_no_bundle = implode(PluginBase::DERIVATIVE_SEPARATOR, [
        $entity_type_id,
        "",
        $field_name,
        $target_entity_type_id,
        $target_bundle,
      ]);
      if (!isset($this->derivatives[$id_no_bundle]) && isset($this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name])) {
        $field_storage_metadata = $this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name];
        $this->derivatives[$id_no_bundle] = array_merge($this->derivatives[$id], [
          "id" => $id_no_bundle,
          "label" => $this->t("@target referenced by @field", [
            "@field" => $field_storage_metadata["label"] ?? $field_name,
            "@target" => $target_entity_label,
          ]),
        ]);
        $this->derivatives[$id_no_bundle]["context_definitions"]["bundle"] = $no_bundle_context;
        $this->derivatives[$id_no_bundle]["context_definitions"]["entity"] = $entity_context;
      }
    }
  }

}
