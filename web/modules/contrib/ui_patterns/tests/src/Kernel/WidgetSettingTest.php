<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\Source;

use Drupal\Tests\ui_patterns\Kernel\SourcePluginsTestBase;
use Drupal\ui_patterns\SourcePluginBase;

/**
 * Test WidgetSettings.
 *
 * @coversDefaultClass \Drupal\ui_patterns\Plugin\UiPatterns\Source\TextfieldWidget
 * @group ui_patterns
 */
class WidgetSettingTest extends SourcePluginsTestBase {

  /**
   * Test merge default settings.
   */
  public function testWidgetDefaultSetting(): void {
    $configuration = SourcePluginBase::buildConfiguration('prop_id', [], [], []);
    /** @var \Drupal\ui_patterns\Plugin\UiPatterns\Source\TextfieldWidget $source */
    $source = $this->sourcePluginManager()->createInstance('textfield', $configuration);
    $this->assertNotNull($source);
    $this->assertFalse($source->getWidgetSetting('required'));
    $this->assertEquals('', $source->getWidgetSetting('title'));
  }

  /**
   * Test widget overwrite from configuration.
   */
  public function testWidgetOverwriteSetting(): void {
    $configuration = SourcePluginBase::buildConfiguration('prop_id', [], [
      'widget_settings' => ['required' => TRUE, 'title' => 'test'],
    ], []);
    /** @var \Drupal\ui_patterns\Plugin\UiPatterns\Source\TextfieldWidget $source */
    $source = $this->sourcePluginManager()->createInstance('textfield', $configuration);
    $this->assertNotNull($source);
    $this->assertTrue($source->getWidgetSetting('required'));
    $this->assertEquals('test', $source->getWidgetSetting('title'));
  }

}
