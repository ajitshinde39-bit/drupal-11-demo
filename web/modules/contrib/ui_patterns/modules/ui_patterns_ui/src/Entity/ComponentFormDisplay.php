<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Core\Url;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;
use Drupal\ui_patterns\PropTypePluginBase;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\SourcePluginManager;
use Drupal\ui_patterns_ui\ComponentFormDisplayInterface;

/**
 * Defines the component display entity type.
 *
 * @ConfigEntityType(
 *   id = "component_form_display",
 *   label = @Translation("Component form display"),
 *   label_collection = @Translation("Component form displays"),
 *   label_singular = @Translation("Component form display"),
 *   label_plural = @Translation("Component form  displays"),
 *   label_count = @PluralTranslation(
 *     singular = "@count component display",
 *     plural = "@count component displays",
 *   ),
 *   config_prefix = "component_display",
 *   admin_permission = "administer component_display",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\ui_patterns_ui\Form\ComponentFormDisplayForm",
 *       "edit" = "Drupal\ui_patterns_ui\Form\ComponentFormDisplayForm",
 *       "delete" = "Drupal\ui_patterns_ui\Form\ComponentFormDisplayDeleteForm",
 *     },
 *   },
 *   links = {
 *      "collection" = "/admin/structure/component/{component_type}",
 *      "delete-form" = "/admin/structure/component/{component_type}/form-display/{form_mode_name}/delete",
 *   },
 *
 *   config_export = {
 *     "id",
 *     "label",
 *     "component_id",
 *     "form_mode_name",
 *     "content",
 *     "hidden",
 *   },
 * )
 */
final class ComponentFormDisplay extends ConfigEntityBase implements ComponentFormDisplayInterface {

  /**
   * The display ID.
   */
  protected string $id;

  /**
   * The display label.
   */
  protected string $label;

  /**
   * The sdc component id.
   */
  protected string $component_id;

  /**
   * The form mode name.
   */
  protected ?string $form_mode_name = NULL;

  /**
   * The plugin objects used for this display, keyed by prop name.
   *
   * @var array
   */
  protected $plugins = [];

  /**
   * List of slot or props display options, keyed by name.
   *
   * @var array
   */
  protected $content = [];

  /**
   * List of slots or props that are set to be hidden.
   *
   * @var array
   */
  protected $hidden = [];

  /**
   * {@inheritdoc}
   */
  public function id(): string {
    return str_replace(':', '.', $this->getComponentId()) . '.' . $this->getFormModeName();
  }

  /**
   * Returns the component plugin manager.
   */
  public static function getComponentPluginManager(): ComponentPluginManager {
    return \Drupal::service('plugin.manager.sdc');
  }

  /**
   * Returns the source plugin manager.
   */
  public static function getSourcePluginManager(): SourcePluginManager {
    return \Drupal::service('plugin.manager.ui_patterns_source');
  }

  /**
   * {@inheritdoc}
   */
  public function getComponentId():string {
    return $this->component_id;
  }

  /**
   * Checks if the prop_id is a slot or prop.
   */
  public function isSlot(string $prop_id): bool {
    return $this->getPropType($prop_id) instanceof SlotPropType;
  }

  /**
   * Returns the prop definition.
   */
  public function getPropDefinition(string $prop_id): array | NULL {
    /* @phpstan-ignore method.notFound */
    $component_definition = self::getComponentPluginManager()->negotiateDefinition($this->component_id);
    if (isset($component_definition['props']['properties'][$prop_id])) {
      return $component_definition['props']['properties'][$prop_id];
    }
    if (isset($component_definition['slots'][$prop_id])) {
      return $component_definition['slots'][$prop_id];
    }
    return NULL;
  }

  /**
   * Returns the prop type.
   */
  protected function getPropType(string $prop_id): ?PropTypePluginBase {
    $prop_definition = $this->getPropDefinition($prop_id);
    if (isset($prop_definition['ui_patterns']['type_definition'])) {
      return $prop_definition['ui_patterns']['type_definition'];
    }
    return NULL;
  }

  /**
   * Returns the source configuration.
   */
  protected function getSourceConfiguration(string $prop_id): array {
    $prop_definition = $this->getPropDefinition($prop_id);
    $display_options = $this->getPropSlotOption($prop_id);
    $settings = [];
    $settings['widget_settings'] = $display_options['widget_settings'] ?? [];
    if (!isset($settings['widget_settings']['title_display'])) {
      $settings['widget_settings']['title_display'] = 'before';
    }
    if (!isset($settings['widget_settings']['description_display'])) {
      $settings['widget_settings']['description_display'] = 'after';
    }
    $configuration = SourcePluginBase::buildConfiguration($prop_id, $prop_definition, $settings, [], []);
    $configuration['settings'] = $display_options['source'] ?? [];
    return $configuration;
  }

