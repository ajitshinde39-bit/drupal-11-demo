<?php

namespace Drupal\Tests\ui_patterns_layouts\FunctionalJavascriptTests;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\ui_patterns\Traits\ConfigImporterTrait;
use Drupal\Tests\ui_patterns\Traits\TestContentCreationTrait;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Performance measuring of field ui.
 *
 * @group ui_patterns_layouts
 */
class LayoutFieldFormatterRenderPerformanceTest extends PerformanceTestBase {

  /**
   * The default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * The tested node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

  use TestContentCreationTrait;
  use TestDataTrait;
  use ConfigImporterTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_layouts',
    'ui_patterns_field_formatters',
    'field_ui',
    'field_layout',
    'layout_builder',
    'block',
  ];

  /**
   * Tests preview and output of props.
   */
  public function testRenderFieldFormatter(): void {

    $test_data_field_formatters = self::loadTestDataFixture(__DIR__ . "/../../fixtures/tests.formatter_per_item.yml");
    $test_set = $test_data_field_formatters->getTestSets('nesting_1');

    $this->node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
    $ui_patterns_config_to_set = $this->buildUiPatternsConfig($test_set);
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout.classic.yml');
    $ui_patterns_config_layout_1 = &$config_import['content']['body']['settings']['ui_patterns'];
    $ui_patterns_config_layout_1 = $ui_patterns_config_to_set;
    $field_name = (isset($test_set["contexts"]) && isset($test_set["contexts"]["field_name"])) ? $test_set["contexts"]["field_name"] : NULL;
    if (!empty($field_name) && $field_name !== "body") {
      $config_import['content'][$field_name] = $config_import['content']["body"];
      unset($config_import['content']["body"]);
    }
    $this->importConfigFixture('core.entity_view_display.node.page.full', $config_import);
    $this->collectPerformanceData(function () {
      $this->drupalGet('node/' . $this->node->id());
    }, 'UIPatternsRenderFieldFormatter');
  }

}
