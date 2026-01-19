<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\Component\Serialization\Yaml;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test SchemaManager parts.
 *
 * @group ui_patterns
 */
final class SchemaManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns'];

  /**
   * Test the StreamWrapper service.
   */
  public function testStreamWrapper(): void {
    $correctUri = 'ui-patterns://number';
    $wrongUri = 'ui-patterns://wrongProptype';

    $correctPropType = file_get_contents($correctUri);
    $wrongPropType = file_get_contents($wrongUri);

    self::assertEquals('{"type":["number","integer"]}', $correctPropType);
    self::assertEquals('[]', $wrongPropType);
  }

  /**
   * Test the ReferencesResolver service.
   *
   * @dataProvider provideResolveData
   */
  public function testResolve(array $schema, array $expected): void {
    $resolver = \Drupal::service("ui_patterns.schema_reference_solver");
    $result = $resolver->resolve($schema);

    // Skipping everything under patternProperties keys
    // to avoid having to deal with std classes in the yaml file.
    $this->cleanupSchema($result);
    self::assertEqualsCanonicalizing($expected, $result);
  }

  /**
   * Provide data for testResolve.
   */
  public static function provideResolveData(): \Generator {
    $file_contents = file_get_contents(__DIR__ . "/../../fixtures/ReferencesResolverData.yml");
    $sources = $file_contents ? Yaml::decode($file_contents) : [];
    foreach ($sources as $source) {
      yield [$source['schema'], $source['expected']];
    }
  }

  /**
   * Remove all "patternProperties" keys from the given schema.
   */
  private function cleanupSchema(array &$schema): void {
    foreach ($schema as $key => &$value) {
      if ($key == 'patternProperties') {
        unset($schema[$key]);
      }
      elseif (is_array($value)) {
        $this->cleanupSchema($value);
      }
    }
  }

}
