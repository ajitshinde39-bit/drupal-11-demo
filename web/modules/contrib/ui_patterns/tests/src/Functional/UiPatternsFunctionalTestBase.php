<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Functional;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\ui_patterns\Traits\ConfigImporterTrait;
use Drupal\Tests\ui_patterns\Traits\TestContentCreationTrait;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Base function testing.
 *
 * @group ui_patterns
 */
abstract class UiPatternsFunctionalTestBase extends BrowserTestBase {

  use TestContentCreationTrait;
  use TestDataTrait;
  use ConfigImporterTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'ui_patterns_test_theme';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'ui_patterns',
    'ui_patterns_layouts',
    'field_ui',
  ];

  /**
   * The user.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected mixed $user = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer node display',
    ], NULL, TRUE);
    if ($this->user) {
      $this->drupalLogin($this->user);
    }
    else {
      throw new AccessDeniedHttpException($this->getTextContent());
    }
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
   * Validates rendered component.
   *
   * @param array $test_set
   *   The test set to validate against.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function validateRenderedComponent($test_set) {
    $output = $test_set['output'] ?? [];
    $page = $this->getSession()->getPage();

    foreach ($output as $prop_or_slot => $prop_or_slot_item) {
      foreach ($prop_or_slot_item as $prop_name => $output) {
        $expected_outputs_here = ($prop_or_slot === "props") ? [$output] : $output;
        foreach ($expected_outputs_here as $expected_output) {
          $type = $prop_or_slot;
          $selector = '.ui-patterns-' . $type . '-' . $prop_name;
          $elements = $page->findAll('css', $selector);
          $prop_value = '';
          foreach ($elements as $element) {
            $prop_value = $element->getHtml();
          }
          $message = sprintf("Test '%s' failed for prop/slot '%s' of component %s. Selector %s. Output is %s", $test_set["name"] ?? "", $prop_or_slot, $test_set['component']['component_id'], $selector, $page->getContent());
          $this->assertNotNull(
            $elements,
            $message
          );

          // Replace "same" by normalized_value.
          if (isset($expected_output["same"])) {
            if (!is_array($expected_output["same"]) && !isset($expected_output["normalized_value"])) {
              $expected_output["normalized_value"] = "" . $expected_output["same"];
            }
            unset($expected_output["same"]);
          }
          if (count($expected_output) > 0) {
            $this->assertExpectedOutput($expected_output, $prop_value, $message);
          }
        }
      }
    }
  }

}
