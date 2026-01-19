<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns_legacy\Kernel;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_patterns_legacy\ConfigurationConverter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Yaml\Yaml;

/**
 * Test configuration upgrade from UI Patterns 1 to UI Patterns 2.
 */
#[CoversClass(ConfigurationConverter::class)]
#[Group('ui_patterns')]
#[Group('ui_patterns_legacy')]
#[RunTestsInSeparateProcesses]
class ConfigurationConverterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected $strictConfigSchema = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'text',
    'field',
    'node',
    'block',
    'datetime',
    'filter',
    'ui_patterns',
    'ui_patterns_library',
    'ui_patterns_legacy',
    'ui_patterns_legacy_test',
  ];

  /**
   * The configuration converter service.
   */
  protected ConfigurationConverter $configurationConverter;

  /**
   * The module extension list service.
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configurationConverter = $this->container->get('ui_patterns_legacy.configuration_converter');
    $this->moduleExtensionList = $this->container->get('extension.list.module');
  }

  /**
   * Test configuration converter.
   */
  public function testConfigurationConverter(): void {
    $fixtureModulePath = $this->moduleExtensionList->getPath('ui_patterns_legacy');

    $configNames = [
      'core.entity_view_display.node.page.ds',
      'core.entity_view_display.node.page.field_formatter',
      'core.entity_view_display.node.page.field_group',
      'core.entity_view_display.node.page.field_layout',
      'core.entity_view_display.node.page.layout_builder_components',
      'core.entity_view_display.node.page.layout_builder_section',
      'views.view.views_row',
      'views.view.views_style',
    ];
    foreach ($configNames as $configName) {
      $uip1DataPath = $fixtureModulePath . '/tests/fixtures/uip1/' . $configName . '.yml';
      /** @var array $uip1Data */
      $uip1Data = Yaml::parseFile($uip1DataPath);

      $uip2DataPath = $fixtureModulePath . '/tests/fixtures/uip2/' . $configName . '.yml';
      /** @var array $uip2Data */
      $uip2Data = Yaml::parseFile($uip2DataPath);

      $this->assertEquals($uip2Data, $this->configurationConverter->convert($uip1Data));
      // Ensure the converted config is unchanged if the update is
      // re-executed.
      $this->assertEquals($uip2Data, $this->configurationConverter->convert($uip2Data));
    }
  }

}
