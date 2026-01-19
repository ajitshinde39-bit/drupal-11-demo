<?php

namespace Drupal\Tests\ui_patterns_layouts\FunctionalJavascriptTests;

use Drupal\FunctionalJavascriptTests\PerformanceTestBase;
use Drupal\node\NodeInterface;
use Drupal\Tests\ui_patterns\Traits\ConfigImporterTrait;
use Drupal\Tests\ui_patterns\Traits\TestContentCreationTrait;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Performance measuring of layout builder sections.
 *
 * @group ui_patterns_layouts
 */
class LayoutBuilderRenderPerformanceTest extends PerformanceTestBase {

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
    'field_ui',
    'layout_builder',
    'block',
  ];

  /**
   * Tests preview and output of props.
   */
  public function testRenderSections(): void {
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.yml');
    $ui_patterns_config = &$config_import['third_party_settings']['layout_builder']['sections'][0]['layout_settings']['ui_patterns'];
    $test_data = $this->loadTestDataFixture();
    $test_set = $test_data->getTestSet('textfield_default');
    $this->node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
    $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
    $config_import['third_party_settings']['layout_builder']['sections'][0]['layout_id'] = 'ui_patterns:' . str_replace('-', '_', $test_set['component']['component_id']);
    $section = $config_import['third_party_settings']['layout_builder']['sections'][0];
    for ($i = 0; $i < 1000; $i++) {
      $config_import['third_party_settings']['layout_builder']['sections'][$i + 1] = $section;
    }
    $this->importConfigFixture(
        'core.entity_view_display.node.page.full',
        $config_import
    );

    $this->collectPerformanceData(function () {
      $this->drupalGet('node/' . $this->node->id());
    }, 'UIPatternsRenderSections');
  }

}
