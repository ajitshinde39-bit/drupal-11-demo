<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Resolver;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for context entity resolvers.
 */
interface ContextEntityResolverInterface {

  /**
   * Get an entity from contexts.
   *
   * @param array<mixed> $contexts
   *   Known contexts.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Entity or null.
   */
  public function guessEntity(array $contexts = []): ?EntityInterface;

}
