<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ui_patterns\Traits\ConfigImporterTrait;
use Drupal\Tests\ui_patterns\Traits\RunSourcePluginTestTrait;
use Drupal\Tests\ui_patterns\Traits\TestContentCreationTrait;

/**
 * Base class to test source plugins.
 *
 * @group ui_patterns
 */
class SourcePluginsTestBase extends KernelTestBase {

  use RunSourcePluginTestTrait;
  use TestContentCreationTrait;
  use ConfigImporterTrait;

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'text',
    'field',
    'node',
    'block',
    'ui_patterns',
    'ui_patterns_test',
    'datetime',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'filter', 'ui_patterns', 'ui_patterns_test']);
    $this->createTestContentContentType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceContexts(array $test_set = []): array {
    $context = [];
    if (isset($test_set['entity'])) {
      $entity = $this->createTestContentNode('page', is_array($test_set['entity']) ? $test_set['entity'] : []);
      $context['entity'] = EntityContext::fromEntity($entity);
    }
    return $context;
  }

  /**
   * Run source plugin tests against a test set.
   *
   * @param string|null $test_starts_with
   *   The prefix of the test set name to run.
   * @param string|null $tests_path
   *   The path to the test data fixture.
   */
  public function runSourcePluginTests(?string $test_starts_with = NULL, ?string $tests_path = NULL): void {
    $testData = self::loadTestDataFixture($tests_path ?? __DIR__ . "/../../fixtures/TestDataSet.yml");
    $testSets = $testData->getTestSets();
    $this->assertNotCount(0, $testSets, "Test sets should not be empty");
    foreach ($testSets as $test_set_name => $test_set) {
      if ($test_starts_with && !str_starts_with($test_set_name, $test_starts_with)) {
        continue;
      }
      $this->runSourcePluginTest($test_set);
    }
  }

}
