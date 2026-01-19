<?php

declare(strict_types=1);

namespace Drupal\Tests\ui_patterns\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\ui_patterns\Traits\TestDataTrait;

/**
 * Test node visitor.
 *
 * @group ui_patterns
 */
class TwigVisitorTest extends KernelTestBase {

  use TestDataTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'text',
    'field',
    'node',
    'ui_patterns',
    'ui_patterns_test',
    'datetime',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('node', 'node_access');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig(['system', 'filter']);
  }

  /**
   * Test different twig usages.
   */
  public function testTwigIntegration() : void {

    $default_context = [
      'prop_string' => $this->randomMachineName(),
      'attributes' => [],
    ];
    $twig_templates = [
      // Include tag.
      '{% include "ui_patterns_test:test-component" with { string: prop_string } %}',
      '{% include "ui_patterns_test:test-component" with { attributes: {}, string: prop_string } %}',
      '{% include "ui_patterns_test:test-component" with { attributes: create_attribute(), string: prop_string } %}',
      '{% include "ui_patterns_test:test-component" with { attributes: "", string: prop_string } %}',
      // Embed tag.
      "{% embed 'ui_patterns_test:test-component' with { attributes: {}, string: prop_string } only  %}
           {% block content %}
               {{ content }}
             {% endblock %}
           {% endembed %}",
      "{% embed 'ui_patterns_test:test-component' with { attributes: create_attribute(), string: prop_string } only  %}
                 {% block content %}
                     {{ content }}
                   {% endblock %}
                 {% endembed %}",
      "{% embed 'ui_patterns_test:test-component' with { attributes: '', string: prop_string } only  %}
                       {% block content %}
                           {{ content }}
                         {% endblock %}
                       {% endembed %}",
      "{% embed 'ui_patterns_test:test-component' with { string: prop_string } only  %}
                 {% block content %}
                     {{ content }}
                   {% endblock %}
                 {% endembed %}",
      // Include function.
      '{{ include("ui_patterns_test:test-component", { string: prop_string }) }}',
      '{{ include("ui_patterns_test:test-component", { attributes: {}, string: prop_string }) }}',
      '{{ include("ui_patterns_test:test-component", { attributes: create_attribute(), string: prop_string }) }}',
      '{{ include("ui_patterns_test:test-component", { attributes: "", string: prop_string }) }}',
    ];
    $render_array = [
      '#type' => 'inline_template',
      '#context' => $default_context,
    ];
    foreach ($twig_templates as $twig_template) {
      $render_array_test = array_merge($render_array, ['#template' => $twig_template]);
      $this->assertExpectedOutput(
        [
          "rendered_value_plain" => $default_context["prop_string"],
          "rendered_value" => $default_context["prop_string"],
          "assert" => "assertStringContainsString",
        ],
        $render_array_test
      );
    }
  }

}
