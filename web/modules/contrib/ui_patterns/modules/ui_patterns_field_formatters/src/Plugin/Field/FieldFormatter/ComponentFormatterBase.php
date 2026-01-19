<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_field_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\Form\ComponentSettingsFormBuilderTrait;
use Drupal\ui_patterns\Plugin\Context\RequirementsContext;
use Drupal\ui_patterns\SourcePluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for ui_patterns field formatter plugin.
 */
abstract class ComponentFormatterBase extends FormatterBase {

  use ComponentSettingsFormBuilderTrait;

  /**
   * The component plugin manager.
   *
   * @var \Drupal\Core\Theme\ComponentPluginManager
   */
  protected ComponentPluginManager $componentPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->componentPluginManager = $container->get('plugin.manager.sdc');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Get list of component by manager to insert label.
    $components = $this->componentPluginManager->getDefinitions();
    $options = [];
    $options['_empty'] = $this->t('Empty');
    foreach ($components as $component_id => $component) {
      $options[$component_id] = $component['name'];
    }
    $settings = $this->getSetting('ui_patterns');
    $summary["selected"] = $this->t('No component selected.')->render();
    if (!empty($settings['component_id'])) {

      $summary["selected"] = $this->t('Component ":component" selected.', [':component' => $options[$settings['component_id']] ?? ""])->render();
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings() + self::getComponentFormDefault();
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentSettings(): array {
    if (!empty($this->configuration)) {
      return $this->configuration;
    }
    return $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $injected_contexts = $this->getComponentSourceContexts();
    // Here we need to propagate the information
    // that in the parent field_formatter hierarchy
    // The current field value has been treated in a per item manner
    // Thus, when source plugins will be fetched and displayed,
    // we properly get them especially source plugins
    // with context_requirements having field_granularity:item.
    if (is_array($this->context) && array_key_exists("context_requirements", $this->context) && $this->context["context_requirements"]->hasValue("field_granularity:item")) {
      $injected_contexts = RequirementsContext::addToContext(["field_granularity:item"], $injected_contexts);
    }
    return [
      'ui_patterns' => $this->buildComponentsForm($form_state, $injected_contexts),
    ];
  }

  /**
   * Set the context of field and entity (override the method trait).
   *
   * @param ?FieldItemListInterface $items
   *   Field items when available.
   *
   * @return array
   *   Source contexts.
   */
  protected function getComponentSourceContexts(?FieldItemListInterface $items = NULL): array {
    $contexts = parent::getComponentSourceContexts($items);
    return RequirementsContext::addToContext(["field_formatter"], $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $dependencies = parent::calculateDependencies();
    $component_configuration = $this->getComponentConfiguration();
    $component_id = $component_configuration['component_id'] ?? NULL;
    if (!$component_id) {
      return $dependencies;
    }
    $component_dependencies = $this->calculateComponentDependencies($component_id, $this->getComponentSourceContexts());
    SourcePluginBase::mergeConfigDependencies($dependencies, $component_dependencies);
    SourcePluginBase::mergeConfigDependencies($dependencies, ["module" => ["ui_patterns_field_formatters"]]);
    return $dependencies;
  }

}
