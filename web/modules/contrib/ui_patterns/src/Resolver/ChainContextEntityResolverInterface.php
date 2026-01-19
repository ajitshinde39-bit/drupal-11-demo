<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Resolver;

/**
 * Runs the added resolvers one by one until one of them returns an Entity.
 *
 * Each resolver in the chain can be another chain, which is why this interface
 * extends the base context resolver one.
 */
interface ChainContextEntityResolverInterface extends ContextEntityResolverInterface {

  /**
   * Adds a resolver.
   *
   * @param \Drupal\ui_patterns\Resolver\ContextEntityResolverInterface $resolver
   *   The resolver.
   */
  public function addResolver(ContextEntityResolverInterface $resolver):void;

  /**
   * Gets all added resolvers.
   *
   * @return \Drupal\ui_patterns\Resolver\ContextEntityResolverInterface[]
   *   The resolvers.
   */
  public function getResolvers(): array;

}
