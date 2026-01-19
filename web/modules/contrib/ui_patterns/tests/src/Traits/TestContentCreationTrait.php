<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Traits;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManager;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Entity test data trait.
 */
trait TestContentCreationTrait {

  use NodeCreationTrait {
    createNode as drupalCreateNode;
  }

  /**
   * Returns the field type plugin manager.
   */
  protected function getFieldTypePluginManager(): FieldTypePluginManager {
    return \Drupal::service('plugin.manager.field.field_type');
  }

  /**
   * Set up test node.
   *
   * @param string $bundle
   *   The bundle name.
   * @param array $values
   *   Values applied to create method of the node.
   */
  protected function createTestContentNode(string $bundle = 'page', array $values = []): NodeInterface {
    $this->createTestContentContentType($bundle);
    $node = $this->drupalCreateNode(['type' => $bundle]);
    foreach ($values as $field_name => $field_values) {
      // Get all keys of the array.
      $keys = array_keys($field_values);
      if (count($keys) === count(array_filter($keys, 'is_int')) && count($keys) > 1) {
        foreach ($field_values as $field_value) {
          $node->get($field_name)->appendItem($field_value);
        }
      }
      else {
        $node->set($field_name, $field_values);
      }
    }
    $node->save();
    return $node;
  }

  /**
   * Creates content type with fields foreach field type.
   *
   * The created field names are:
   *   - field_"type" with cardinality -1
   *   - field_"type"_1 with cardinality 1
   */
  protected function createTestContentContentType($bundle = 'page'): NodeType {
    if ($type = NodeType::load($bundle)) {
      return $type;
    }

    $type = NodeType::create([
      'name' => $bundle,
      'type' => $bundle,
    ]);
    $type->save();

    $this->createEntityField($type->getEntityType()->getBundleOf(), $bundle, 'body', 'text_long', 1);

    $field_types = $this->getFieldTypePluginManager()->getDefinitions();

    foreach (array_keys($field_types) as $field_type_id) {
      if ($field_type_id === 'uuid') {
        continue;
      }
      $field_name = sprintf("field_%s_%s", $field_type_id, 1);
      $bundle = (string) $type->id();
      $this->createEntityField($type->getEntityType()->getBundleOf(), $bundle, $field_name, $field_type_id, 1);
      $field_name = sprintf("field_%s", $field_type_id);
      $this->createEntityField($type->getEntityType()->getBundleOf(), $bundle, $field_name, $field_type_id,
          FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    }
    return $type;
  }

  /**
   * Create a field on a content type.
   */
  protected function createEntityField(
    string $entity_type,
    string $bundle,
    string $field_name,
    string $field_type_id,
    int $cardinality = FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
  ) : FieldConfigInterface {
    $field_type_definition = $this->getFieldTypePluginManager()->getDefinition($field_type_id);
    // Create a field storage.
    $existing_field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);
    if ($existing_field_storage === NULL) {
      $field_storage = [
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'type' => $field_type_id,
        'settings' => [],
        'cardinality' => $cardinality,
      ];
      FieldStorageConfig::create($field_storage)->save();
    }
    else {
      $field_storage = $existing_field_storage->toArray();
    }

    // Create a field instance on the content type.
    $field = [
      'field_name' => $field_storage['field_name'],
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'label' => $field_type_definition['label'],
      'settings' => [],
    ];
    $field_config = FieldConfig::create($field);
    $field_config->save();
    // Set cardinality.
    $field_storage_reload = FieldStorageConfig::loadByName($entity_type, $field_name);
    $field_storage_reload->setCardinality($cardinality);
    $field_storage_reload->save();
    return $field_config;
  }

}
