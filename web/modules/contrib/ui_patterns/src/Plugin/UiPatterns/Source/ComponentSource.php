<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Utility\Token;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\ComponentPluginManager;
use Drupal\ui_patterns\Element\ComponentElementBuilder;
use Drupal\ui_patterns\Entity\SampleEntityGeneratorInterface;
use Drupal\ui_patterns\PropTypePluginManager;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourceWithChoicesInterface;
use Drupal\ui_patterns\UiPatternsNormalizerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the source.
 */
#[Source(
  id: 'component',
  label: new TranslatableMarkup('Component'),
  description: new TranslatableMarkup('Add a Component'),
  prop_types: ['slot']
)]
class ComponentSource extends SourcePluginBase implements SourceWithChoicesInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    PropTypePluginManager $propTypeManager,
    ContextRepositoryInterface $contextRepository,
    RouteMatchInterface $routeMatch,
    SampleEntityGeneratorInterface $sampleEntityGenerator,
    ModuleHandlerInterface $moduleHandler,
    Token $token,
    UiPatternsNormalizerInterface $normalizer,
    protected ComponentElementBuilder $componentElementBuilder,
    protected ComponentPluginManager $componentManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $propTypeManager, $contextRepository, $routeMatch, $sampleEntityGenerator, $moduleHandler, $token, $normalizer);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns_prop_type'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('ui_patterns.sample_entity_generator'),
      $container->get('module_handler'),
      $container->get('token'),
      $container->get('ui_patterns.normalizer'),
      $container->get('ui_patterns.component_element_builder'),
      $container->get('plugin.manager.sdc'),
    );
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    return [
      'plugin_id' => NULL,
      'settings' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $component_settings = $this->getSetting("component") ?? [];
    if (!isset($component_settings["component_id"])) {
      return [];
    }
    return [
      '#type' => 'component',
      '#component' => $component_settings["component_id"],
      '#ui_patterns' => $component_settings,
      '#source_contexts' => $this->context,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $component_settings = $this->getSetting("component") ?? [];
    $form["component"] = [
      '#type' => 'component_form',
      '#default_value' => $component_settings,
      '#source_contexts' => $this->context,
    ];
    $this->moduleHandler->alter('ui_patterns_form', $form["component"], $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(): array {
    $component_id = $this->getSetting("component")['component_id'];
    $definition = $this->componentManager->getDefinition($component_id);
    $label = $definition['annotated_name'] ?? $definition['label'] ?? $component_id;
    $variant_id = $this->getSetting("component")['variant_id']['source']['value'];
    $variant = $definition['props']['properties']['variant'];
    $variant = $variant['meta:enum'][$variant_id] ?? '';
    return array_filter([$label, $variant]);
  }

  /**
   * {@inheritdoc}
   */
  public function getChoices(): array {

    $definitions = $this->componentManager->getNegotiatedGroupedDefinitions();
    $choices = [];
    foreach ($definitions as $group_id => $group) {
      foreach ($group as $component_id => $definition) {
        $choice = [
          "label" => $definition['annotated_name'] ?? $definition['label'] ?? $component_id,
          "original_id" => $component_id,
          "group" => $group_id,
          "provider" => $definition['provider'] ?? NULL,
        ];
        if ($choice['label'] instanceof MarkupInterface) {
          $choice['label'] = (string) $choice['label'];
        }
        $choices[$component_id] = $choice;
      }
    }
    return $choices;
  }

  /**
   * {@inheritdoc}
   */
  public function getChoiceSettings(string $choice_id): array {
    return [
      'component' => [
        'component_id' => $choice_id,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getChoice(array $settings): string {
    return $settings['component_id'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    $component_settings = $this->getSetting("component") ?? [];
    if (!isset($component_settings["component_id"])) {
      return $dependencies;
    }
    SourcePluginBase::mergeConfigDependencies($dependencies,
    $this->componentElementBuilder->calculateComponentDependencies(
    $component_settings["component_id"], $component_settings, $this->context));
    return $dependencies;
  }

}
