<?php

namespace Drupal\Tests\ui_patterns_views\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test component rendering in views.
 *
 * @group ui_patterns_views
 */
class ViewsRenderTest extends UiPatternsFunctionalTestBase {

  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_field_formatters',
    'ui_patterns_views',
    'views',
    'views_ui',
    'block',
  ];

  /**
   * Test to see if UIP plugins appear.
   */
  public function testPlugins(): void {
    $this->createTestContentContentType();
    $assert_session = $this->assertSession();
    $view_test_data = self::loadTestDataFixture(__DIR__ . "/../../fixtures/TestDataSet.yml");
    // ---
    // View Style
    // ---
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.yml');
    $this->importConfigFixture(
      'views.view.test',
      $config_import
    );
    // Check that the Views style plugin appears.
    $this->drupalGet('admin/structure/views/nojs/display/test/default/style');
    $assert_session->elementTextEquals("css", ".form-item label", "Component (UI Patterns)");
    // Configure the style plugin.
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.style.yml');
    $ui_patterns_config = &$config_import['display']['page_1']['display_options']["style"]["options"]["ui_patterns"]['ui_patterns'];
    $tests = $view_test_data->getTestSets();
    foreach ($tests as $test_set_name => $test_set) {
      if (!str_starts_with($test_set_name, 'style') || !isset($test_set["assertSession"])) {
        continue;
      }
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $this->importConfigFixture(
        'views.view.test',
        $config_import
      );
      \Drupal::service('router.builder')->rebuild();
      $this->drupalGet('test');
      $this->assertSessionObject($test_set["assertSession"]);
      $node->delete();
    }
    // ---
    // View Rows
    // ---
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.yml');
    $this->importConfigFixture(
      'views.view.test',
      $config_import
    );
    // Check that the View rows style plugin appears.
    $this->drupalGet('admin/structure/views/nojs/display/test/default/row');
    $assert_session->elementTextEquals("css", ".form-item label", "Component (UI Patterns)");
    // Configure the row style plugin.
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.row_style.yml');
    $ui_patterns_config = &$config_import['display']['page_1']['display_options']["row"]["options"]["ui_patterns"];
    $tests = $view_test_data->getTestSets();
    foreach ($tests as $test_set_name => $test_set) {
      if (!str_starts_with($test_set_name, 'row_style') || !isset($test_set["assertSession"])) {
        continue;
      }
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $this->importConfigFixture(
        'views.view.test',
        $config_import
      );
      \Drupal::service('router.builder')->rebuild();
      $this->drupalGet('test');
      $this->validateRenderedComponent($test_set);
      $this->assertSessionObject($test_set["assertSession"]);
      $node->delete();
    }
    // ---
    // View field
    // ---
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.yml');
    $this->importConfigFixture(
      'views.view.test',
      $config_import
    );
    // Check that field formatter plugin appears.
    $this->drupalGet('admin/structure/views/nojs/handler/test/default/field/title');
    $text_field_formatter_plugin = "Component per item (UI Patterns)";
    $key_field_formatter_plugin = "ui_patterns_component_per_item";
    $page = $this->getSession()->getPage();
    $nodes = $page->findAll("css", "select[name='options[type]'] option");
    $textFound = FALSE;
    $valueFound = FALSE;
    foreach ($nodes as $node) {
      $text = $node->getText();
      if ($text === $text_field_formatter_plugin) {
        $textFound = TRUE;
      }
      if ($node->getAttribute("value") === $key_field_formatter_plugin) {
        $valueFound = TRUE;
      }
    }
    if (!$textFound || !$valueFound) {
      $this->fail(sprintf("Option not found for field formatter: %s / %s", $key_field_formatter_plugin, $text_field_formatter_plugin));
    }
    // Configure a field, with field formatter plugin.
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/views.view.test.field.yml');
    $ui_patterns_config = &$config_import['display']['page_1']['display_options']["fields"]["title"]["settings"]["ui_patterns"];
    $tests = $view_test_data->getTestSets();
    foreach ($tests as $test_set_name => $test_set) {
      if (!str_starts_with($test_set_name, 'field') || !isset($test_set["assertSession"])) {
        continue;
      }
      $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $this->importConfigFixture(
        'views.view.test',
        $config_import
      );
      \Drupal::service('router.builder')->rebuild();
      $this->drupalGet('test');
      $this->assertSessionObject($test_set["assertSession"]);
      $node->delete();
    }
  }

}
