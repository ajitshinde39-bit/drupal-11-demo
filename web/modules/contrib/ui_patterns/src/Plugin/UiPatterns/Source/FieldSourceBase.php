<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Plugin\Exception\ContextException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\ui_patterns\SourceInterface;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for all field source plugins.
 */
abstract class FieldSourceBase extends SourcePluginBase implements SourceInterface {

  use LoggerChannelTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    // We keep the same constructor as the parent class SourcePluginBase.
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityFieldManager = $container->get('entity_field.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $form['label_map'] = [
      "#type" => "label",
      "#title" => $this->propDefinition['title'] ?? '' . ": " . $this->label(),
    ];
    return $form;
  }

  /**
   * Returns the field name.
   *
   * @return string
   *   The field name.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  protected function getFieldName(): string {
    return $this->getContextValue('field_name');
  }

  /**
   * Returns the bundle.
   *
   * @return string
   *   The bundle.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  protected function getBundle(): string {
    return $this->getContextValue('bundle');
  }

  /**
   * Returns the entity type id.
   *
   * @return string|null
   *   The entity type id.
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  protected function getEntityTypeId(): ?string {
    if ($entity = $this->getEntity()) {
      return $entity->getEntityTypeId();
    }
    throw new ContextException("Entity not found");
  }

  /**
   * Returns the entity from context.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity
   *
   * @throws \Drupal\Component\Plugin\Exception\ContextException
   */
  protected function getEntity(): ?EntityInterface {
    return $this->getContextValue('entity');
  }

  /**
   * Returns the field definition.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface|null
   *   The field definition.
   */
  protected function getFieldDefinition(): ?FieldDefinitionInterface {
    $entity = $this->getEntity();
    if (!$entity) {
      return NULL;
    }
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    $field_name = $this->getFieldName();
    if (!array_key_exists($field_name, $field_definitions)) {
      return NULL;
    }
    return $field_definitions[$field_name];
  }

}
