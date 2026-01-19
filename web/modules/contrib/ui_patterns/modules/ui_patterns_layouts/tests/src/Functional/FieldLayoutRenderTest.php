<?php

namespace Drupal\Tests\ui_patterns_layouts\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test components rendering as layouts.
 *
 * @group ui_patterns_layouts
 */
class FieldLayoutRenderTest extends UiPatternsFunctionalTestBase {


  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_layouts',
    'field_ui',
    'field_layout',
    'block',
  ];

  /**
   * Test the form and the existence of the.
   */
  public function testContextInForm(): void {
    $assert_session = $this->assertSession();
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.field_layout.yml');
    $ui_patterns_config = &$config_import['third_party_settings']['field_layout']['settings']['ui_patterns'];
    $test_data = $this->loadTestDataFixture();
    $test_set = $test_data->getTestSet('context_exists_default');
    $this->createTestContentContentType();
    $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
    $this->importConfigFixture(
      'core.entity_view_display.node.page.default',
      $config_import
    );
    $this->drupalGet('admin/structure/types/manage/page/display');
    $assert_session->statusCodeEquals(200);
    $assert_session->elementTextEquals('css', '.context-exists', $test_set['output']['props']['string']['value']);
  }

  /**
   * Tests preview and output of props.
   */
  public function testRenderProps(): void {
    $assert_session = $this->assertSession();
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.field_layout.yml');

    $test_data = $this->loadTestDataFixture();
    $tests = [
      $test_data->getTestSet('context_exists_default'),
      $test_data->getTestSet('func_attributes_empty'),
      $test_data->getTestSet('func_attributes_default'),
      $test_data->getTestSet('textfield_default'),
      $test_data->getTestSet('token_default'),
    ];

    foreach ($tests as $test_set) {
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config = &$config_import['third_party_settings']['field_layout']['settings']['ui_patterns'];
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $this->importConfigFixture(
        'core.entity_view_display.node.page.default',
        $config_import
      );
      $component_id = str_replace('_', '-', explode(':', $test_set['component']['component_id'])[1]);
      $this->drupalGet('node/' . $node->id());
      $assert_session->statusCodeEquals(200);
      $assert_session->elementExists('css', '.ui-patterns-' . $component_id);
      $this->validateRenderedComponent($test_set);
      $node->delete();
    }
  }

  /**
   * Tests preview and output of slots.
   */
  public function testRenderSlots(): void {
    $node = $this->createTestContentNode('page', ['field_text_1' => ['value' => 'field_text_1 value']]);
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.field_layout.yml');
    $this->importConfigFixture(
      'core.entity_view_display.node.page.default',
      $config_import
    );
    $assert_session = $this->assertSession();
    $this->drupalGet('node/' . $node->id());
    $assert_session->elementExists('css', '.ui-patterns-test-component');
    $assert_session->elementTextContains('css', '.ui-patterns-slots-slot', 'field_text_1 value');
  }

}
