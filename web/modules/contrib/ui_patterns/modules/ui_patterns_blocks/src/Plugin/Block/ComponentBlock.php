<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_blocks\Plugin\Block;

use Drupal\Component\Utility\Html;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Form\ComponentFormBuilderTrait;
use Drupal\ui_patterns\Resolver\ChainContextEntityResolverInterface;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns_blocks\Plugin\Derivative\ComponentBlock as DerivativeComponentBlock;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a component block.
 */
#[Block(
  id: "ui_patterns",
  admin_label: new TranslatableMarkup("Component (UI Patterns)"),
  category: new TranslatableMarkup("UI Patterns"),
  deriver: DerivativeComponentBlock::class
)]
class ComponentBlock extends BlockBase implements ContainerFactoryPluginInterface {

  use ComponentFormBuilderTrait;

  /**
   * Constructs a new MyCustomBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ui_patterns\Resolver\ChainContextEntityResolver $chainContextEntityResolver
   *   The chained context entity resolver.
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
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ui_patterns.chain_context_entity_resolver'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + $this->getComponentFormDefault();
  }

  /**
   * Get the wrapper id.
   *
   * @return string
   *   The wrapper id.
   */
  protected function getWrapperId(): string {
    return Html::getId('ui-patterns-block-' . $this->getPluginId() . "-" . $this->getDerivativeId());
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $component_dependencies = $this->calculateComponentDependencies($this->getDerivativeId(), $this->getComponentSourceContexts());
    SourcePluginBase::mergeConfigDependencies($dependencies, $component_dependencies);
    SourcePluginBase::mergeConfigDependencies($dependencies, ["module" => ["ui_patterns_blocks"]]);
    return $dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'content' => $this->buildComponentRenderable($this->getDerivativeId(), $this->getComponentSourceContexts()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $wrapper_id = $this->getWrapperId();
    $form['ui_patterns'] = $this->buildComponentsForm($form_state, $this->getComponentSourceContexts($form_state), $this->getDerivativeId());
    $form['ui_patterns']['#prefix'] = '<div id="' . $wrapper_id . '">' . ($form['#prefix'] ?? '');
    $form['ui_patterns']['#suffix'] = ($form['#suffix'] ?? '') . '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) : void {
    $this->submitComponentsForm($form_state);
  }

  /**
   * Set the context.
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
    if (!isset($this->context["bundle"]) && isset($this->context["entity"])) {
      $this->context['bundle'] = new Context(ContextDefinition::create('string'), $this->context["entity"]->getContextValue()->bundle() ?? "");
    }
    return $this->context;
  }

}
