<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_layouts\Plugin\Layout;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\Attribute\Layout;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\ui_patterns\Form\ComponentFormBuilderTrait;
use Drupal\ui_patterns\Resolver\ChainContextEntityResolverInterface;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns_layouts\Plugin\Derivative\ComponentLayout as DerivativeComponentLayout;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component Layout.
 */
#[Layout(
  id: "ui_patterns",
  deriver: DerivativeComponentLayout::class
)]
class ComponentLayout extends LayoutDefault implements ContainerFactoryPluginInterface {

  use ComponentFormBuilderTrait;

  /**
   * {@inheritdoc}
   */
  protected function addContextAssignmentElement(ContextAwarePluginInterface $plugin, array $contexts) {
    return $this->componentsAdjustContextEntitySelection(parent::addContextAssignmentElement($plugin, $contexts), "layout_builder.entity");
  }

  /**
   * Constructs a new Component Layout instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\Resolver\ChainContextEntityResolverInterface $chainContextEntityResolver
   *   The context resolver.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ChainContextEntityResolverInterface $chainContextEntityResolver,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ui_patterns.chain_context_entity_resolver'),
    );
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + $this->getComponentFormDefault();
  }

  /**
   * Returns the region names.
   *
   * @return string[]
   *   The region names.
   */
  public function getRegionNames() {
    return $this->pluginDefinition->getRegionNames();
  }

  /**
   * Returns the region.
   *
   * @return array[]
   *   The regions.
   */
  public function getRegions() {
    return $this->pluginDefinition->getRegions();
  }

  /**
   * Get the entity context if exists.
   *
   * @return array
   *   Source contexts.
   */
  protected function getComponentSourceContexts(?FormStateInterface $form_state = NULL): array {
    if (!isset($this->context['entity']) || !($this->context['entity']->getContextValue() instanceof EntityInterface)) {
      $contexts = $this->context;
      if ($form_state !== NULL) {
        $contexts['ui_patterns:form_state'] = new Context(ContextDefinition::create('any'), $form_state);
      }
      if ($entity = $this->chainContextEntityResolver->guessEntity($contexts)) {
        $this->context['entity'] = EntityContext::fromEntity($entity);
      }
    }
    if (!isset($this->context["bundle"]) && isset($this->context["entity"]) && ($entity = $this->context["entity"]->getContextValue())) {
      $this->context['bundle'] = new Context(ContextDefinition::create('string'), $entity->bundle() ?? "");
    }
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = $this->buildComponentRenderable($this->getPluginDefinition()->id(), $this->getComponentSourceContexts());
    $build['#layout'] = $this;
    $regions = parent::build($regions);
    foreach ($this->getPluginDefinition()->getRegionNames() as $region_name) {
      if (!isset($regions[$region_name])) {
        continue;
      }
      $build['#slots'][$region_name] = $regions[$region_name];
      // Add a reference from slots to regions
      // Additional markup and contextual links are added
      // by layout consumers.
      // @see Drupal\layout_builder\Element\LayoutBuilder::buildAddSectionLink
      $build[$region_name] = &$build['#slots'][$region_name];
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $contexts = $this->getComponentSourceContexts($form_state);
    $form = parent::buildConfigurationForm($form, $form_state);
    $component_id = $this->getPluginDefinition()->id();
    $form['ui_patterns'] = $this->buildComponentsForm($form_state, $contexts, $component_id, FALSE, TRUE);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state):void {
    $this->submitComponentsForm($form_state);
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $component_id = $this->getPluginDefinition()->id();
    $component_dependencies = $this->calculateComponentDependencies($component_id, $this->getComponentSourceContexts());
    SourcePluginBase::mergeConfigDependencies($dependencies, $component_dependencies);
    SourcePluginBase::mergeConfigDependencies($dependencies, ["module" => ["ui_patterns_layouts"]]);
    return $dependencies;
  }

  /**
   * Getter for the inPreview attribute.
   *
   * As there is currently no getter in the base class.
   *
   * @return bool
   *   If the layout is in preview.
   */
  public function isInPreview(): bool {
    return $this->inPreview;
  }

}
