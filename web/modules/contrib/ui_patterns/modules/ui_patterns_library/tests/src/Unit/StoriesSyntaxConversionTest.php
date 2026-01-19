<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_library\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\ui_patterns_library\StoriesSyntaxConverter;

/**
 * @coversDefaultClass \Drupal\ui_patterns_library\StoriesSyntaxConverter
 *
 * @group ui_patterns
 * @group ui_patterns_library
 */
final class StoriesSyntaxConversionTest extends UnitTestCase {

  /**
   * Test the method ::convertSlots().
   *
   * @dataProvider provideConversionData
   */
  public function testConvertSlot(array $value, array $expected): void {
    $converter = new StoriesSyntaxConverter();
    $converted = $converter->convertSlots($value);
    self::assertEquals($expected, $converted);
  }

  /**
   * Provide data for testConvertSlot.
   */
  public static function provideConversionData(): \Generator {
    $data = [
      "Not convertible" => self::notConvertible(),
      "Bootstrap accordion" => self::bootstrapAccordion(),
      "Bootstrap carousel" => self::bootstrapCarousel(),
      "Daisy Grid Row 4" => self::daisyGridRow4(),
      "Props special words" => self::componentSpecialProps(),
      'Theme layout' => self::themeLayout(),
      'Theme item_list' => self::themeItemList(),
    ];
    foreach ($data as $label => $test) {
      yield $label => [
        $test['value'],
        $test['expected'],
      ];
    }
  }

  /**
   * Only values which must not be converted. Some of them are not slot values.
   */
  protected static function notConvertible() {
    $value = [
      'empty' => [],
      'list' => [
        "one",
        "two",
        "free",
      ],
      'no_render_properties' => [
        "foo" => "bar",
        "bar" => "foo",
      ],
      'two_render_properties' => [
        "type" => "component",
        "markup" => "Lorem ipsum",
      ],
      'twice_same_property' => [
        "type" => "component",
        "#type" => "component",
      ],
      'already_converted' => [
        "#type" => "html_tag",
        "#tag" => "p",
        "#value" => "Lorem ipsum",
      ],
    ];
    return [
      "value" => $value,
      "expected" => $value,
    ];
  }

