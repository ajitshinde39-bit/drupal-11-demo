<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Unit;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_patterns\SchemaManager\Canonicalizer;
use Drupal\ui_patterns\SchemaManager\CompatibilityChecker;

/**
 * @coversDefaultClass \Drupal\ui_patterns\SchemaManager\CompatibilityChecker
 *
 * @group ui_patterns
 */
final class CompatibilityCheckerTest extends UnitTestCase {

  /**
   * Test the method ::isCompatible().
   *
   * @dataProvider provideCompatibilityCheckerData
   */
  public function testIsCompatible(array $referenceSchema, array $testData): void {
    $validator = new CompatibilityChecker(new Canonicalizer());
    foreach ($testData as $test) {
      $result = $validator->isCompatible($test['schema'], $referenceSchema);
      self::assertEquals((bool) $test['result'], $result);
    }
  }

  /**
   * Provide data for testIsCompatible.
   */
  public static function provideCompatibilityCheckerData(): \Generator {
    $file_contents = file_get_contents(__DIR__ . "/../../../fixtures/CompatibilityCheckerData.yml");
    $sources = $file_contents ? Yaml::decode($file_contents) : [];
    foreach ($sources as $source) {
      yield $source['label'] => [
        $source['schema'],
        $source['tests'],
      ];
    }
  }

}