  /**
   * Sets the form mode name.
   */
  public function setFormModeName(string $form_mode_name):void {
    $this->form_mode_name = $form_mode_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormModeName(): ?string {
    return $this->form_mode_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function urlRouteParameters($rel) {
    $params = parent::urlRouteParameters($rel);
    $params['component_id'] = $this->getComponentId();
    $params['form_mode_name'] = $this->getFormModeName();
    return $params;
  }

  /**
   * Returns source plugins.
   */
  public function getSourcePlugins(string $prop_id): array {
    $component = $this->getPropSlotOption($prop_id);
    $sources = self::getSourcePluginManager()->getDefinitionsForPropType($this->getPropType($prop_id)->getPluginId());
    return self::getSourcePluginManager()->createInstances(array_keys($sources), ['widget_settings' => $component['widget_settings'] ?? []]);
  }

  /**
   * Returns the default source plugin.
   */
  public function getDefaultSourcePlugin(string $prop_id): ?SourcePluginBase {
    $source_id = self::getSourcePluginManager()->getPropTypeDefault($this->getPropType($prop_id)->getPluginId());
    return $source_id ? $this->getSourcePlugin($prop_id, $source_id) : NULL;
  }

  /**
   * Returns a source plugin for prop and source id.
   */
  public function getSourcePlugin(string $prop_id, string $source_id): SourcePluginBase {
    /** @var \Drupal\ui_patterns\SourcePluginBase $source */
    $source = self::getSourcePluginManager()->createInstance($source_id, $this->getSourceConfiguration($prop_id));
    return $source;
  }

  /**
   * Returns the selected source plugin for prop id.
   */
  public function getSelectedSourcePlugin(string $prop_id): ?SourcePluginBase {
    $display_options = $this->getPropSlotOption($prop_id);
    $selected_source_id = $display_options['source_id'] ?? NULL;
    assert(is_string($selected_source_id) || is_null($selected_source_id));
    if ($selected_source_id === '') {
      return NULL;
    }
    if ($selected_source_id === NULL) {
      $plugin = $this->getDefaultSourcePlugin($prop_id);
    }
    else {
      $plugin = $this->getSourcePlugin($prop_id, $selected_source_id);
    }
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropSlotOptions(): array {
    $content = $this->content;
    uasort($content, function ($a, $b) {
      return $a['weight'] <=> $b['weight'];
    });
    return $content;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropSlotOption($name) {
    return $this->content[$name] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getHighestWeight(): ?int {
    $weights = [];

    // Collect weights for the components in the display.
    foreach ($this->content as $options) {
      if (isset($options['weight'])) {
        $weights[] = $options['weight'];
      }
    }
    return $weights ? max($weights) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setPropSlotOptions($name, array $options = []) {
    // If no weight specified, make sure the field sinks at the bottom.
    if (!isset($options['weight'])) {
      $max = $this->getHighestWeight();
      $options['weight'] = isset($max) ? $max + 1 : 0;
    }
    // Ensure we always have an empty settings and array.
    $options += ['widget_settings' => [], 'third_party_settings' => []];
    $this->content[$name] = $options;
    unset($this->hidden[$name]);
    unset($this->plugins[$name]);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function removePropSlotOption($name) {
    $this->hidden[$name] = TRUE;
    unset($this->content[$name]);
    unset($this->plugins[$name]);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function toUrl($rel = NULL, array $options = []) {
    // Unless language was already provided, avoid setting an explicit language.
    $options += ['language' => NULL];
    if ($rel === 'edit-form' || $rel === NULL) {
      return Url::fromRoute('entity.component_form_display.' . $this->getComponentId() . '.edit_form', ['form_mode_name' => $this->getFormModeName()]);
    }
    if ($rel === 'collection') {
      return Url::fromRoute('entity.component_form_display.' . $this->getComponentId());
    }
    return parent::toUrl($rel, $options);
  }

  /**
   * Load form display by form mode.
   */
  public static function loadByFormMode(string $component_id, mixed $form_mode): ?ComponentFormDisplayInterface {
    if (is_array($form_mode)) {
      // Strange behavior for default value form modes.
      // @todo Debug it.
      return NULL;
    }
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[] $items */
    $items = \Drupal::entityTypeManager()->getStorage('component_form_display')
      ->loadByProperties(['component_id' => $component_id, 'form_mode_name' => $form_mode]);
    return count($items) !== 0 ? current($items) : NULL;
  }

  /**
   * Load the default display.
   */
  public static function loadDefault(string $component_id): ?ComponentFormDisplayInterface {
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay[] $items */
    $items = \Drupal::entityTypeManager()->getStorage('component_form_display')
      ->loadByProperties(['component_id' => $component_id]);
    return count($items) !== 0 ? current($items) : NULL;
  }

}
