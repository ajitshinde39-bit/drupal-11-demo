<?php

/**
 * @file
 * Post-update functions for the ui_patterns.
 */

declare(strict_types=1);

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Utility\UpdateException;
use Drupal\ui_patterns_legacy\Service\LayoutBuilderUpdaterInterface;

/**
 * Convert layout builder overrides into UI Patterns 2.
 *
 * @SuppressWarnings("PHPMD.NPathComplexity")
 * @SuppressWarnings("PHPMD.CyclomaticComplexity")
 * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
 */
function ui_patterns_post_update_update_layout_builder_override_ui_patterns_2(array &$sandbox): MarkupInterface {
  if (!\Drupal::moduleHandler()->moduleExists('layout_builder')) {
    return \t('Layout builder is not installed, nothing to update.');
  }

  if (!\Drupal::moduleHandler()->moduleExists('ui_patterns_legacy')) {
    throw new UpdateException('Not possible to update Layout Builder overrides. Enable ui_patterns_legacy module before.');
  }

  if (!isset($sandbox['total'])) {
    $sandbox['count'] = [];
    $sandbox['entity_fields'] = [];
    $sandbox['total'] = 0;

    // Get all entity types containing layout_section fields to prepare batch.
    $layout_section_field_mapping = \Drupal::service('entity_field.manager')
      ->getFieldMapByFieldType('layout_section');
    foreach ($layout_section_field_mapping as $entity_type_id => $entity_fields) {
      $sandbox['entity_fields'][$entity_type_id] = [];
      /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
      $entity_storage = \Drupal::service('entity_type.manager')->getStorage($entity_type_id);

      // There should be only one field but in case of custom development or
      // contrib module allowing other section fields on the same entity type.
      $query = $entity_storage->getQuery()
        ->accessCheck(FALSE);
      $or_group = $query->orConditionGroup();
      /** @var string $field_name */
      foreach (\array_keys($entity_fields) as $field_name) {
        $or_group->condition($field_name, LayoutBuilderUpdaterInterface::PATTERN_PREFIX, 'CONTAINS');
        $sandbox['entity_fields'][$entity_type_id][] = $field_name;
      }
      $query->condition($or_group);
      $query->count();
      $sandbox['count'][$entity_type_id] = $query->execute();
      $sandbox['total'] += $sandbox['count'][$entity_type_id];
    }

    $sandbox['progress'] = 0;
    $sandbox['current_entity_id'] = 0;
  }

  // Do not continue if no entities are found.
  if ($sandbox['total'] == 0) {
    $sandbox['#finished'] = 1;
    return \t('No entities to update.');
  }

  /** @var \Drupal\ui_patterns_legacy\Service\LayoutBuilderUpdaterInterface $updater */
  $updater = \Drupal::service('ui_patterns_legacy.layout_builder_updater');

  // Loop on the entity types even if we will process only one entity type per
  // batch run.
  foreach ($sandbox['entity_fields'] as $entity_type_id => $entity_fields) {
    // No more entities of this type to process.
    if ($sandbox['count'][$entity_type_id] == 0) {
      continue;
    }

    /** @var \Drupal\Core\Entity\EntityStorageInterface $entity_storage */
    $entity_storage = \Drupal::service('entity_type.manager')->getStorage($entity_type_id);
    /** @var string $entity_id_key */
    $entity_id_key = $entity_storage->getEntityType()->getKey('id');

    // If the entity type does not have an ID key, skip.
    if (!$entity_id_key) {
      continue;
    }

    // Get next batch of entities.
    $query = $entity_storage->getQuery()
      ->accessCheck(FALSE);
    $or_group = $query->orConditionGroup();
    /** @var string $field_name */
    foreach ($sandbox['entity_fields'][$entity_type_id] as $field_name) {
      $or_group->condition($field_name, LayoutBuilderUpdaterInterface::PATTERN_PREFIX, 'CONTAINS');
    }
    $query->condition($or_group);
    $query->condition($entity_id_key, $sandbox['current_entity_id'], '>');
    $query->sort($entity_id_key, 'ASC');
    // @phpstan-ignore-next-line
    $query->range(0, (int) 25);
    /** @var int[] $entity_ids_to_update */
    $entity_ids_to_update = $query->execute();
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $entities_to_update */
    $entities_to_update = $entity_storage->loadMultiple($entity_ids_to_update);

    foreach ($entities_to_update as $entity) {
      $save_entity = FALSE;
      foreach ($entity_fields as $entity_field) {
        if (!$entity->hasField($entity_field)) {
          continue;
        }

        $layout_builder_override = $entity->get($entity_field);
        if ($layout_builder_override->isEmpty()) {
          continue;
        }

        $override_field_value = $layout_builder_override->get(0);
        if ($override_field_value === NULL) {
          continue;
        }

        $sections_changed = FALSE;
        /** @var \Drupal\layout_builder\Section[] $sections */
        $sections = $override_field_value->getValue();
        foreach ($sections as $delta => $section) {
          $newSection = $updater->updateLayout($section);
          if (!$newSection) {
            continue;
          }

          $sections[$delta] = $newSection;
          $save_entity = TRUE;
          $sections_changed = TRUE;
        }
        if ($sections_changed) {
          $entity->set($entity_field, $sections);
        }
      }

      if ($save_entity) {
        $entity->save();
      }

      $sandbox['current_entity_id'] = $entity->id();
      --$sandbox['count'][$entity_type_id];
      ++$sandbox['progress'];
    }

    // If it is the last batch run for this entity type, reset the
    // current_entity_id for the next entity type.
    if ($sandbox['count'][$entity_type_id] == 0) {
      $sandbox['current_entity_id'] = 0;
    }

    break;
  }

  $sandbox['#finished'] = ($sandbox['progress'] / $sandbox['total']);
  if ($sandbox['#finished'] == 1) {
    return \t('Entities using Layout Builder override have been updated. After update is ok, you can uninstall the UI Patterns Legacy module and uninstall UI Patterns Blocks if not using it.');
  }
  else {
    return \t('Updating entities using Layout Builder override');
  }
}
