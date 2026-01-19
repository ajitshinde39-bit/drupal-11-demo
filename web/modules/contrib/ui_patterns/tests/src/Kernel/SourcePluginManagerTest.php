<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourcePluginManager;

/**
 * Test SourcePluginManager.
 *
 * @group ui_patterns
 */
final class SourcePluginManagerTest extends KernelTestBase {

  /**
   * Defined contexts inside constructor.
   *
   * @var array
   */
  protected $definedContexts = [];

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'text', 'field', 'entity_test', 'ui_patterns', 'ui_patterns_test'];

  /**
   * The inline block usage service.
   */
  protected SourcePluginManager $sourcePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // @phpstan-ignore-next-line
    entity_test_create_bundle('ui_patterns');
    $this->installEntitySchema('entity_test');
    $this->installEntitySchema('user');
    $this->sourcePluginManager = \Drupal::service('plugin.manager.ui_patterns_source');
    $test_entity = EntityTest::create(['name' => 'test', 'type' => 'ui_patterns']);
    $this->definedContexts = ['entity' => EntityContext::fromEntity($test_entity)];
  }

  /**
   * Returns defined contexts by context id.
   */
  private function getContextsByIds($context_keys): array {
    $contexts = [];
    foreach ($context_keys as $context_key) {
      $contexts[$context_key] = $this->definedContexts[$context_key];
    }
    return $contexts;
  }

  /**
   * Provides generic test data.
   *
   * The test data returns:
   *   - the prop type,
   *   - the context keys
   *   - the expected plugin ids
   *   - the unexpected plugin ids.
   */
  public static function providerPropTypeDefinitions() {
    $data = [];
    $data[] = ['string', [], ['textfield', 'foo'], ['context_foo']];
    $data[] = ['string', ['entity'], ['textfield', 'foo', 'context_foo'], []];
    return $data;
  }

  /**
   * Test create source plugins.
   *
   * @dataProvider providerPropTypeDefinitions
   */
  public function testCreateSourcePluginsForPropType($prop_type_id, $context_keys = [], $should_contains = [], $not_contains = []): void {
    $contexts = $this->getContextsByIds($context_keys);
    $source_plugin_manager = $this->sourcePluginManager;
    $source_ids = array_keys($source_plugin_manager->getDefinitionsForPropType($prop_type_id, $contexts));
    $configuration = SourcePluginBase::buildConfiguration($prop_type_id, ['title' => 'test title'], [], $contexts, NULL);
    $source_ids = array_combine($source_ids, $source_ids);
    $sources = $source_plugin_manager->createInstances($source_ids, $configuration);
    $sources_key = array_keys($sources);
    $should_exists = array_intersect($sources_key, $should_contains);
    $should_not_exists = array_intersect($sources_key, $not_contains);
    $this->assertCount(count($should_contains), $should_exists, implode($should_exists));
    $this->assertCount(0, $should_not_exists, implode(' ', $should_not_exists));
  }

  /**
   * Test getDefinitionsForPropType.
   *
   * @dataProvider providerPropTypeDefinitions
   */
  public function testGetDefinitionsForPropType($prop_type_id, $context_keys = [], $should_contains = [], $not_contains = []): void {
    $contexts = $this->getContextsByIds($context_keys);
    $definitions = $this->sourcePluginManager->getDefinitionsForPropType($prop_type_id, $contexts);
    $definition_keys = array_keys($definitions);
    $should_exists = array_intersect($definition_keys, $should_contains);
    $should_not_exists = array_intersect($definition_keys, $not_contains);
    $this->assertCount(count($should_contains), $should_exists, implode($should_exists));
    $this->assertCount(0, $should_not_exists, implode(' ', $should_not_exists));
  }

  /**
   * Test getDefinitionsForPropType.
   *
   * @dataProvider providerPropTypeDefinitions
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public function testGetPropTypeDefault($prop_type_id, $context_keys = [], $should_contains = [], $not_contains = []): void {
    $contexts = $this->getContextsByIds($context_keys);
    $definition_id = $this->sourcePluginManager->getPropTypeDefault($prop_type_id, $contexts);
    $this->assertNotNull($definition_id);
    $this->assertNull($not_contains[$definition_id] ?? NULL);
  }

}
