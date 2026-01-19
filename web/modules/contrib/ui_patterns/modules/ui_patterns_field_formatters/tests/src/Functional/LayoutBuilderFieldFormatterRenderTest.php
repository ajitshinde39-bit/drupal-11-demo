<?php

namespace Drupal\Tests\ui_patterns_field_formatters\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test component rendering with Layout Builder.
 *
 * @group ui_patterns_field_formatters
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
   * Test the form and the existence of the.
   */
  public function testContextInForm(): void {
    $this->createTestContentContentType();
    $assert_session = $this->assertSession();
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout_builder.classic.yml');
    $ui_patterns_config = &$config_import['third_party_settings']['layout_builder']['sections'][0]['components']['b1fbf76f-1430-4a9c-8397-e060f3893142']['configuration']['formatter']['settings']['ui_patterns'];
    $test_data = $this->loadTestDataFixture();
    $test_set = $test_data->getTestSet('context_exists_default');
    $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
    $this->importConfigFixture(
      'core.entity_view_display.node.page.full',
      $config_import
    );
    $this->drupalGet('layout_builder/update/block/defaults/node.page.full/0/wrapper/b1fbf76f-1430-4a9c-8397-e060f3893142');
    $assert_session->elementTextEquals('css', '.context-exists', $test_set['output']['props']['string']['value']);
  }

  /**
   * Tests preview and output of props.
   */
  public function testRender(): void {
    $assert_session = $this->assertSession();
    $test_data = self::loadTestDataFixture();
    $test_data_field_formatters = self::loadTestDataFixture(__DIR__ . "/../../fixtures/tests.formatter_per_item.yml");
    $tests = array_merge($test_data->getTestSets(), $test_data_field_formatters->getTestSets());
    foreach ($tests as $test_set) {
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config_to_set = $this->buildUiPatternsConfig($test_set);
      // -----
      // Layout builder classic
      $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout_builder.classic.yml');
      $ui_patterns_config_lb_1 = &$config_import['third_party_settings']['layout_builder']['sections'][0]['components']['b1fbf76f-1430-4a9c-8397-e060f3893142']['configuration']['formatter']['settings']['ui_patterns'];
      $ui_patterns_config_lb_1 = $ui_patterns_config_to_set;
      $field_name = (isset($test_set["contexts"]) && isset($test_set["contexts"]["field_name"])) ? $test_set["contexts"]["field_name"] : NULL;
      if (!empty($field_name) && $field_name !== "body") {
        $config_id = $config_import['third_party_settings']['layout_builder']['sections'][0]['components']['b1fbf76f-1430-4a9c-8397-e060f3893142']['configuration']['id'];
        $config_import['third_party_settings']['layout_builder']['sections'][0]['components']['b1fbf76f-1430-4a9c-8397-e060f3893142']['configuration']['id'] = str_replace(":body", ":" . $field_name, $config_id);
      }
      $this->importConfigFixture('core.entity_view_display.node.page.full', $config_import);
      $this->drupalGet('node/' . $node->id());
      $status_code = $this->getSession()->getStatusCode();
      $this->assertTrue($status_code === 200, sprintf('Status code is $status_code for test %s. %s', $test_set['name'], $this->getSession()->getPage()->getContent()));
      $assert_session->statusCodeEquals(200);
      $this->validateRenderedComponent($test_set);
      if (isset($test_set["assertSession"])) {
        $this->assertSessionObject($test_set["assertSession"]);
      }
    }
  }

}
