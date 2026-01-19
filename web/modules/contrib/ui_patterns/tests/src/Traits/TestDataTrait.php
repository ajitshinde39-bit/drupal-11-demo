<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Traits;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\BrowserTestBase;

/**
 * Trait to read fixtures that describe component test situations.
 */
trait TestDataTrait {

  /**
   * Assert session object with the configuration from the test set.
   *
   * @param array $assert_session_expectations
   *   The expected actions to run on the session object.
   */
  protected function assertSessionObject(array $assert_session_expectations): void {
    if ($this instanceof BrowserTestBase) {
      $assert_session = $this->assertSession();
      $page = $this->getSession()->getPage();
      $pageContent = $page->getContent();
      foreach ($assert_session_expectations as $method => $list_of_method_arguments) {
        if (!method_exists($assert_session, $method)) {
          throw new \RuntimeException(sprintf('Method "%s" not found in assert session object.', $method));
        }
        foreach ($list_of_method_arguments as $method_arguments) {
          if (is_array($method_arguments)) {
            if ($method === "elementExists") {
              // @phpstan-ignore-next-line
              $elements = call_user_func_array([$page, "findAll"], $method_arguments);
              $this->assertTrue(count($elements) > 0, sprintf("Element %s=%s not found from %s", $method_arguments[0], $method_arguments[1], $pageContent));
            }
            elseif ($method === "elementsCount" && count($method_arguments) === 3) {
              $selectorType = $method_arguments[0];
              $selector = $method_arguments[1];
              $nodes = $page->findAll($selectorType, $selector);
              $count = (int) $method_arguments[2];
              $message = sprintf(
                      '%d %s %s found on the page, but should be %d. %s',
                      count($nodes),
                      $selectorType,
                      $selector,
                      $count,
                      $pageContent
                  );
              $this->assertTrue($count === count($nodes), $message);
            }
            else {
              // @phpstan-ignore-next-line
              call_user_func_array([$assert_session, $method], $method_arguments);
            }
          }
          else {
            // @phpstan-ignore-next-line
            call_user_func_array([$assert_session, $method], [$method_arguments]);
          }
        }
      }
    }
  }

  /**
   * Assert an expected output with the configuration from the test set.
   *
   * @param array $expected_result
   *   The expected result from the test set.
   * @param mixed $result
   *   The result.
   * @param string $message
   *   The message.
   *
   * @throws \RuntimeException
   */
  protected function assertExpectedOutput(array $expected_result, mixed $result, string $message = ''): void {
    $assert_done = FALSE;
    if (isset($expected_result['value'])) {
      $this->assertTrue(str_contains((string) $result, "" . $expected_result['value']), sprintf("%s: '%s'", $message, print_r($result, TRUE)));
      $assert_done = TRUE;
    }
    if (isset($expected_result['normalized_value'])) {
      $normalized_value = self::normalizeMarkupString((string) $result);
      $this->assertTrue(str_contains($normalized_value, $expected_result['normalized_value']), sprintf("%s: '%s'", $message, $normalized_value));
      $assert_done = TRUE;
    }
    if (isset($expected_result['same'])) {
      $this->assertSame($expected_result['same'], $result, $message);
      $assert_done = TRUE;
    }
    if (isset($expected_result['regEx'])) {
      if (is_array($result)) {
        throw new \Exception("invalid result to test for regEx: " . print_r($result, TRUE));
      }
      if (!is_string($result)) {
        $result = "" . $result;
      }
      $this->assertTrue(preg_match($expected_result['regEx'], $result) === 1, $message);
      $assert_done = TRUE;
    }
    if (isset($expected_result['rendered_value']) || isset($expected_result['rendered_value_plain'])) {
      $rendered = is_array($result) ? \Drupal::service('renderer')->renderInIsolation($result) : $result;
      if ($rendered instanceof MarkupInterface) {
        $rendered = "" . $rendered;
      }
      $normalized_rendered = self::normalizeMarkupString($rendered);
      if (isset($expected_result['rendered_value'])) {
        $message = sprintf("%s: '%s' VS '%s'", $message, $expected_result['rendered_value'], $normalized_rendered);
        $this->assertExpectedOutputGeneric($expected_result['rendered_value'], $normalized_rendered, $expected_result, $message);
      }
      if (isset($expected_result['rendered_value_plain'])) {
        $rendered_plain = Xss::filter($normalized_rendered);
        $message = sprintf("%s: '%s' VS '%s'", $message, $rendered_plain, $normalized_rendered);
        $this->assertExpectedOutputGeneric($expected_result['rendered_value_plain'], $rendered_plain, $expected_result, $message);
      }
      $assert_done = TRUE;
    }
    if (isset($expected_result['closure'])) {
      $expected_result['closure']($result);
      $assert_done = TRUE;
    }
    if (!$assert_done) {
      throw new \RuntimeException(sprintf('Missing "value" or "regEx" in expected result %s', print_r($expected_result, TRUE)));
    }
  }

  /**
   * Assert expected output generic.
   *
   * @param mixed $expected_argument
   *   The expected argument.
   * @param mixed $computed_data
   *   The computed data.
   * @param array $expected_result_metadata
   *   The expected result metadata.
   * @param string $message
   *   The message.
   */
  protected function assertExpectedOutputGeneric(mixed $expected_argument, mixed $computed_data, array $expected_result_metadata, string $message = ''): void {
    $haystack = $computed_data;
    if (!isset($expected_result_metadata["assert"])) {
      $expected_result_metadata["assert"] = "assertContains";
    }
    if ($expected_result_metadata["assert"] === "assertContains") {
      $haystack = [$computed_data];
    }
    $this->{$expected_result_metadata["assert"]}($expected_argument, $haystack, $message);
  }

  /**
   * Normalize a string of markup for comparison.
   */
  protected static function normalizeMarkupString(string $markup): string {
    $markup = preg_replace('/\s*(<|>)\s*/', '$1', $markup);
    $markup = trim($markup);
    return $markup;
  }

  /**
   * Loads test dataset fixture.
   */
  protected static function loadTestDataFixture($path = __DIR__ . "/../../fixtures/TestDataSet.yml") {
    return new class($path) {

      /**
       * The loaded fixture.
       *
       * @var array
       */
      private array $fixture;

      /**
       * Constructs the TestDataSet.
       *
       * @param string $path
       *   The path to the fixture.
       */
      public function __construct(string $path) {
        $yaml = file_get_contents($path);
        if ($yaml === FALSE) {
          throw new \InvalidArgumentException(sprintf("fixture: %s not found.", $path));
        }
        $this->fixture = Yaml::decode($yaml);
      }

      /**
       * Get test data sets.
       *
       * @return array<string, array<string, mixed> >
       *   The test data sets.
       */
      public function getTestSets() : array {
        if (!is_array($this->fixture)) {
          return [];
        }
        $test_sets = $this->fixture;
        foreach ($test_sets as $set_name => &$test_set) {
          $test_set = array_merge(["name" => $set_name], $test_set);
        }
        unset($test_set);
        return $test_sets;
      }

      /**
       * Returns data test set from name.
       *
       * @param string $set_name
       *   The test set name.
       *
       * @return array
       *   The test data set.
       */
      public function getTestSet(string $set_name): array {
        $test_sets = $this->getTestSets();
        if (is_array($test_sets[$set_name])) {
          return array_merge(["name" => $set_name], $test_sets[$set_name]);
        }
        throw new \Exception(sprintf('Test set "%s" not found.', $set_name));
      }

    };
  }

}
