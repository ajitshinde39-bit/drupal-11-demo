<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Traits;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Config\TypedConfigManagerInterface;

/**
 * Entity test data trait.
 */
trait ConfigImporterTrait {

  use SchemaCheckTrait;

  /**
   * Config is initialized.
   */
  private bool $configInitialize = FALSE;

  /**
   * Initialize config.
   */
  private function initializeConfig() {
    if ($this->configInitialize === FALSE) {
      $this->copyConfig(\Drupal::service('config.storage'), \Drupal::service('config.storage.sync'));
    }
    $this->configInitialize = TRUE;
  }

  /**
   * Load a configure fixture.
   *
   * @param string $path
   *   The path to the fixture.
   *
   * @return array
   *   The fixture.
   */
  public function loadConfigFixture(string $path):array {
    $yaml = file_get_contents($path);
    if ($yaml === FALSE) {
      throw new \InvalidArgumentException($path . ' not found.');
    }
    return Yaml::decode($yaml);
  }

  /**
   * Build UI Patterns compatible configuration for given test_set.
   *
   * @param array $test_set
   *   The test_set to build the configuration from.
   *
   * @return array
   *   The builded configuration.
   */
  protected function buildUiPatternsConfig(array $test_set):array {
    if (!isset($test_set['component']['slots'])) {
      $test_set['component']['slots'] = [];
    }
    return $test_set['component'];
  }

  /**
   * Import config fixture.
   *
   * @param string $config_id
   *   The config id.
   * @param array $config
   *   The config fixture.
   */
  public function importConfigFixture(string $config_id, array $config) {
    $this->initializeConfig();
    $type_data = \Drupal::service('Drupal\Core\Config\TypedConfigManagerInterface');
    $this->assertConfigSchema($type_data, $config_id, $config);
    \Drupal::service('config.storage.sync')->write($config_id, $config);
    $config_importer = $this->configImporter();
    $config_importer->import();
  }

  /**
   * Asserts the TypedConfigManager has a valid schema for the configuration.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   */
  public function assertConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data) {
    $check = $this->checkConfigSchema($typed_config, $config_name, $config_data);
    $message = '';
    if ($check === FALSE) {
      $message = 'Error: No schema exists.';
    }
    elseif ($check !== TRUE) {
      $this->assertIsArray($check, "The config schema check errors should be in the form of an array.");
      $message = "Errors:\n";
      foreach ($check as $key => $error) {
        $message .= "Schema key $key failed with: $error\n";
      }
      $message .= print_r($config_data, TRUE);
    }
    $this->assertTrue($check, "There should be no errors in configuration '$config_name'. $message");
  }

}
