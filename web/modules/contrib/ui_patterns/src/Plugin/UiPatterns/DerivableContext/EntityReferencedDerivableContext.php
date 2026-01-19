<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\DerivableContext;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Plugin\DataType\EntityReference;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\DerivableContext;
use Drupal\ui_patterns\DerivableContextPluginBase;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Drupal\ui_patterns\Plugin\Derivative\EntityReferencedDerivableContextDeriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derivable context plugins for entity Reference fields.
 */
#[DerivableContext(
  id: 'entity_reference',
  label: new TranslatableMarkup('Entity Referenced from fields'),
  description: new TranslatableMarkup('Derived contexts for Entity Reference Fields.'),
  deriver: EntityReferencedDerivableContextDeriver::class
)]
class EntityReferencedDerivableContext extends DerivableContextPluginBase {


  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The sample entity generator.
   *
   * @var \Drupal\ui_patterns\Entity\SampleEntityGenerator
   */
  protected $sampleEntityGenerator;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.repository'),
    );
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->sampleEntityGenerator = $container->get('ui_patterns.sample_entity_generator');
    $instance->logger = $container->get('logger.channel.ui_patterns');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivedContexts(): array {
    $referenced_entities = $this->getEntities();
    if (empty($referenced_entities)) {
      return [];
    }
    $removed_context_keys = ["entity", "ui_patterns:field:", "bundle"];
    $base_context = array_filter($this->context, function ($one_context, $one_context_id) use (&$removed_context_keys) {
      if (in_array($one_context_id, $removed_context_keys)) {
        return FALSE;
      }
      foreach ($removed_context_keys as $removed_context_key) {
        if (str_starts_with($one_context_id, $removed_context_key)) {
          return FALSE;
        }
      }
      return TRUE;
    }, ARRAY_FILTER_USE_BOTH);
    $base_context = RequirementsContext::removeFromContext(["field_granularity:item"], $base_context);
    $metadata = $this->getMetadata();
    $entity_type_id = $metadata["entity_type_id"];
    $bundle = $metadata["bundle"];
    // Bundle context definition.
    $bundle_context_definition = new ContextDefinition("string", "Bundle");
    $base_context['bundle'] = new Context($bundle_context_definition, $bundle);
    // Entity context definition.
    $entity_context_definition = new EntityContextDefinition($entity_type_id);
    if (!empty($bundle)) {
      $entity_context_definition->addConstraint('Bundle', [$bundle]);
    }

    // Generate the contexts.
    $returned_contexts = [];
    foreach ($referenced_entities as $referenced_entity) {
      $returned_contexts[] = array_merge($base_context, [
        "entity" => new Context($entity_context_definition, $referenced_entity),
      ]);
    }
    return $returned_contexts;
  }

  /**
   * Get metadata about the current derivable context.
   *
   * @return array
   *   Metadata about the current derivable context
   */
  protected function getMetadata() {
    // Base, entity_type, bundle, field name, target_entity_type, target_bundle.
    $split_plugin_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $this->getPluginId());
    [$bundle, $entity_type_id, $ref_field_name] = array_slice(array_reverse($split_plugin_id), 0, 3);
    // Bundle context definition.
    return [
      "entity_type_id" => $entity_type_id,
      "bundle" => $bundle,
      "field_name" => $ref_field_name,
      "parent_entity_type_id" => $split_plugin_id[1],
    ];
  }

  /**
   * Retrieve the entity from the context.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity.
   */
  protected function getEntityFromContext() : ?EntityInterface {
    if (!isset($this->context["entity"])) {
      // This case is not supposed to happen,
      // unless context guessing is not working,
      // like when, for example, using Display Suite without
      // the correct module installed to guess the entity...etc.
      $this->logger->error(t("Missing entity from context for @entity_type_id", [
        "@entity_type_id" => $this->getMetadata()["parent_entity_type_id"],
      ]));
      return NULL;
    }
    try {
      return $this->context["entity"]->getContextValue();
    }
    catch (\Exception $e) {
      $this->logger->error(t("Missing entity from context for @entity_type_id: @error", [
        "@entity_type_id" => $this->getMetadata()["parent_entity_type_id"],
        "@error" => $e->getMessage(),
      ]));
      return NULL;
    }
  }

  /**
   * Get entities for this derivable context.
   *
   * @return array
   *   The references entities.
   */
  protected function getEntities() : array {
    $entity = $this->getEntityFromContext();
    if (!($entity instanceof EntityInterface)) {
      return [];
    }
    $metadata = $this->getMetadata();
    $entity_type_id = $metadata["entity_type_id"];
    $bundle = $metadata["bundle"];
    // Get referenced entities.
    $referenced_entities = $this->getReferencedEntities($entity, $metadata["field_name"], $bundle);
    if ((count($referenced_entities) === 0) && !$entity->id()) {
      // Case when the entity is a sample (we are probably in a form)
      // we generate a sample referenced entity.
      $referenced_entities[] = $this->sampleEntityGenerator->get($entity_type_id, empty($bundle) ? $this->findEntityBundleWithField($entity_type_id, NULL) : $bundle);
    }
    else {
      // Check ui_patterns:field:index.
      if (isset($this->context["ui_patterns:field:index"])) {
        $field_index = $this->context["ui_patterns:field:index"]->getContextValue();
        if (isset($referenced_entities[$field_index])) {
          $referenced_entities = [$referenced_entities[$field_index]];
        }
      }
    }
    return $referenced_entities;
  }

  /**
   * Get the referenced entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity.
   * @param string $ref_field_name
   *   The field name of the reference field.
   * @param string $bundle
   *   Optional bundle of the referenced entity.
   *
   * @return array
   *   The referenced entities.
   */
  protected function getReferencedEntities(?EntityInterface $entity, string $ref_field_name, string $bundle = "") : array {
    if (!($entity instanceof ContentEntityInterface)) {
      return [];
    }
    $referenced_entities = [];
    if ($entity->hasField($ref_field_name)) {
      $field_reference = $entity->get($ref_field_name);
      if (!$field_reference->isEmpty()) {
        for ($i = 0; $i < $field_reference->count(); $i++) {
          $field_item = $field_reference->get($i);
          $referenced_entity = NULL;
          $typed_data_item = $field_item->get('entity');
          if ($typed_data_item instanceof EntityReference) {
            $referenced_entity = $typed_data_item->getValue();
          }
          if (($referenced_entity instanceof EntityInterface) && (empty($bundle) || $referenced_entity->bundle() === $bundle)) {
            $referenced_entities[] = $referenced_entity;
          }
        }
      }
    }
    return $referenced_entities;
  }

  /**
   * Find an entity bundle which eventually has a field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field name to be found in searched bundle.
   *
   * @return string
   *   The bundle.
   */
  protected function findEntityBundleWithField(string $entity_type_id, ?string $field_name = NULL) : string {
    // @todo better implementation with service 'entity_type.bundle.info'
    $bundle = $entity_type_id;
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();
    if (NULL !== $bundle_entity_type) {
      $bundle_list = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
      if (count($bundle_list) > 0) {
        foreach ($bundle_list as $bundle_entity) {
          $bundle_to_test = (string) $bundle_entity->id();
          if ($field_name === NULL) {
            $bundle = $bundle_to_test;
            break;
          }
          $definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle_to_test);
          if (array_key_exists($field_name, $definitions)) {
            $bundle = $bundle_to_test;
            break;
          }
        }
      }
    }
    return $bundle;
  }

}
