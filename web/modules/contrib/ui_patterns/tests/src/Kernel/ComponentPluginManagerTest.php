<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\KernelTests\KernelTestBase;

/**
 * Test the ComponentPluginManager service.
 *
 * @coversDefaultClass \Drupal\ui_patterns\ComponentPluginManager
 *
 * @group ui_patterns
 */
final class ComponentPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns', 'ui_patterns_test'];

  /**
   * Themes to install.
   *
   * @var string[]
   */
  protected static $themes = [];

  /**
   * The component plugin manager from ui_patterns.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected ComponentPluginManager $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->manager = \Drupal::service('plugin.manager.sdc');
  }

  /**
   * Test the method hook_component_info_alter().
   */
  public function testHookComponentInfoAlter() : void {
    $definition = $this->manager->getDefinition('ui_patterns_test:test-component');
    $this->assertEquals('Hook altered', $definition['variants']['hook']['title']);
  }

  /**
   * Test the method ::getCategories().
   */
  public function testGetCategories() : void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $categories = $manager->getCategories();
    $this->assertNotEmpty($categories);
  }

  /**
   * Test the method ::getSortedDefinitions().
   */
  public function testGetSortedDefinitions(): void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $sortedDefinitions = $manager->getSortedDefinitions();
    $this->assertNotEmpty($sortedDefinitions);
  }

  /**
   * Test the method ::getGroupedDefinitions().
   */
  public function testGetGroupedDefinitions(): void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $groupedDefinitions = $manager->getGroupedDefinitions();
    $this->assertNotEmpty($groupedDefinitions);
  }

  /**
   * Test the method ::getNegotiatedGroupedDefinitions().
   */
  public function testGetNegotiatedGroupedDefinitions(): void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $sortedDefinitions = $manager->getSortedDefinitions();
    $groupedDefinitions = $manager->getNegotiatedGroupedDefinitions();
    $this->assertNotEmpty($groupedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:test-form-component-replaced', $groupedDefinitions['Other']);
    $this->assertArrayHasKey('ui_patterns_test:test-form-component', $groupedDefinitions['Other']);
    $this->assertArrayHasKey('ui_patterns_test:no-ui-component', $sortedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:no-ui-component', $groupedDefinitions['Other']);
  }

  /**
   * Test the method ::getNegotiatedGroupedDefinitions().
   */
  public function testGetNegotiatedGroupedDefinitionsIncludeReplaces(): void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $sortedDefinitions = $manager->getSortedDefinitions();
    $groupedDefinitions = $manager->getNegotiatedGroupedDefinitions(NULL, 'label', TRUE);
    $this->assertNotEmpty($groupedDefinitions);
    $this->assertArrayHasKey('ui_patterns_test:test-form-component-replaced', $groupedDefinitions['Other']);
    $this->assertArrayHasKey('ui_patterns_test:test-form-component', $groupedDefinitions['Other']);
    $this->assertArrayHasKey('ui_patterns_test:no-ui-component', $sortedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:no-ui-component', $groupedDefinitions['Other']);
  }

  /**
   * Test the method ::getNegotiatedSortedDefinitions().
   */
  public function testGetNegotiatedSortedDefinitions(): void {
    /** @var \Drupal\ui_patterns\ComponentPluginManager $manager */
    $manager = $this->manager;
    $sortedDefinitions = $manager->getSortedDefinitions();
    $groupedDefinitions = $manager->getNegotiatedSortedDefinitions();
    $this->assertNotEmpty($groupedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:test-form-component-replaced', $groupedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:no-ui-component', $groupedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:ui-component', $groupedDefinitions);
    $this->assertArrayNotHasKey('ui_patterns_test:ui-component-replaces-no-ui', $groupedDefinitions);
    $this->assertArrayHasKey('ui_patterns_test:test-form-component', $groupedDefinitions);
  }

}
