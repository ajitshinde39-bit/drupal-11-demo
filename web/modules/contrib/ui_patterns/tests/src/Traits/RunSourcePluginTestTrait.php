<?php

namespace Drupal\Tests\ui_patterns\Traits;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourcePluginManager;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

/**
 * Run Source with test trait.
 */
trait RunSourcePluginTestTrait {

  use TestDataTrait;
  use ConfigImporterTrait;

  /**
   * Return the component manager.
   */
  protected function componentManager(): ComponentPluginManager {
    return \Drupal::service('plugin.manager.sdc');
  }

  /**
   * Returns the source plugin manager.
   */
  protected function sourcePluginManager(): SourcePluginManager {
    return \Drupal::service('plugin.manager.ui_patterns_source');
  }

  /**
   * Returns the source contexts required by the test.
   *
   * Overwrite this function to add additional contexts.
   *
   * @param array $test_set
   *   The test set.
   *
   * @return array
   *   Returns all source contexts required by the test.
   *
   * @SuppressWarnings("PHPMD.UnusedFormalParameter")
   */
  public function getSourceContexts(array $test_set): array {
    return [];
  }

  /**
   * Returns defined contexts by context id.
   */
  private function getContextsByIds(array $test_set): array {
    $contexts = [];
    if (isset($test_set['contexts']) && is_array($test_set['contexts'])) {
      foreach ($test_set['contexts'] as $context_key => $context_value) {
        $contexts[$context_key] = new Context(new ContextDefinition('any'), $context_value);
      }
    }
    return array_merge($contexts, $this->getSourceContexts($test_set));
  }

  /**
   * Get the type definition of a prop or slot.
   *
   * @param array $component
   *   The component definition.
   * @param string $prop_or_slot_id
   *   The prop or slot id.
   *
   * @return mixed
   *   The type definition.
   */
  private function getComponentPropOrSlotTypeDefinition(array $component, string $prop_or_slot_id): mixed {
    $type_definition = NULL;
    if (isset($component['props']['properties'][$prop_or_slot_id])) {
      $type_definition = $component['props']['properties'][$prop_or_slot_id]['ui_patterns']['type_definition'];
    }
    elseif (isset($component['slots'][$prop_or_slot_id])) {
      $type_definition = $component['slots'][$prop_or_slot_id]['ui_patterns']['type_definition'];
    }
    else {
      throw new \RuntimeException(sprintf("Prop or Slot '%s' not found in component %s", $prop_or_slot_id, $component["id"]));
    }
    return $type_definition;
  }

  /**
   * Get the source plugin for a prop or slot.
   *
   * @param array $component
   *   The component definition.
   * @param string $prop_or_slot_id
   *   The prop or slot id.
   * @param array $prop_or_slot_configuration
   *   The prop or slot configuration.
   * @param string $source_id
   *   The source id.
   * @param array $context
   *   The context.
   *
   * @return \Drupal\ui_patterns\SourcePluginBase|null
   *   The source plugin or NULL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function getSourcePluginForPropOrSlot(array $component, string $prop_or_slot_id, array $prop_or_slot_configuration, string $source_id, array $context): ?SourcePluginBase {
    $type_definition = $this->getComponentPropOrSlotTypeDefinition($component, $prop_or_slot_id);
    assertNotNull($type_definition);
    $configuration = SourcePluginBase::buildConfiguration($prop_or_slot_id, $component['props']['properties'][$prop_or_slot_id] ?? $component['slots'][$prop_or_slot_id], $prop_or_slot_configuration, $context);
    $plugin = $this->sourcePluginManager()->createInstance($source_id, $configuration);
    return ($plugin instanceof SourcePluginBase) ? $plugin : NULL;
  }

  /**
   * Runs source plugin test with given test_set and source.
   *
   * @param array $test_set
   *   The test set.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function runSourcePluginTest(array $test_set): void {
    if (!isset($test_set['skip_schema_check']) || $test_set['skip_schema_check'] === FALSE) {
      $config_import = $this->loadConfigFixture(__DIR__ . '/../../fixtures/config/block.block.schema_test_block.yml');
      $type_data = \Drupal::service('Drupal\Core\Config\TypedConfigManagerInterface');
      $config_import['settings']['ui_patterns'] = $test_set['component'];
      $this->assertConfigSchema($type_data, 'block.block.schema_test_block', $config_import);
    }
    $component_configuration = $test_set['component'];
    $component_id = $component_configuration["component_id"];
    $component = $this->componentManager()->getDefinition($component_id);
    $contexts = $this->getContextsByIds($test_set);
    $expected_outputs = $test_set['output'] ?? [];
    $prop_or_slots = ["props", "slots"];
    foreach ($prop_or_slots as $prop_or_slot) {
      if (!isset($expected_outputs[$prop_or_slot], $component_configuration[$prop_or_slot])) {
        continue;
      }
      foreach ($component_configuration[$prop_or_slot] as $prop_or_slot_id => $prop_or_slot_configuration) {
        if (isset($expected_outputs[$prop_or_slot][$prop_or_slot_id])) {
          // For slots, there is a table of sources for each slot.
          $source_to_tests = ($prop_or_slot === "props") ? [$prop_or_slot_configuration] : $prop_or_slot_configuration["sources"];
          $expected_outputs_here = ($prop_or_slot === "props") ? [$expected_outputs[$prop_or_slot][$prop_or_slot_id]] : $expected_outputs[$prop_or_slot][$prop_or_slot_id];
          foreach ($source_to_tests as $index => $source_to_test) {
            if (!isset($source_to_test["source_id"])) {
              throw new \RuntimeException(sprintf("Missing source_id for '%s' in test_set '%s'", $prop_or_slot_id, $test_set["name"] ?? ""));
            }
            $plugin = $this->getSourcePluginForPropOrSlot($component, $prop_or_slot_id, $source_to_test, $source_to_test["source_id"], $contexts);
            assertTrue($plugin instanceof SourcePluginBase);
            // getPropValue() returns
            // the 'raw' value of the source compatible with the type declared.
            // getValue() returns
            // the value of the source converted to the type definition.
            $prop_value = $plugin->getValue($plugin->getPropDefinition()["ui_patterns"]["type_definition"]);
            $message = sprintf("Test '%s' failed for prop/slot '%s' of component %s with source '%s'", $test_set["name"] ?? "", $prop_or_slot_id, $component_id, $source_to_test["source_id"]);
            $this->assertExpectedOutput($expected_outputs_here[$index], $prop_value, $message);

          }
        }
      }
    }
  }

}
