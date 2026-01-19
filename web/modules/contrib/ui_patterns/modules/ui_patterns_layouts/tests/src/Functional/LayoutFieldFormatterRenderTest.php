<?php

namespace Drupal\Tests\ui_patterns_layouts\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test pattern preview rendering.
 *
 * @group ui_patterns_layouts
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
    'ui_patterns_layouts',
    'field_ui',
    'field_layout',
    'block',
  ];

  /**
   * Tests preview and output of props.
   */
  public function testRender(): void {
    $test_data = self::loadTestDataFixture();
    $tests = $test_data->getTestSets();
    foreach ($tests as $name => $test_set) {
      if (!isset($test_set["component"]["slots"]) || !is_array($test_set["component"]["slots"]) || count($test_set["component"]["slots"]) < 1) {
        continue;
      }
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config_to_set = $this->buildUiPatternsConfig($test_set);
      $slots_of_component = $test_set["component"]["slots"];
      $first_slot = array_keys($slots_of_component)[0];
      // -----
      // Field Layout with a component section
      $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout.section_component.yml');
      $ui_patterns_config_layout_2 = &$config_import['third_party_settings']['field_layout']['settings']['ui_patterns'];
      $ui_patterns_config_layout_2 = $ui_patterns_config_to_set;
      $field_name = (isset($test_set["contexts"]) && isset($test_set["contexts"]["field_name"])) ? $test_set["contexts"]["field_name"] : "body";
      if (!empty($field_name) && $field_name !== "body") {
        $config_import['content'][$field_name] = $config_import['content']["body"];
        unset($config_import['content']["body"]);
      }
      $config_import['content'][$field_name]["region"] = $first_slot;
      $config_import['third_party_settings']['field_layout']['id'] = sprintf("ui_patterns:%s", str_replace("-", "_", $ui_patterns_config_to_set["component_id"]));
      $this->importConfigFixture('core.entity_view_display.node.page.full', $config_import);
      $this->drupalGet('node/' . $node->id());
      $status_code = $this->getSession()->getStatusCode();
      $this->assertTrue($status_code === 200, sprintf('Status code is $status_code for test %s. %s', $test_set['name'], $this->getSession()->getPage()->getContent()));
      $this->validateRenderedComponent([
        "component" => $test_set["component"],
        "name" => $name,
        "output" => [
          "slots" => [
            "wrapper" => [
              [
                "normalized_value" => "entity exists: 1",
              ],
            ],
          ],
        ],
      ]);
      // We do not run assertSession tests, because when used as sections
      // the component is not rendered as expected by the test.
    }
  }

}
