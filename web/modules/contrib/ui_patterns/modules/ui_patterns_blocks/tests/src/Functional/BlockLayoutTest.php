<?php

namespace Drupal\Tests\ui_patterns_blocks\Functional;

use Drupal\Tests\ui_patterns\Functional\UiPatternsFunctionalTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test component rendering with Layout Builder.
 *
 * @group ui_patterns_blocks
 */
class BlockLayoutTest extends UiPatternsFunctionalTestBase {

  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ui_patterns',
    'ui_patterns_test',
    'ui_patterns_blocks',
    'block',
    'user',
    'contextual',
    'block_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    \Drupal::service('theme_installer')->install(['ui_patterns_test_theme']);
    \Drupal::configFactory()
      ->getEditable('system.theme')
      ->set('default', 'ui_patterns_test_theme')
      ->save();
    if ($this->user) {
      $this->drupalCreateRole(['administer blocks'], 'custom_role');
      $this->user->addRole('custom_role');
      $this->user->save();
    }
    $this->blockManager = $this->container->get('plugin.manager.block');
  }

  /**
   * Test the form and the existence of the.
   */
  public function testBlockLayout(): void {
    $test_data = self::loadTestDataFixture();
    $test_blocks_no_context = self::loadTestDataFixture(__DIR__ . "/../../fixtures/tests.blocks.no_context.yml");
    $tests = array_merge($test_data->getTestSets(), $test_blocks_no_context->getTestSets());
    $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/block.block.test_block.yml');
    $ui_patterns_config = &$config_import['settings']['ui_patterns'];
    foreach ($tests as $test_set) {
      if (isset($test_set['entity']) && is_array($test_set['entity']) && (count($test_set['entity']) > 0)) {
        // We skip tests with an entity.
        continue;
      }
      $ui_patterns_config = $this->buildUiPatternsConfig($test_set);
      $this->importConfigFixture(
        'block.block.test_block',
        $config_import
      );
      $block_ids = \Drupal::entityQuery('block')
        ->condition('theme', 'ui_patterns_test_theme')
        ->condition('region', 'content')
        ->execute();
      $this->assertArrayHasKey('test_block', $block_ids, print_r($block_ids, TRUE));
      $this->drupalGet('/user');
      $this->validateRenderedComponent($test_set);
      if (isset($test_set["assertSession"])) {
        $this->assertSessionObject($test_set["assertSession"]);
      }
      // We check that the form is appearing.
      $this->drupalGet('/admin/structure/block/manage/test_block');
      $this->assertSession()->statusCodeEquals(200);
      if (isset($test_set['component'])) {
        if (isset($test_set['component']['props']) && is_array($test_set['component']['props'])) {
          foreach ($test_set['component']['props'] as $prop_key => $prop) {
            if (!isset($prop["source"])) {
              continue;
            }
            foreach ($prop["source"] as $source_key => $source_value) {
              if (is_array($source_value) ||
                str_starts_with($prop_key, "enum_list") ||
                ($prop_key === "enum_set")) {
                continue;
              }
              $this->assertSession()->elementExists('css', '[name="settings[ui_patterns][props][' . $prop_key . '][source][' . $source_key . ']"]');
            }
          }
        }
      }
    }
  }

}
