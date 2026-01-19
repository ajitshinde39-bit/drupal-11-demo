<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Unit;

use Drupal\Core\Render\Markup;
use Drupal\Tests\UnitTestCase;
use Drupal\ui_patterns\Element\ComponentElementAlter;

/**
 * @coversDefaultClass \Drupal\ui_patterns\Element\ComponentElementAlter
 *
 * @group ui_patterns
 */
final class ComponentElementAlterTest extends UnitTestCase {

  /**
   * Test the method ::canonicalize().
   *
   * @dataProvider provideSlots
   */
  public function testIsSlotEmpty(array $slot, bool $isEmpty): void {
    $this->assertEquals($isEmpty, ComponentElementAlter::isSlotEmpty($slot));
  }

  /**
   * Provide data for testCanonicalize.
   */
  public static function provideSlots(): array {
    return [
      [
        ['#markup' => ''], TRUE,
      ],
      [
        ['#markup' => Markup::create('')], TRUE,
      ],
      [
        ['#markup' => Markup::create('test')], FALSE,
      ],
      [
        ['#cache' => ['tags' => ['tag1']]], TRUE,
      ],
      [
        ['#cache' => ['tags' => ['tag2']], '#markup' => NULL], TRUE,
      ],
      [
        ['#cache' => ['tags' => ['tag2']], 'children' => [['#markup' => '']]], TRUE,
      ],
      [
        ['#plain_text' => ''], TRUE,
      ],
      [
        ['#plain_text' => '', '#preprocess' => 'dummy'], FALSE,
      ],
      [
        [
          'children' => [
          ['#markup' => ''],
          ['#markup' => ''],
          ],
        ], TRUE,
      ],
      [
        [
          'children' => [
          ['#markup' => ''],
          ['#markup' => 'TEST'],
          ],
        ], FALSE,
      ],
      [
        ['children' => ['#markup' => 'some']], FALSE,
      ],
      [
        ['#markup' => 'some data'], FALSE,
      ],
      [
        ['#theme' => 'my_theme'], FALSE,
      ],
      [
        ['#weight' => 0], TRUE,
      ],
      [
        ['#theme' => 'my_theme', '#access' => FALSE], TRUE,
      ],

    ];
  }

}
