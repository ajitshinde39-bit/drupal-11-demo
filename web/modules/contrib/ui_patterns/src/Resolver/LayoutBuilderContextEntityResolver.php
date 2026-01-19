<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Resolver;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\layout_builder\DefaultsSectionStorageInterface;
use Drupal\layout_builder\Form\ConfigureSectionForm;
use Drupal\layout_builder\OverridesSectionStorageInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Drupal\ui_patterns\Entity\SampleEntityGeneratorInterface;

/**
 * Provides context entity for layout builder.
 */
class LayoutBuilderContextEntityResolver implements ContextEntityResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    protected RouteMatchInterface $routeMatch,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected SampleEntityGeneratorInterface $sampleEntityGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function guessEntity(array $contexts = []): ?EntityInterface {
    $form_state = isset($contexts['ui_patterns:form_state']) ? $contexts['ui_patterns:form_state']->getContextValue() : NULL;
    $section_storage = $this->getLayoutBuilderSectionStorage($form_state);
    if ($section_storage && ($entity = $this->guessLayoutBuilderEntity($section_storage))) {
      return $entity;
    }
    return NULL;
  }

  /**
   * Gets the layout builder section storage.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   Optional Form state.
   *
   * @return \Drupal\layout_builder\SectionStorageInterface|null
   *   The section storage or null.
   */
  protected function getLayoutBuilderSectionStorage(?FormStateInterface $form_state = NULL) : ?SectionStorageInterface {
    if ($form_state !== NULL) {
      $form_object = $form_state->getFormObject();
      if ($form_object instanceof ConfigureSectionForm) {
        return $form_object->getSectionStorage();
      }
    }
    $section_storage = $this->routeMatch->getParameter("section_storage");
    if ($section_storage instanceof SectionStorageInterface) {
      return $section_storage;
    }
    return NULL;
  }

  /**
   * Gets entity information from sections storage.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section to look up info on.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if found or null.
   */
  protected function guessLayoutBuilderEntity(SectionStorageInterface $section_storage) : ?EntityInterface {
    if ($entity = $this->guessLayoutBuilderEntityFromContexts($section_storage->getContexts())) {
      return $entity;
    }
    $storage_id = $section_storage->getStorageId();
    if ($section_storage instanceof DefaultsSectionStorageInterface) {
      $display = $this->entityTypeManager->getStorage('entity_view_display')->load($storage_id);
      if ($display instanceof EntityViewDisplayInterface) {
        return $this->sampleEntityGenerator->get($display->getTargetEntityTypeId(), $display->getTargetBundle());
      }
    }
    elseif ($section_storage instanceof OverridesSectionStorageInterface) {
      [$entity_type_id, $id] = explode('.', $storage_id);
      if ($entity = $this->entityTypeManager->getStorage($entity_type_id)->load($id)) {
        return $entity;
      }
    }
    return NULL;
  }

  /**
   * Gets entity information from sections storage.
   *
   * @param array<mixed> $section_storage_contexts
   *   Contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   The entity if found or null.
   */
  protected function guessLayoutBuilderEntityFromContexts(array $section_storage_contexts) : ?EntityInterface {
    if (array_key_exists("entity", $section_storage_contexts) && ($entity = $section_storage_contexts["entity"]->getContextValue())) {
      return $entity;
    }
    // Search for the entity in the context (no matter the name).
    foreach ($section_storage_contexts as $section_storage_context) {
      if ($section_storage_context instanceof EntityContext) {
        // Now check if it is an entity view display.
        $entity_in_context = $section_storage_context->getContextValue();
        if ($entity_in_context instanceof EntityViewDisplayInterface) {
          return $this->sampleEntityGenerator->get($entity_in_context->getTargetEntityTypeId(), $entity_in_context->getTargetBundle());
        }
      }
    }
    return NULL;
  }

}
