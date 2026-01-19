<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Resolver;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\ui_patterns\Resolver\ChainContextEntityResolver;
use Drupal\ui_patterns_test\Resolver\TestContextEntityResolver;

/**
 * Test the ChainContextEntityResolver service.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Resolver\ChainContextEntityResolver
 *
 * @group ui_patterns
 */
final class ChainContextEntityResolverTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns', 'ui_patterns_test'];

  /**
   * The chain context entity resolver.
   */
  protected ChainContextEntityResolver $chainContextEntityResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->chainContextEntityResolver = \Drupal::service('ui_patterns.chain_context_entity_resolver');
  }

  /**
   * Test the method ::getResolvers().
   */
  public function testGetResolvers() : void {
    $resolvers = $this->chainContextEntityResolver->getResolvers();
    $test_resolver = NULL;
    foreach ($resolvers as $resolver) {
      if ($resolver instanceof TestContextEntityResolver) {
        $test_resolver = $resolver;
      }
    }
    $this->assertNotNull($test_resolver);
  }

  /**
   * Test the method ::guessEntity().
   */
  public function testGuessEntity() : void {
    $contexts['ui_patterns:test'] = new Context(ContextDefinition::create('any'), TRUE);
    $entity = $this->chainContextEntityResolver->guessEntity($contexts);
    $this->assertNotNull($entity);
  }

}
