<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Entity;

use Drupal\Core\Entity\EntityInterface;

/**
 * Generates a sample entity.
 *
 * In cases where no entity exists during the mapping
 * process it is required to generate a sample entity to
 * provide the entity context as well as preview values.
 *
 * The idea and the interface copied from the Layout Builder.
 * To have no dependency to layout builder the classes were copied.
 */
interface SampleEntityGeneratorInterface {

  /**
   * Gets a sample entity for a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An entity.
   */
  public function get(string $entity_type_id, string $bundle_id) : EntityInterface;

  /**
   * Deletes a sample entity for a given entity type and bundle.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $bundle_id
   *   The bundle ID.
   *
   * @return $this
   */
  public function delete(string $entity_type_id, string $bundle_id) :SampleEntityGeneratorInterface;

}
