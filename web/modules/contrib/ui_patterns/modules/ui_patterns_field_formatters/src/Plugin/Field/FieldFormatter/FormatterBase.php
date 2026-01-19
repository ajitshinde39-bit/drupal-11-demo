<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase as FieldFormatterBase;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for UI Patterns field formatters.
 */
abstract class FormatterBase extends FieldFormatterBase {

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
   * The chain context entity resolver.
   *
   * @var \Drupal\ui_patterns\Resolver\ContextEntityResolverInterface
   */
  protected $chainContextEntityResolver;

  /**
   * The provided plugin contexts.
   *
   * @var array|null
   */
  protected $context = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->sampleEntityGenerator = $container->get('ui_patterns.sample_entity_generator');
    $instance->chainContextEntityResolver = $container->get('ui_patterns.chain_context_entity_resolver');
    return $instance;
  }

  /**
   * Set the context.
   *
   * @param array $context
   *   Context.
   *
   * @return void
   *   Nothing.
   */
  public function setContext(array $context): void {
    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkEntityHasField(EntityInterface $entity, string $entity_type_id, string $field_name) : bool {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    return ($entity->getEntityTypeId() === $entity_type_id &&
      array_key_exists($field_name, $field_definitions));
  }

  /**
   * Find an entity bundle which has a field.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field_name
   *   The field name to be found in searched bundle.
   *
   * @return string
   *   The bundle.
   */
  protected function findEntityBundleWithField(string $entity_type_id, string $field_name) : string {
    // @todo better implementation with service 'entity_type.bundle.info'
    $bundle = $entity_type_id;
    $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type_id)->getBundleEntityType();
    if (NULL !== $bundle_entity_type) {
      $bundle_list = $this->entityTypeManager->getStorage($bundle_entity_type)->loadMultiple();
      if (count($bundle_list) > 0) {
        foreach ($bundle_list as $bundle_entity) {
          $bundle_to_test = (string) $bundle_entity->id();
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

  /**
   * Set the context of field and entity (override the method trait).
   *
   * @param ?FieldItemListInterface $items
   *   Field items when available.
   *
   * @return array
   *   Source contexts.
   */
  protected function getComponentSourceContexts(?FieldItemListInterface $items = NULL): array {
    $contexts = array_merge($this->context ?? [], $this->getThirdPartySetting('ui_patterns', 'context') ?? []);
    $field_definition = $this->fieldDefinition;
    $field_name = $field_definition->getName() ?? "";
    $contexts['field_name'] = new Context(ContextDefinition::create('string'), $field_name);
    $bundle = $field_definition->getTargetBundle();
    $contexts['bundle'] = new Context(ContextDefinition::create('string'), $bundle ?? "");
    // Get the entity.
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    // When field items are available, we can get the entity directly.
    $entity = ($items) ? $items->getEntity() : NULL;
    if (!$entity) {
      $entity = $this->chainContextEntityResolver->guessEntity($contexts);
    }
    if (!$entity_type_id) {
      return $contexts;
    }
    if (!$entity || !$this->checkEntityHasField($entity, $entity_type_id, $field_name)) {
      // Generate a default bundle when it is missing,
      // this covers contexts like the display of a field in a view.
      // the bundle selected should have the field in definition...
      $entity = !empty($bundle) ? $this->sampleEntityGenerator->get($entity_type_id, $bundle) :
        $this->sampleEntityGenerator->get($entity_type_id, $this->findEntityBundleWithField($entity_type_id, $field_name));

    }
    $contexts['entity'] = EntityContext::fromEntity($entity);
    return $contexts;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return ($field_definition->getTargetEntityTypeId() !== NULL) && parent::isApplicable($field_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    SourcePluginBase::mergeConfigDependencies($dependencies, ["module" => ["ui_patterns_field_formatters"]]);
    return $dependencies;
  }

}
