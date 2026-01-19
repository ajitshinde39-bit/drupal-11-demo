<?php

namespace Drupal\Tests\ui_patterns_field_formatters\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test pattern preview rendering.
 *
 * @group ui_patterns_field_formatters
 */
class LayoutFieldFormatterRenderTest extends UiPatternsFunctionalTestBase {

  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_field_formatters',
    'field_ui',
    'field_layout',
    'block',
  ];

  /**
   * Tests preview and output of props.
   */
  public function testRender(): void {
    $test_data = self::loadTestDataFixture();
    $test_data_field_formatters = self::loadTestDataFixture(__DIR__ . "/../../fixtures/tests.formatter_per_item.yml");
    $tests = array_merge($test_data->getTestSets(), $test_data_field_formatters->getTestSets());
    foreach ($tests as $test_set) {
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config_to_set = $this->buildUiPatternsConfig($test_set);
      // -----
      // Field Layout classic
      $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout.classic.yml');
      $ui_patterns_config_layout_1 = &$config_import['content']['body']['settings']['ui_patterns'];
      $ui_patterns_config_layout_1 = $ui_patterns_config_to_set;
      $field_name = (isset($test_set["contexts"]) && isset($test_set["contexts"]["field_name"])) ? $test_set["contexts"]["field_name"] : NULL;
      if (!empty($field_name) && $field_name !== "body") {
        $config_import['content'][$field_name] = $config_import['content']["body"];
        unset($config_import['content']["body"]);
      }
      $this->importConfigFixture('core.entity_view_display.node.page.full', $config_import);
      $this->drupalGet('node/' . $node->id());
      $status_code = $this->getSession()->getStatusCode();
      $this->assertTrue($status_code === 200, sprintf('Status code is $status_code for test %s. %s', $test_set['name'], $this->getSession()->getPage()->getContent()));
      $this->validateRenderedComponent($test_set);
      if (isset($test_set["assertSession"])) {
        $this->assertSessionObject($test_set["assertSession"]);
      }
    }
  }

}
