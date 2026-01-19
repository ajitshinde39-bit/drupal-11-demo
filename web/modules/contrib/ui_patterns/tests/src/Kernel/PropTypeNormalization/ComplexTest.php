<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel\PropTypeNormalization;

use Drupal\Tests\ui_patterns\Kernel\PropTypeNormalizationTestBase;

/**
 * Test some complex cases.
 *
 * @group ui_patterns
 */
class ComplexTest extends PropTypeNormalizationTestBase {

  /**
   * Test slot normalization.
   */
  public function testNestedComponentWithForm() : void {
    // Test nested component with form.
    $render_array_tests = [
      [
        "#type" => "inline_template",
        "#template" => "
        {% set comp_form = include('ui_patterns_test:test-form-component', {}) %}
        {{ include('ui_patterns_test:test-component', {slot: comp_form}) }}",
        "#context" => [],
      ],
      [
        "#type" => "component",
        '#component' => 'ui_patterns_test:test-component',
        "#slots" => [
          "slot" => [
            "#type" => "component",
            '#component' => 'ui_patterns_test:test-form-component',
          ],
        ],
      ],
    ];
    foreach ($render_array_tests as $render_array_test) {
      $this->assertExpectedOutput(
        [
          "rendered_value" => "<input ",
          "assert" => "assertStringContainsString",
        ],
        $render_array_test
      );
      $this->assertExpectedOutput(
        [
          "rendered_value" => "<form ",
          "assert" => "assertStringContainsString",
        ],
        $render_array_test
      );
    }
  }

  /**
   * Test slot normalization with Twig Markup.
   */
  public function testTwigMarkup() : void {
    // Test nested component with form.
    $render_array_tests = [
        [
          "#type" => "inline_template",
          "#template" => "
          {% set content %}<div>My Markup<input type='hidden' id='key' value='value' /></div>{%endset %}
          {{ include('ui_patterns_test:test-component', {slot: content}) }}",
          "#context" => [],
        ],
    ];
    foreach ($render_array_tests as $render_array_test) {
      $this->assertExpectedOutput(
        [
          "rendered_value" => "<input ",
          "assert" => "assertStringContainsString",
        ],
        $render_array_test
      );
      $this->assertExpectedOutput(
        [
          "rendered_value" => "<div>My Markup",
          "assert" => "assertStringContainsString",
        ],
        $render_array_test
      );
    }
  }

}
