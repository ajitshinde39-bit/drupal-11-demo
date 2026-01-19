<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Plugin\ContextAwarePluginAssignmentTrait;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginTrait;
use Drupal\Core\Plugin\Definition\DependentPluginDefinitionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for source plugins.
 */
abstract class DerivableContextPluginBase extends PluginBase implements
  DerivableContextInterface,
  ContextAwarePluginInterface,
  ContainerFactoryPluginInterface {

  use ContextAwarePluginAssignmentTrait;
  use ContextAwarePluginTrait;
  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The provided plugin contexts.
   *
   * @var array
   */
  protected $context = [];

  /**
   * All gathered plugin contexts.
   *
   * @var array
   */
  protected $gatheredContexts = [];

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.repository'),
    );
  }

  /**
   * Constructs a \Drupal\Component\Plugin\PluginBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $contextRepository
   *   The context repository.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected ContextRepositoryInterface $contextRepository,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
    $this->setDefinedContextValues();
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    $plugin_definition = $this->getPluginDefinition();
    // Cast the label to a string since it is a TranslatableMarkup object.
    return ($plugin_definition instanceof PluginDefinitionInterface) ? $plugin_definition->id() : (string) ($plugin_definition["label"] ?? "");
  }

  /**
   * {@inheritdoc}
   */
  abstract public function getDerivedContexts(): array;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) : void {
    if (isset($configuration['contexts'])) {
      $this->context = $configuration['contexts'];
    }
    $this->configuration = $configuration;
  }

  /**
   * Build plugin configuration.
   */
  public static function buildConfiguration(array $source_contexts): array {
    return [
      'contexts' => $source_contexts,
    ];
  }

  /**
   * Set values for the defined contexts of this plugin.
   */
  private function setDefinedContextValues(): void {
    // Fetch the available contexts.
    $available_contexts = $this->contextRepository->getAvailableContexts();

    $available_runtime_contexts = $this->context;
    // Ensure that the contexts have data by getting corresponding runtime
    // contexts.
    $available_runtime_contexts += $this->contextRepository->getRuntimeContexts(
      array_keys($available_contexts)
    );
    $plugin_context_definitions = $this->getContextDefinitions();
    $this->gatheredContexts = $available_runtime_contexts;
    foreach ($plugin_context_definitions as $name => $plugin_context_definition) {
      // Identify and fetch the matching runtime context, with the plugin's
      // context definition.
      $matches = $this->contextHandler()
        ->getMatchingContexts(
          $available_runtime_contexts,
          $plugin_context_definition
        );
      $matching_context = reset($matches);
      if ($matching_context) {
        $this->setContextValue($name, $matching_context->getContextValue());
      }
    }
  }

  /**
   * {@inheritDoc}
   */
  public function calculateDependencies() : array {
    $plugin_definition = $this->getPluginDefinition();
    if ($plugin_definition instanceof PluginDefinitionInterface) {
      return ($plugin_definition instanceof DependentPluginDefinitionInterface) ? $plugin_definition->getConfigDependencies() : [];
    }
    return $plugin_definition["config_dependencies"] ?? [];
  }

}