  /**
   * Bootstrap's accordion.
   */
  protected static function bootstrapAccordion() {
    $slots = [
      'content' => [
        0 => [
          'type' => 'component',
          'component' => 'ui_suite_bootstrap:accordion_item',
          'slots' => [
            'title' => 'Accordion Item #1',
            'content' => [
              'type' => 'html_tag',
              'tag' => 'p',
              'value' => 'Mollis pretium lorem primis senectus.',
            ],
          ],
          'props' => [
            'opened' => TRUE,
          ],
        ],
      ],
      'content_mixed' => [
        0 => [
          '#type' => 'component',
          '#component' => 'ui_suite_bootstrap:accordion_item',
          'slots' => [
            'title' => 'Accordion Item #1',
            'content' => [
              'type' => 'html_tag',
              '#tag' => 'p',
              'value' => 'Mollis pretium lorem primis senectus.',
            ],
          ],
          '#props' => [
            'opened' => TRUE,
          ],
        ],
      ],
    ];
    $expected = [
      'content' => [
        0 => [
          '#type' => 'component',
          '#component' => 'ui_suite_bootstrap:accordion_item',
          '#slots' => [
            'title' => 'Accordion Item #1',
            'content' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => 'Mollis pretium lorem primis senectus.',
            ],
          ],
          '#props' => [
            'opened' => TRUE,
          ],
        ],
      ],
      'content_mixed' => [
        0 => [
          '#type' => 'component',
          '#component' => 'ui_suite_bootstrap:accordion_item',
          '#slots' => [
            'title' => 'Accordion Item #1',
            'content' => [
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => 'Mollis pretium lorem primis senectus.',
            ],
          ],
          '#props' => [
            'opened' => TRUE,
          ],
        ],
      ],
    ];
    return [
      "value" => $slots,
      "expected" => $expected,
    ];
  }

  /**
   * Bootstrap's carousel.
   */
  protected static function bootstrapCarousel() {
    $slots = [
      'slides' => [
        [
          'type' => 'component',
          'component' => 'ui_suite_bootstrap:carousel_item',
          'slots' => [
            'image' => [
              'theme' => 'image',
              'uri' => 'data:image/svg+xml;base64,PHN2ZyBzdHl=',
            ],
            'caption' => [
              [
                'type' => 'html_tag',
                'tag' => 'h5',
                '0' => [
                  'type' => 'html_tag',
                  'tag' => 'em',
                  'value' => 'First slide label',
                ],
              ],
              [
                '0' => [
                  'type' => 'html_tag',
                  'tag' => 'em',
                  'value' => 'Nulla vitae elit libero, a pharetra augue mollis interdum.',
                ],
                'type' => 'html_tag',
                'tag' => 'p',
              ],
            ],
          ],
        ],
      ],
      'slides_mixed' => [
        [
          '#type' => 'component',
          'component' => 'ui_suite_bootstrap:carousel_item',
          '#slots' => [
            'image' => [
              'theme' => 'image',
              '#uri' => 'data:image/svg+xml;base64,PHN2ZyBzdHl=',
            ],
            'caption' => [
              [
                'type' => 'html_tag',
                '#tag' => 'h5',
                '0' => [
                  '#type' => 'html_tag',
                  'tag' => 'em',
                  '#value' => 'First slide label',
                ],
              ],
              [
                '0' => [
                  '#type' => 'html_tag',
                  '#tag' => 'em',
                  'value' => 'Nulla vitae elit libero, a pharetra augue mollis interdum.',
                ],
                'type' => 'html_tag',
                '#tag' => 'p',
              ],
            ],
          ],
        ],
      ],
    ];
    $expected = [
      'slides' => [
        [
          '#type' => 'component',
          '#component' => 'ui_suite_bootstrap:carousel_item',
          '#slots' => [
            'image' => [
              '#theme' => 'image',
              '#uri' => 'data:image/svg+xml;base64,PHN2ZyBzdHl=',
            ],
            'caption' => [
              [
                '#type' => 'html_tag',
                '#tag' => 'h5',
                '0' => [
                  '#type' => 'html_tag',
                  '#tag' => 'em',
                  '#value' => 'First slide label',
                ],
              ],
              [
                '0' => [
                  '#type' => 'html_tag',
                  '#tag' => 'em',
                  '#value' => 'Nulla vitae elit libero, a pharetra augue mollis interdum.',
                ],
                '#type' => 'html_tag',
                '#tag' => 'p',
              ],
            ],
          ],
        ],
      ],
      'slides_mixed' => [
        [
          '#type' => 'component',
          '#component' => 'ui_suite_bootstrap:carousel_item',
          '#slots' => [
            'image' => [
              '#theme' => 'image',
              '#uri' => 'data:image/svg+xml;base64,PHN2ZyBzdHl=',
            ],
            'caption' => [
              [
                '#type' => 'html_tag',
                '#tag' => 'h5',
                '0' => [
                  '#type' => 'html_tag',
                  '#tag' => 'em',
                  '#value' => 'First slide label',
                ],
              ],
              [
                '0' => [
                  '#type' => 'html_tag',
                  '#tag' => 'em',
                  '#value' => 'Nulla vitae elit libero, a pharetra augue mollis interdum.',
                ],
                '#type' => 'html_tag',
                '#tag' => 'p',
              ],
            ],
          ],
        ],
      ],
    ];
    return [
      "value" => $slots,
      "expected" => $expected,
    ];
  }

  /**
   * Daisy's grid row 4.
   */
  protected static function daisyGridRow4() {
    $slots = [
      'col_first' => [
        'type' => 'component',
        'component' => 'ui_suite_daisyui:card',
        'slots' => [
          'image' => [
            'theme' => 'image',
            'uri' => 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp',
            'alt' => 'Shoes',
          ],
          'title' => 'Shoes!',
          'text' => [
            'type' => 'html_tag',
            'tag' => 'p',
            'value' => 'If a dog chews shoes whose shoes does he choose?',
          ],
          'actions' => [
            'type' => 'component',
            'component' => 'ui_suite_daisyui:button',
            'slots' => [
              'label' => 'Buy Now',
            ],
            'props' => [
              'variant' => 'primary',
              'size' => 'sm',
            ],
          ],
        ],
        'props' => [
          'attributes' => [
            'class' => [
              0 => 'bg-base-100',
              1 => 'shadow-xl',
            ],
          ],
        ],
      ],
      'col_second' => [
        'type' => 'component',
        '#component' => 'ui_suite_daisyui:card',
        'slots' => [
          'image' => [
            'theme' => 'image',
            '#uri' => 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp',
            'alt' => 'Shoes',
          ],
          'title' => 'Shoes!',
          'text' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'If a dog chews shoes whose shoes does he choose?',
          ],
          'actions' => [
            '#type' => 'component',
            'component' => 'ui_suite_daisyui:button',
            '#slots' => [
              'label' => 'Buy Now',
            ],
            'props' => [
              'variant' => 'primary',
              'size' => 'sm',
            ],
          ],
        ],
        'props' => [
          'attributes' => [
            'class' => [
              0 => 'bg-base-100',
              1 => 'shadow-xl',
            ],
          ],
        ],
      ],
      'col_third' => [],
      'col_fourth' => [],
    ];
    $expected = [
      'col_first' => [
        '#type' => 'component',
        '#component' => 'ui_suite_daisyui:card',
        '#slots' => [
          'image' => [
            '#theme' => 'image',
            '#uri' => 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp',
            '#alt' => 'Shoes',
          ],
          'title' => 'Shoes!',
          'text' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'If a dog chews shoes whose shoes does he choose?',
          ],
          'actions' => [
            '#type' => 'component',
            '#component' => 'ui_suite_daisyui:button',
            '#slots' => [
              'label' => 'Buy Now',
            ],
            '#props' => [
              'variant' => 'primary',
              'size' => 'sm',
            ],
          ],
        ],
        '#props' => [
          'attributes' => [
            'class' => [
              0 => 'bg-base-100',
              1 => 'shadow-xl',
            ],
          ],
        ],
      ],
      'col_second' => [
        '#type' => 'component',
        '#component' => 'ui_suite_daisyui:card',
        '#slots' => [
          'image' => [
            '#theme' => 'image',
            '#uri' => 'https://img.daisyui.com/images/stock/photo-1606107557195-0e29a4b5b4aa.webp',
            '#alt' => 'Shoes',
          ],
          'title' => 'Shoes!',
          'text' => [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'If a dog chews shoes whose shoes does he choose?',
          ],
          'actions' => [
            '#type' => 'component',
            '#component' => 'ui_suite_daisyui:button',
            '#slots' => [
              'label' => 'Buy Now',
            ],
            '#props' => [
              'variant' => 'primary',
              'size' => 'sm',
            ],
          ],
        ],
        '#props' => [
          'attributes' => [
            'class' => [
              0 => 'bg-base-100',
              1 => 'shadow-xl',
            ],
          ],
        ],
      ],
      'col_third' => [],
      'col_fourth' => [],
    ];
    return [
      "value" => $slots,
      "expected" => $expected,
    ];
  }

  /**
   * Fake component with props with reserved words.
   */
  protected static function componentSpecialProps(): array {
    $slots = [
      'component' => [
        'type' => 'component',
        'component' => 'foo:bar',
        'props' => [
          'attributes' => [
            'class' => ['bar'],
            'type' => 'bar',
          ],
          'tag' => 'bar',
          'type' => 'bar',
          'value' => 'bar',
          'attached' => 'bar',
        ],
      ],
      'component_mixed' => [
        '#type' => 'component',
        'component' => 'foo:bar',
        '#props' => [
          'attributes' => [
            'class' => ['bar'],
            'type' => 'bar',
          ],
          'tag' => 'bar',
          'type' => 'bar',
          'value' => 'bar',
          'attached' => 'bar',
        ],
      ],
    ];
    $expected = [
      'component' => [
        '#type' => 'component',
        '#component' => 'foo:bar',
        '#props' => [
          'attributes' => [
            'class' => [
              0 => 'bar',
            ],
            'type' => 'bar',
          ],
          'tag' => 'bar',
          'type' => 'bar',
          'value' => 'bar',
          'attached' => 'bar',
        ],
      ],
      'component_mixed' => [
        '#type' => 'component',
        '#component' => 'foo:bar',
        '#props' => [
          'attributes' => [
            'class' => [
              0 => 'bar',
            ],
            'type' => 'bar',
          ],
          'tag' => 'bar',
          'type' => 'bar',
          'value' => 'bar',
          'attached' => 'bar',
        ],
      ],
    ];
    return [
      'value' => $slots,
      'expected' => $expected,
    ];
  }

  /**
   * Theme item_list.
   */
  protected static function themeItemList(): array {
    $value = [
      [
        'theme' => 'item_list',
        'list_type' => 'ul',
        'items' => [
          [
            'type' => 'html_tag',
            'tag' => 'p',
            'value' => 'Item One',
          ],
          [
            'type' => 'html_tag',
            'tag' => 'p',
            'value' => 'Item Two',
          ],
        ],
      ],
      [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item One',
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item Two',
          ],
        ],
      ],
    ];

    $expected = [
      [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item One',
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item Two',
          ],
        ],
      ],
      [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item One',
          ],
          [
            '#type' => 'html_tag',
            '#tag' => 'p',
            '#value' => 'Item Two',
          ],
        ],
      ],
    ];

    return [
      'value' => $value,
      'expected' => $expected,
    ];
  }

  /**
   * Theme layout.
   */
  protected static function themeLayout(): array {
    $value = [
      [
        'theme' => 'layout',
        'my_region' => [
          'markup' => 'my markup',
        ],
        'attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        'theme' => 'layout__my_theme',
        'my_region' => [
          'markup' => 'my markup',
        ],
        'attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        '#theme' => 'layout',
        'my_region' => [
          'markup' => 'my markup',
        ],
        'attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        'theme' => 'layout__my_theme',
        'my_region' => [
          '#markup' => 'my markup',
        ],
        'attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
    ];
    $expected = [
      [
        '#theme' => 'layout',
        'my_region' => [
          '#markup' => 'my markup',
        ],
        '#attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        '#theme' => 'layout__my_theme',
        'my_region' => [
          '#markup' => 'my markup',
        ],
        '#attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        '#theme' => 'layout',
        'my_region' => [
          '#markup' => 'my markup',
        ],
        '#attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
      [
        '#theme' => 'layout__my_theme',
        'my_region' => [
          '#markup' => 'my markup',
        ],
        '#attributes' => [
          'class' => [
            'my-class',
          ],
        ],
      ],
    ];
    return [
      'value' => $value,
      'expected' => $expected,
    ];
  }

}
