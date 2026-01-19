<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItemInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class to create plugin derivers for entity fields.
 */
abstract class EntityFieldSourceDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;
  use LoggerChannelTrait;

  /**
   * The metadata for each entity and each field.
   *
   * @var array<string, array<mixed> >
   */
  protected array $entityFieldsMetadata = [];

  /**
   * Constructs new FieldBlockDeriver.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   The entity field manager.
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface $typedDataManager
   *   The typed data manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   The entity type bundle info.
   */
  public function __construct(
    protected EntityFieldManagerInterface $entityFieldManager,
    protected TypedDataManagerInterface $typedDataManager,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    $this->entityFieldsMetadata = $this->getEntityFieldsMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('typed_data_manager'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * Get entity fields classification.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type_definition
   *   The entity type definition.
   *
   * @return array
   *   The classification of the fields.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  protected function getEntityFieldsClassification(EntityTypeInterface $entity_type_definition) {
    // Location of field definitions for this entity.
    $entity_type_class = $entity_type_definition->getClass();
    $entity_type_parent_class = get_parent_class($entity_type_class);
    $fields_editorial = [];
    $parents_base = [];
    $fields_base = [];
    if (is_subclass_of($entity_type_class, EditorialContentEntityBase::class)) {
      $fields_editorial = array_merge($fields_editorial, array_keys($entity_type_class::revisionLogBaseFieldDefinitions($entity_type_definition) ?? []));
    }
    if (is_subclass_of($entity_type_class, EntityPublishedTrait::class)) {
      $fields_editorial = array_merge($fields_editorial, array_keys($entity_type_class::publishedBaseFieldDefinitions($entity_type_definition) ?? []));
    }
    if ($entity_type_parent_class && is_subclass_of($entity_type_parent_class, FieldableEntityInterface::class)) {
      $parents_base = array_keys($entity_type_parent_class::baseFieldDefinitions($entity_type_definition) ?? []);
      $fields_base = array_keys($entity_type_class::baseFieldDefinitions($entity_type_definition) ?? []);
    }
    return [
      "fields_editorial" => $fields_editorial,
      "parents_base" => $parents_base,
      "fields_base" => $fields_base,
    ];
  }

  /**
   * Get entity field storage metadata.
   *
   * @param array $field_storage_definitions
   *   The field storage definitions.
   * @param array $entity_field_map
   *   The entity field map.
   * @param array $entityFieldsClassification
   *   The classification of the fields.
   *
   * @return array
   *   The metadata for each field.
   */
  protected function getEntityFieldStorageMetadata(
    array $field_storage_definitions,
    array $entity_field_map,
    array $entityFieldsClassification,
  ) {
    $returned = [];
    // Field storage definitions.
    foreach ($entity_field_map as $field_name => $field_info) {
      if (!array_key_exists($field_name, $field_storage_definitions)) {
        continue;
      }
      /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
      $field_storage_definition = $field_storage_definitions[$field_name];
      $main_property_name = (is_object($field_storage_definition) && method_exists($field_storage_definition, "getMainPropertyName")) ? $field_storage_definition->getMainPropertyName() : NULL;
      $is_base = (in_array($field_name, $entityFieldsClassification["fields_base"]) || ($field_storage_definition instanceof BaseFieldDefinition));
      $returned[$field_name] = [
        "label" => $field_storage_definition->getLabel(),
        "bundles" => array_values($field_info['bundles'] ?? []),
        "metadata" => [
          "type" => $field_storage_definition->getType(),
          "configurable" => ($field_storage_definition instanceof FieldStorageConfig),
          "editorial" => in_array($field_name, $entityFieldsClassification["fields_editorial"]),
          "parent_base" => in_array($field_name, $entityFieldsClassification["parents_base"]),
          "base" => $is_base,
          "cardinality" => $field_storage_definition->getCardinality(),
        ],
        "provider" => $field_storage_definition->getProvider(),
        "main_property" => $main_property_name,
        'config_dependencies' => [],
        'properties' => [],
      ];
      // Derive for each property, property information.
      foreach ($field_storage_definition->getPropertyDefinitions() as $property_id => $property_definition) {
        // Skip entity reference
        // Description could have been more precise,
        // but at the price of loading lot of stuff for nothing.
        $returned[$field_name]["properties"][$property_id] = [
          "label" => $this->t("[Field item] @property", ["@property" => $property_definition->getLabel()]),
          "description" => $this->t('Property "@property" of field "@field', [
            '@property' => $property_definition->getLabel(),
            '@field' => $field_storage_definition->getLabel(),
          ]),
          "data_type" => $property_definition->getDataType(),
          "entity_reference" => (($main_property_name === $property_id) && ($property_definition instanceof DataReferenceTargetDefinition)),
        ];
      }
    }
    return $returned;
  }

  /**
   * Get entity bundle field metadata.
   *
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $item_definition
   *   The item definition.
   * @param array $entityFieldsClassification
   *   The classification of the fields.
   *
   * @return array
   *   The metadata for the field.
   */
  protected function getEntityBundleFieldMetadata(
    string $field_name,
    $field_definition,
    $item_definition,
    array $entityFieldsClassification,
  ) {
    $main_property_name = (method_exists($item_definition, "getMainPropertyName")) ? $item_definition->getMainPropertyName() : NULL;
    $field_storage_definition = $field_definition->getFieldStorageDefinition();
    $returned = [
      "label" => $field_definition->getLabel(),
      "config_dependencies" => [],
      "metadata" => [
        "configurable" => ($field_definition instanceof FieldConfigInterface),
        "editorial" => in_array($field_name, $entityFieldsClassification["fields_editorial"]),
        "parent_base" => in_array($field_name, $entityFieldsClassification["parents_base"]),
        "base" => in_array($field_name, $entityFieldsClassification["fields_base"]) || ($field_definition instanceof BaseFieldDefinition),
        "cardinality" => $field_storage_definition->getCardinality(),
      ],
      "provider" => $field_storage_definition->getProvider(),
      "main_property" => $main_property_name,
    ];
    // Config dependencies.
    if ($field_definition instanceof FieldConfigInterface) {
      $returned['config_dependencies'][$field_definition->getConfigDependencyKey()][] =
        $field_definition->getConfigDependencyName();
    }
    return $returned;
  }

  /**
   * Get entity bundle field metadata for entity reference.
   *
   * @param string|null $main_property_name
   *   The main property name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $item_definition
   *   The item definition.
   * @param array $entity_type_definitions
   *   The entity type definitions.
   *
   * @return array
   *   The metadata for the entity reference.
   */
  protected function getEntityBundleFieldMetadataEntityReference(
    ?string $main_property_name,
    $field_definition,
    $item_definition,
    array $entity_type_definitions,
  ) {
    $returned = [];
    $main_property_definition = ($main_property_name && method_exists($item_definition, "getPropertyDefinition")) ? $item_definition->getPropertyDefinition($main_property_name) : NULL;
    // Entity reference.
    if ($main_property_definition instanceof DataReferenceTargetDefinition) {
      $target_entity_type_id = $item_definition->getSetting('target_type');
      $target_entity_type_definition = $entity_type_definitions[$target_entity_type_id] ?? NULL;
      $returned = [
        "entity_type_id" => $target_entity_type_id,
        "fieldable" => $target_entity_type_definition ? $target_entity_type_definition->entityClassImplements(FieldableEntityInterface::class) : FALSE,
        "bundles" => [],
      ];
      $target_type_to_bundles = NULL;
      $item_class = $item_definition->getClass();
      if (is_subclass_of($item_class, EntityReferenceItemInterface::class)) {
        $target_type_to_bundles = $item_class::getReferenceableBundles($field_definition);
      }
      if ($target_type_to_bundles && array_key_exists($target_entity_type_id, $target_type_to_bundles)) {
        $target_bundles = array_values($target_type_to_bundles[$target_entity_type_id]);
        $returned['bundles'] = $target_bundles;
      }
    }
    return $returned;
  }

  /**
   * Get entity bundle fields metadata.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $field_storage_definitions
   *   The field storage definitions.
   * @param array $entity_type_definitions
   *   The entity type definitions.
   * @param array $entityFieldsClassification
   *   The classification of the fields.
   *
   * @return array
   *   The metadata for each bundle and each field.
   */
  protected function getEntityBundleFieldsMetadata(
    string $entity_type_id,
    array $field_storage_definitions,
    array $entity_type_definitions,
    array $entityFieldsClassification,
  ) : array {
    $returned = [];
    // Derive for each bundle, field information.
    $bundle_list = $this->entityTypeBundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundle_list as $bundle => $bundle_info) {
      $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
      // ------
      $returned[$bundle] = [
        "label" => $bundle_info["label"],
        "fields" => [],
      ];
      foreach ($field_definitions as $field_name => $field_definition) {
        if (!array_key_exists($field_name, $field_storage_definitions) && !$field_definition->isComputed()) {
          continue;
        }
        $item_definition = $field_definition->getItemDefinition();
        $returned[$bundle]["fields"][$field_name] =
          $this->getEntityBundleFieldMetadata($field_name, $field_definition, $item_definition, $entityFieldsClassification);
        $returned[$bundle]["fields"][$field_name]["entity_reference"] =
          $this->getEntityBundleFieldMetadataEntityReference($returned[$bundle]["fields"][$field_name]["main_property"], $field_definition, $item_definition, $entity_type_definitions);
      }
    }
    return $returned;
  }

  /**
   * Get data about entity fields.
   *
   * @return array
   *   The metadata for each entity and each field.
   *
   * @throws \Drupal\Core\Entity\Exception\UnsupportedEntityTypeDefinitionException
   */
  protected function getEntityFieldsMetadata() : array {
    $fields_metadata = [];
    $entity_type_definitions = $this->entityTypeManager->getDefinitions();
    $all_entity_field_map = $this->entityFieldManager->getFieldMap();
    foreach ($entity_type_definitions as $entity_type_id => $entity_type_definition) {
      $fields_metadata[$entity_type_id] = [
        "fieldable" => $entity_type_definition->entityClassImplements(FieldableEntityInterface::class),
        "label" => $entity_type_definition->getLabel(),
      ];
      if (!$fields_metadata[$entity_type_id]["fieldable"]) {
        continue;
      }
      $entity_field_map = $all_entity_field_map[$entity_type_id] ?? NULL;
      if (!is_array($entity_field_map)) {
        continue;
      }
      $entityFieldsClassification = $this->getEntityFieldsClassification($entity_type_definition);
      $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      $fields_metadata[$entity_type_id]["field_storages"] = $this->getEntityFieldStorageMetadata($field_storage_definitions, $entity_field_map, $entityFieldsClassification);
      $fields_metadata[$entity_type_id]["bundles"] = $this->getEntityBundleFieldsMetadata($entity_type_id, $field_storage_definitions, $entity_type_definitions, $entityFieldsClassification);
      foreach ($fields_metadata[$entity_type_id]["bundles"] as $bundle_data) {
        foreach ($bundle_data["fields"] as $field_name => $field_data) {
          if (isset($field_data["config_dependencies"])) {
            foreach ($field_data["config_dependencies"] as $config_dependency_key => $config_dependency_names) {
              if (!isset($fields_metadata[$entity_type_id]["field_storages"][$field_name]['config_dependencies'][$config_dependency_key])) {
                $fields_metadata[$entity_type_id]["field_storages"][$field_name]['config_dependencies'][$config_dependency_key] = [];
              }
              $fields_metadata[$entity_type_id]["field_storages"][$field_name]['config_dependencies'][$config_dependency_key][] = array_values($config_dependency_names)[0];
            }
          }
        }
      }
    }
    return $fields_metadata;
  }

  /**
   * Get derivative definitions for entity bundles.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $base_plugin_definition
   *   The base plugin definition.
   */
  protected function getDerivativeDefinitionsForEntityBundles(string $entity_type_id, array $base_plugin_definition): void {
    if (!isset($this->entityFieldsMetadata[$entity_type_id])) {
      return;
    }
    $entity_type_fields_data = $this->entityFieldsMetadata[$entity_type_id];
    $entity_context = EntityContextDefinition::fromEntityTypeId($entity_type_id)
      ->setRequired()
      ->setLabel((string) ($entity_type_fields_data["label"] ?? ""));
    if (!isset($entity_type_fields_data["bundles"]) || !is_array($entity_type_fields_data["bundles"])) {
      return;
    }
    // Derive for each bundle.
    foreach ($entity_type_fields_data["bundles"] as $bundle => $bundle_data) {
      $bundle_context = (new ContextDefinition('string'))
        ->setRequired()
        ->setLabel((string) ($bundle_data["label"] ?? ""))
        ->addConstraint('AllowedValues', [$bundle]);
      foreach ($bundle_data["fields"] as $field_name => $field_data) {
        $field_name_context = (new ContextDefinition('string'))
          ->setRequired()
          ->setLabel("field_name")
          ->setDefaultValue($field_name)
          ->addConstraint('AllowedValues', [$field_name]);
        $base_plugin_derivative = array_merge($base_plugin_definition, [
          'label' => $field_data["label"],
          'context_definitions' => [
            'entity' => $entity_context,
            'bundle' => $bundle_context,
            'field_name' => $field_name_context,
          ],
          'metadata' => [
            "field" => $field_data["metadata"],
            'field_name' => $field_name,
            "entity_type_id" => $entity_type_id,
            "entity_bundle" => $bundle,
            "provider" => $field_data["provider"] ?? NULL,
          ],
          "tags" => ["entity", "field"],
          'context_requirements' => [],
          'config_dependencies' => array_merge($base_plugin_definition['config_dependencies'], $field_data['config_dependencies']),
        ]);
        $this->getDerivativeDefinitionsForEntityBundleField($entity_type_id, $bundle, $field_name, $base_plugin_derivative);
      }
    }
  }

  /**
   * Get derivative definitions for entity field storages & properties.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param array $base_plugin_definition
   *   The base plugin definition.
   */
  protected function getDerivativeDefinitionsForEntityFieldStorages(string $entity_type_id, array $base_plugin_definition): void {
    if (!isset($this->entityFieldsMetadata[$entity_type_id])) {
      return;
    }
    $entity_type_fields_data = $this->entityFieldsMetadata[$entity_type_id];
    $entity_context = EntityContextDefinition::fromEntityTypeId($entity_type_id)
      ->setRequired()
      ->setLabel((string) ($entity_type_fields_data["label"] ?? ""));
    // Derive when bundle is unknown (in views for example)
    foreach (($entity_type_fields_data["field_storages"] ?? []) as $field_name => $field_storage_data) {
      if (!isset($field_storage_data["label"])) {
        // During site install $field_storage_data is not setup completed.
        // Skip for now.
        continue;
      }
      $field_name_context = (new ContextDefinition('string'))
        ->setRequired()
        ->setLabel("field_name")
        ->setDefaultValue($field_name)
        ->addConstraint('AllowedValues', [$field_name]);
      $bundle_context = (new ContextDefinition('string'))
        ->setRequired()
        ->setLabel("Bundle")
        ->addConstraint('AllowedValues', [""]);
      $base_plugin_derivative = array_merge($base_plugin_definition, [
        'label' => $field_storage_data["label"],
        'context_definitions' => [
          'entity' => $entity_context,
          'bundle' => $bundle_context,
          'field_name' => $field_name_context,
        ],
        "tags" => ["entity", "field", "field_storage"],
        'metadata' => [
          "field" => $field_storage_data["metadata"],
          'field_name' => $field_name,
          "entity_type_id" => $entity_type_id,
          "provider" => $field_storage_data["provider"] ?? NULL,
        ],
        'context_requirements' => [],
        'config_dependencies' => array_merge($base_plugin_definition['config_dependencies'], $field_storage_data['config_dependencies']),
      ]);
      $this->getDerivativeDefinitionsForEntityStorageField($entity_type_id, $field_name, $base_plugin_derivative);
      // Derive for each property.
      $bundle_context_for_properties = (new ContextDefinition('string'))
        ->setRequired()
        ->setLabel("Bundle")
        ->addConstraint('AllowedValues', array_merge($field_storage_data["bundles"] ?? [], [""]));
      foreach ($field_storage_data["properties"] as $property_id => $property_data) {

        $base_plugin_derivative = array_merge($base_plugin_definition, [
          'label' => $property_data["label"],
          'context_definitions' => [
            'entity' => $entity_context,
            'bundle' => $bundle_context_for_properties,
            'field_name' => $field_name_context,
          ],
          'metadata' => [
            "field" => $field_storage_data["metadata"],
            'field_name' => $field_name,
            "property" => $property_id,
            "entity_type_id" => $entity_type_id,
            "provider" => $field_storage_data["provider"] ?? NULL,
          ],
          "tags" => ["entity", "field", "field_property"],
          'context_requirements' => [],
          'config_dependencies' => array_merge($base_plugin_definition['config_dependencies'], $field_storage_data['config_dependencies']),
        ]);
        $this->getDerivativeDefinitionsForEntityStorageFieldProperty($entity_type_id, $field_name, $property_id, $base_plugin_derivative);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    if (!array_key_exists('config_dependencies', $base_plugin_definition) || !is_array($base_plugin_definition['config_dependencies'])) {
      $base_plugin_definition['config_dependencies'] = [];
    }
    foreach ($this->entityFieldsMetadata as $entity_type_id => $entity_type_fields_data) {
      if (!$entity_type_fields_data["fieldable"]) {
        continue;
      }
      $this->getDerivativeDefinitionsForEntityBundles($entity_type_id, $base_plugin_definition);
      $this->getDerivativeDefinitionsForEntityFieldStorages($entity_type_id, $base_plugin_definition);
    }
    return $this->derivatives;
  }

  /**
   * Get derivative definitions per entity bundle field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   * @param array $base_plugin_derivative
   *   The base plugin derivative.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function getDerivativeDefinitionsForEntityBundleField(string $entity_type_id, string $bundle, string $field_name, array $base_plugin_derivative): void {
  }

  /**
   * Get derivative definitions per entity field storage.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field name.
   * @param array $base_plugin_derivative
   *   The base plugin derivative.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function getDerivativeDefinitionsForEntityStorageField(string $entity_type_id, string $field_name, array $base_plugin_derivative): void {
  }

  /**
   * Get derivative definitions per entity field storage.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field name.
   * @param string $property
   *   The property.
   * @param array $base_plugin_derivative
   *   The base plugin derivative.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  protected function getDerivativeDefinitionsForEntityStorageFieldProperty(string $entity_type_id, string $field_name, string $property, array $base_plugin_derivative): void {

  }

}
