<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_layout\Entity\FieldLayoutEntityFormDisplay;
use Drupal\field_layout\Entity\FieldLayoutEntityViewDisplay;
use Drupal\field_layout\Form\FieldLayoutEntityFormDisplayEditForm;
use Drupal\field_layout\Form\FieldLayoutEntityViewDisplayEditForm;
use Drupal\ui_patterns\Entity\SampleEntityGeneratorInterface;

/**
 * Provides context entity for field layouts.
 */
class FieldLayoutContextEntityResolver implements ContextEntityResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SampleEntityGeneratorInterface $sampleEntityGenerator,
  ) {

  }

  /**
   * {@inheritdoc}
   */
  public function guessEntity(array $contexts = []): ?EntityInterface {
    $form_state = isset($contexts['ui_patterns:form_state']) ? $contexts['ui_patterns:form_state']->getContextValue() : NULL;
    if ($entity = $this->guessFieldLayoutEntity($form_state)) {
      return $entity;
    }
    return NULL;
  }

  /**
   * Guess the entity from the field layout form.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   Optional Form state.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if found or null.
   */
  protected function guessFieldLayoutEntity(?FormStateInterface $form_state = NULL) : ?EntityInterface {
    if ($form_state !== NULL) {
      $form_object = $form_state->getFormObject();
      if (($form_object instanceof FieldLayoutEntityViewDisplayEditForm) ||
        ($form_object instanceof FieldLayoutEntityFormDisplayEditForm)) {
        $entity = $form_object->getEntity();
        if (($entity instanceof FieldLayoutEntityViewDisplay)|| ($entity instanceof FieldLayoutEntityFormDisplay)) {
          return $this->sampleEntityGenerator->get($entity->getTargetEntityTypeId(), $entity->getTargetBundle());
        }
      }
    }
    return NULL;
  }

}
