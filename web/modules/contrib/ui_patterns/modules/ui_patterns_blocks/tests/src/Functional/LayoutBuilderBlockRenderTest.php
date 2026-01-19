<?php

namespace Drupal\Tests\ui_patterns_blocks\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test component rendering with Layout Builder.
 *
 * @group ui_patterns_blocks
 */
class LayoutBuilderBlockRenderTest extends UiPatternsFunctionalTestBase {

  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_blocks',
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
  public function testContextInFormAndRender(): void {
    $this->createTestContentContentType();
    $assert_session = $this->assertSession();
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/core.entity_view_display.node.page.full.layout_builder.classic.yml');
    $ui_patterns_config = &$config_import['third_party_settings']['layout_builder']['sections'][0]['components']['e35dd171-c69c-451f-a035-88dcb7a80af5']['configuration']['ui_patterns'];
    $test_data = $this->loadTestDataFixture();
    $test_set = $test_data->getTestSet('context_exists_default');
    $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
    $this->importConfigFixture(
      'core.entity_view_display.node.page.full',
      $config_import
    );
    $this->drupalGet('layout_builder/update/block/defaults/node.page.full/0/wrapper/e35dd171-c69c-451f-a035-88dcb7a80af5');
    $status_code = $this->getSession()->getStatusCode();
    $this->assertTrue($status_code === 200, sprintf('Status code is $status_code for test %s. %s', $test_set['name'], $this->getSession()->getPage()->getContent()));
    $assert_session->elementTextEquals('css', '.context-exists', $test_set['output']['props']['string']['value']);

    // ---
    $node = $this->createTestContentNode('page', $test_set['entity'] ?? []);
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
