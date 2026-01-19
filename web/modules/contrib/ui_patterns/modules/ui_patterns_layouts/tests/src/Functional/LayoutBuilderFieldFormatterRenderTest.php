<?php

namespace Drupal\Tests\ui_patterns_layouts\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test pattern preview rendering.
 *
 * @group ui_patterns_layouts
 */
class LayoutBuilderFieldFormatterRenderTest extends UiPatternsFunctionalTestBase {

  use TestDataTrait;

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
    'layout_builder',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    if ($this->user) {
      $this->drupalCreateRole(['configure any layout'], 'custom_role');
      $this->user->addRole('custom_role');
      $this->user->save();
    }
  }

  /**
   * Tests preview and output of props.
   */
  public function testRender(): void {
    $assert_session = $this->assertSession();
    $test_data = self::loadTestDataFixture();
    $tests = $test_data->getTestSets();
    foreach ($tests as $test_set) {
      if (!isset($test_set["component"]["slots"]) || !is_array($test_set["component"]["slots"]) || count($test_set["component"]["slots"]) < 1) {
        continue;
      }
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config_to_set = $this->buildUiPatternsConfig($test_set);
      // -----
      // Layout builder classic
      $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout_builder.section_component.yml');
      $ui_patterns_config_lb_1 = &$config_import['third_party_settings']['layout_builder']['sections'][0]['layout_settings']['ui_patterns'];
      $ui_patterns_config_lb_1 = $ui_patterns_config_to_set;
      $field_name = (isset($test_set["contexts"]) && isset($test_set["contexts"]["field_name"])) ? $test_set["contexts"]["field_name"] : "body";
      if (!empty($field_name) && $field_name !== "body") {

        $config_id = $config_import['third_party_settings']['layout_builder']['sections'][0]['components']['2b7726dd-cf0a-4b6c-b2d6-3c7e9b3bab33']['configuration']['id'];
        $config_import['third_party_settings']['layout_builder']['sections'][0]['components']['2b7726dd-cf0a-4b6c-b2d6-3c7e9b3bab33']['configuration']['id'] = str_replace(":body", ":" . $field_name, $config_id);
      }
      $slots_of_component = $test_set["component"]["slots"];
      $first_slot = array_keys($slots_of_component)[0];
      $config_import['third_party_settings']['layout_builder']['sections'][0]['components']['2b7726dd-cf0a-4b6c-b2d6-3c7e9b3bab33']["region"] = $first_slot;
      $config_import['third_party_settings']['layout_builder']['sections'][0]['layout_id'] = sprintf("ui_patterns:%s", str_replace("-", "_", $ui_patterns_config_to_set["component_id"]));
      $this->importConfigFixture('core.entity_view_display.node.page.full', $config_import);
      $this->drupalGet('node/' . $node->id());
      $status_code = $this->getSession()->getStatusCode();
      $this->assertTrue($status_code === 200, sprintf('Status code is $status_code for test %s. %s', $test_set['name'], $this->getSession()->getPage()->getContent()));
      $assert_session->statusCodeEquals(200);
      $this->validateRenderedComponent([
        "component" => $test_set["component"],
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
