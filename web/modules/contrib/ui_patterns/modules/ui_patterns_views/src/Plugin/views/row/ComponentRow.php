<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_views\Plugin\views\row;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\ui_patterns_views\ViewsPluginUiPatternsTrait;
use Drupal\views\Attribute\ViewsRow;
use Drupal\views\Plugin\views\row\Fields;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render a single row with a component.
 *
 * @ingroup views_row_plugins
 */
#[ViewsRow(
  id: "ui_patterns",
  title: new TranslatableMarkup("Component (UI Patterns)"),
  help: new TranslatableMarkup("Displays fields using an UI component."),
  display_types: ['normal'],
  theme: "pattern_views_row",
  register_theme: FALSE,
)]
class ComponentRow extends Fields {

  use ViewsPluginUiPatternsTrait;

  /**
   * The sample entity generator.
   *
   * @var \Drupal\ui_patterns\Entity\SampleEntityGenerator
   */
  protected $sampleEntityGenerator;

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
    $instance->sampleEntityGenerator = $container->get('ui_patterns.sample_entity_generator');
    return $instance;
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
  public function getComponentSettings(): array {
    if (!empty($this->configuration['ui_patterns'])) {
      return $this->configuration;
    }
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions(): array {
    return parent::defineOptions() + ['ui_patterns' => ['default' => self::getComponentFormDefault()['ui_patterns']]];
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) : void {
    parent::buildOptionsForm($form, $form_state);
    // Supported options.
    $keep_element_form = ['hide_empty'];
    $children = Element::children($form);
    foreach ($children as $child) {
      if (!in_array($child, $keep_element_form, TRUE)) {
        unset($form[$child]);
      }
    }
    // Build ui patterns component form.
    $form['ui_patterns'] = $this->componentSettingsForm($form, $form_state, $this->getFullContext());
    $form['ui_patterns']["#component_validation"] = FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOptionsForm(&$form, FormStateInterface $form_state) : void {}

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    return $this->buildComponentRenderable($this->getComponentConfiguration()['component_id'], $this->getFullContext($row));
  }

  /**
   * Get the source contexts for the component.
   *
   * @param mixed $row
   *   The view row if relevant.
   *
   * @return array
   *   Source contexts.
   */
  protected function getFullContext(mixed $row = NULL): array {
    $context = $this->getComponentSourceContexts();
    $entity = NULL;
    $bundle = NULL;
    $view = $this->view;
    if ($row === NULL) {
      $base_entity_type = $view->getBaseEntityType();
      if ($base_entity_type instanceof EntityTypeInterface) {
        $base_entity_type_id = "" . $base_entity_type->id();
        $entity = $this->sampleEntityGenerator->get($base_entity_type_id, $this->findEntityBundle($base_entity_type_id));
        $bundle = "";
      }
    }
    else {
      $context['ui_patterns_views:row:index'] = new Context(new ContextDefinition('integer'), $row->index ?? 0);
      $entity = $row->_entity;
      $bundle = ($row->_entity instanceof EntityInterface) ? $row->_entity->bundle() : "";
    }
    if ($entity instanceof EntityInterface) {
      $context['entity'] = EntityContext::fromEntity($entity);
      $context['bundle'] = new Context(new ContextDefinition('string'), $bundle);
    }
    return $context;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return $this->addDependencies(parent::calculateDependencies());
  }

}
