<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\ui_patterns\Plugin\UiPatterns\PropTypeAdapter\NamespacedAttributes;

/**
 * Test the PropTypeAdapterPluginManager service.
 *
 * @group ui_patterns
 */
final class PropTypeAdapterPluginManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['ui_patterns', 'ui_patterns_test'];

  /**
   * Test callback.
   */
  public function testGuessFromSchema(): void {
    /** @var \Drupal\ui_patterns\PropTypeAdapterPluginManager $prop_type_adapter_plugin_manager */
    $prop_type_adapter_plugin_manager = \Drupal::service('plugin.manager.ui_patterns_prop_type_adapter');

    $attribute_type = $prop_type_adapter_plugin_manager->guessFromSchema(['type' => 'Drupal\Core\Template\Attribute']);
    self::assertInstanceOf(NamespacedAttributes::class, $attribute_type);

    $object_type = $prop_type_adapter_plugin_manager->guessFromSchema(['type' => 'object']);
    self::assertNull($object_type);

    $array_type = $prop_type_adapter_plugin_manager->guessFromSchema(['type' => 'array']);
    self::assertNull($array_type);
  }

}
