<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_patterns_views\ViewsPluginUiPatternsTrait;
use Drupal\views\Attribute\ViewsStyle;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render all rows with a component.
 *
 * @ingroup views_style_plugins
 */
#[ViewsStyle(
  id: "ui_patterns",
  title: new TranslatableMarkup("Component (UI Patterns)"),
  help: new TranslatableMarkup("Displays views with UI components."),
  theme: "pattern_views_style",
  display_types: ["normal"],
  register_theme: FALSE,
)]
class ComponentStyle extends StylePluginBase {

  use ViewsPluginUiPatternsTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesOptions = TRUE;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->initialize($container);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    return parent::defineOptions() + [
      'ui_patterns' => [
        'default' => [
          "ui_patterns" => self::getComponentFormDefault()['ui_patterns'],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) : void {
    parent::buildOptionsForm($form, $form_state);
    // Wrapper ajax id.
    $wrapper_id = '';
    // Build ui patterns component form.
    $form['ui_patterns'] = [];
    $subform_state = SubformState::createForSubform($form['ui_patterns'], $form, $form_state);
    $form['ui_patterns']['ui_patterns'] = $this->buildComponentsForm($subform_state, $this->getFullContext());
    $form['ui_patterns']['#prefix'] = '<div id="' . $wrapper_id . '">' . ($form['ui_patterns']['#prefix'] ?? '');
    $form['ui_patterns']['#suffix'] = ($form['ui_patterns']['#suffix'] ?? '') . '</div>';
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentSettings(): array {
    if (is_array($this->configuration) && isset($this->configuration['ui_patterns']) &&
      is_array($this->configuration['ui_patterns']) && isset($this->configuration['ui_patterns']['component_id']) &&
      $this->configuration['ui_patterns']['component_id']) {
      return $this->configuration;
    }
    $styleOptions = $this->options['ui_patterns'] ?? [];
    return isset($styleOptions['ui_patterns']) ? $styleOptions : ['ui_patterns' => []];
  }

  /**
   * {@inheritdoc}
   */
  protected function getAjaxUrl(FormStateInterface $form_state): ?Url {
    return $this->getViewsUiBuildFormUrl($form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $component_configuration = $this->getComponentSettings()['ui_patterns'];
    $component_id = $component_configuration["component_id"];
    $rendered_output = parent::render();
    foreach ($rendered_output as &$rendered_output_item) {
      $rendered_output_item = ($component_id) ? $this->buildComponentRenderable($component_id, $this->getFullContext($rendered_output_item["#rows"] ?? [])) : [];
    }
    unset($rendered_output_item);
    return $rendered_output;
  }

  /**
   * Get the full context for component.
   *
   * @param array $rows
   *   Views Rows.
   *
   * @return array|\Drupal\Core\Plugin\Context\Context[]
   *   The context.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getFullContext(array $rows = []): array {
    $context = $this->getComponentSourceContexts();
    $contextDefinitionRows = new ContextDefinition('any');
    return array_merge($context, [
      "ui_patterns_views:rows" => new Context($contextDefinitionRows, $rows),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->addDependencies(parent::calculateDependencies());
  }

}
