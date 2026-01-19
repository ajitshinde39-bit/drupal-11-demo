<?php

namespace Drupal\Tests\ui_patterns_ui\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test components rendering as layouts.
 *
 * @group ui_patterns_layouts
 */
class LayoutBuilderRenderTest extends UiPatternsFunctionalTestBase {


  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_layouts',
    'ui_patterns_ui',
    'field_ui',
    'layout_builder',
    'block',
  ];

  /**
   * Test the form and the existence of the.
   */
  public function testDisplayExistsForm(): void {
    $assert_session = $this->assertSession();

    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.yml');
    $ui_patterns_config = &$config_import['third_party_settings']['layout_builder']['sections'][0]['layout_settings']['ui_patterns'];
    $test_data = $this->loadTestDataFixture(__DIR__ . "/../../fixtures/TestDataSet.yml");
    $test_set = $test_data->getTestSet('display_test_1');
    $this->createTestContentContentType();
    $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
    $this->importConfigFixture(
      'core.entity_view_display.node.page.full',
      $config_import
    );
    $display_config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/ui_patterns_ui.component_display.ui_patterns_test.test-component.test.yml');
    $this->importConfigFixture(
      'ui_patterns_ui.component_display.ui_patterns_test.test-component.test',
      $display_config_import
    );
    $this->drupalGet('admin/structure/types/manage/page/display/full/layout');
    $assert_session->statusCodeEquals(200);
    $this->click('.layout-builder__link--configure');
    $assert_session->elementExists('css', '.uip-display-select');
  }

  /**
   * Tests preview and output of props.
   */
  public function testRender(): void {
    $assert_session = $this->assertSession();
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.yml');
    $display_config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/ui_patterns_ui.component_display.ui_patterns_test.test-component.test.yml');
    $this->importConfigFixture(
      'ui_patterns_ui.component_display.ui_patterns_test.test-component.test',
      $display_config_import
    );
    $ui_patterns_config = &$config_import['third_party_settings']['layout_builder']['sections'][0]['layout_settings']['ui_patterns'];
    $test_data = $this->loadTestDataFixture(__DIR__ . "/../../fixtures/TestDataSet.yml");
    $tests = [
      $test_data->getTestSet('display_test_1'),
    ];

    foreach ($tests as $test_set) {
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $config_import['third_party_settings']['layout_builder']['sections'][0]['layout_id'] = 'ui_patterns:' . str_replace('-', '_', $test_set['component']['component_id']);
      $this->importConfigFixture(
        'core.entity_view_display.node.page.full',
        $config_import
      );
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);

      $this->drupalGet('admin/structure/types/manage/page/display/full/layout');
      $assert_session->statusCodeEquals(200);
      $component_id = str_replace('_', '-', explode(':', $test_set['component']['component_id'])[1]);
      $assert_session->elementExists('css', '.ui-patterns-' . $component_id);
      $this->drupalGet('node/' . $node->id());
      $assert_session->statusCodeEquals(200);
      $assert_session->elementExists('css', '.ui-patterns-' . $component_id);
      $this->validateRenderedComponent($test_set);
      $node->delete();
    }
  }

}
