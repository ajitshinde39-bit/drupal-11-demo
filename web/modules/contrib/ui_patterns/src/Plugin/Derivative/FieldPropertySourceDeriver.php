<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\Derivative;

use Drupal\Component\Plugin\PluginBase;
use Drupal\ui_patterns\PropTypePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides derivable context for every field property.
 */
class FieldPropertySourceDeriver extends EntityFieldSourceDeriverBase {
  /**
   * The ui patterns prop type plugin manager.
   *
   * @var \Drupal\ui_patterns\PropTypePluginManager
   */
  protected ?PropTypePluginManager $propTypePluginManager = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    $plugin = parent::create($container, $base_plugin_id);
    $plugin->propTypePluginManager = $container->get('plugin.manager.ui_patterns_prop_type');
    return $plugin;
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
    $prop_types = $this->propTypePluginManager->getAllPropTypeByTypedData(
      $this->entityFieldsMetadata[$entity_type_id]["field_storages"][$field_name]["properties"][$property]["data_type"]);
    if (count($prop_types) > 0) {
      $this->derivatives[$id] = array_merge(
        $base_plugin_derivative,
        [
          "id" => $id,
          "prop_types" => $prop_types,
          "context_requirements" => array_merge($base_plugin_derivative["context_requirements"], ["field_granularity:item"]),
        ]);
    }

  }

}
