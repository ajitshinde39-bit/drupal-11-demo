<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\ui_patterns\SourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for source plugins derived from field properties.
 */
abstract class FieldValueSourceBase extends FieldSourceBase implements SourceInterface {

  use LoggerChannelTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    // We keep the same constructor as SourcePluginBase.
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    // Defined in parent class FieldSourceBase.
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Returns the entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity
   */
  protected function getEntity(): ?EntityInterface {
    $entity = parent::getEntity();
    if ($entity instanceof EntityInterface) {
      return $entity;
    }
    if (isset($this->context["ui_patterns:field:items"])) {
      // Useful in the context of views.
      $field_items = $this->getContextValue("ui_patterns:field:items");
      if ($field_items instanceof FieldItemListInterface) {
        return $field_items->getEntity();
      }
    }
    return NULL;
  }

  /**
   * Gets a field item list for the entity and field name in the context.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface|mixed|null
   *   Return the field items of entity.
   */
  protected function getEntityFieldItemList():mixed {
    $field_name = $this->getCustomPluginMetadata('field_name');
    if (empty($field_name)) {
      return NULL;
    }
    /** @var  \Drupal\Core\Entity\ContentEntityBase $entity */
    $entity = $this->getEntity();
    if (!$entity && isset($this->context["ui_patterns:field:items"])) {
      $field_items = $this->getContextValue('ui_patterns:field:items');
      if ($field_items instanceof FieldItemListInterface) {
        if ($field_items->getFieldDefinition()->getName() == $field_name) {
          return $field_items;
        }
        $entity = $field_items->getEntity();
      }
    }
    if (!$entity) {
      $this->getLogger('ui_patterns')
        ->error('Entity not found in context');
      return NULL;
    }

    if (!$entity->hasField($field_name)) {
      $this->getLogger('ui_patterns')
        ->error('Entity %entity_type %bundle has no field %field_name', [
          '%entity_type' => $entity->getEntityTypeId(),
          '%bundle' => $entity->bundle() ?? "",
          '%field_name' => $field_name,
        ]);
      return NULL;
    }
    return $entity->get($field_name);
  }

  /**
   * Get the settings from the plugin configuration.
   *
   * @param array $parents
   *   An array of parent keys of the value, starting with the outermost key.
   *
   * @return mixed|null
   *   The requested nested value from configuration,
   */
  protected function getSettingsFromConfiguration(array $parents) {
    $configuration = $this->getConfiguration();
    return NestedArray::getValue($configuration, $parents);
  }

}
