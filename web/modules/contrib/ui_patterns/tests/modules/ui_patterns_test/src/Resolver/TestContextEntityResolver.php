<?php

namespace Drupal\ui_patterns_test\Resolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\ui_patterns\Resolver\ContextEntityResolverInterface;

/**
 * Test implementation of the context entity resolver.
 */
class TestContextEntityResolver implements ContextEntityResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function guessEntity(array $contexts = []): ?EntityInterface {
    if (isset($contexts['ui_patterns:test'])) {
      $entity = EntityTest::create();
      $entity->save();
      return $entity;
    }
    return NULL;
  }

}
