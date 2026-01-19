<?php

declare(strict_types=1);

namespace Drupal\ui_patterns\Plugin\UiPatterns\Source;

use Drupal\Component\Plugin\Definition\PluginDefinitionInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityDisplayBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\FormatterInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\ui_patterns\SourcePluginBase;
use Drupal\ui_patterns\Plugin\Derivative\FieldFormatterSourceDeriver;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Source plugin for field formatter.
 */
#[Source(
  id: 'field_formatter',
  label: new TranslatableMarkup('[Field] Formatter'),
  description: new TranslatableMarkup('Entity Field formatted with a field formatter'),
  deriver: FieldFormatterSourceDeriver::class
)]
class FieldFormatterSource extends FieldValueSourceBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderFormatterSettingsForm'];
  }

  use LoggerChannelTrait;

  use FieldFormatterFormTrait;

  /**
   * The formatter plugin manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager|null
   */
  protected ?FormatterPluginManager $formatterPluginManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface|null
   */
  protected ?FieldTypePluginManagerInterface $fieldTypePluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->formatterPluginManager = $container->get('plugin.manager.field.formatter');
    $instance->fieldTypePluginManager = $container->get('plugin.manager.field.field_type');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $this->buildFieldFormatterForm($form, $form_state);
    return $form;
  }

  /**
   * Callback to build field formatter form.
   *
   * @param array $form
   *   The source plugin settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return bool
   *   True if the form was generated.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function buildFieldFormatterForm(array &$form, FormStateInterface $form_state) {
    $field_definition = $this->getFieldDefinition();
    if (!$field_definition instanceof FieldDefinitionInterface) {
      return FALSE;
    }
    $field_storage = $field_definition->getFieldStorageDefinition();
    $this->generateFieldFormatterForm($form, $form_state, $field_definition, $field_storage);
    return TRUE;
  }

  /**
   * Generate the form formatter field formatter.
   *
   * @param array $form
   *   The source plugin settings form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage
   *   Field storage.
   *
   * @return bool
   *   False if can't generate.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function generateFieldFormatterForm(array &$form, FormStateInterface $form_state, FieldDefinitionInterface $field_definition, FieldStorageDefinitionInterface $field_storage): bool {
    $formatter_options = $this->getAvailableFormatterOptions($field_storage, $field_definition);
    if (empty($formatter_options)) {
      return FALSE;
    }
    // @todo remove ui patterns formatters from the list of options ?
    // Get the formatter type from configuration.
    $formatter_type = $this->getSettingsFromConfiguration(["settings", "type"]);
    $uniqueID = Html::getId(implode("_", $this->formArrayParents ?? []) . "_field-formatter-settings-ajax");
    // Get the formatter settings from configuration.
    $form['type'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Formatter'),
      '#options' => $formatter_options,
      '#default_value' => $formatter_type,
      '#empty_option' => $this->t('- Select -'),
      // Note: We cannot use ::foo syntax, because the form is the entity form
      // display.
      '#ajax' => [
        'callback' => [__CLASS__, 'onFormatterTypeChange'],
        'wrapper' => $uniqueID,
        'method' => 'replaceWith',
      ],
    ];
    $form['settings'] = [];
    $form['third_party_settings'] = [];
    $form['settings_wrapper'] = [
      '#prefix' => '<div id="' . $uniqueID . '">',
      '#suffix' => '</div>',

    ];
    $options = [
      'field_definition' => $field_definition,
      'configuration' => $this->getFormatterConfiguration($form_state, $formatter_options, $formatter_type),
      'view_mode' => EntityDisplayBase::CUSTOM_MODE,
      'prepare' => TRUE,
    ];

    if ($formatter = $this->formatterPluginManager->getInstance($options)) {
      // This probably needs a better way (interface?)
      if (method_exists($formatter, "setContext")) {
        // The Source is giving its context to the field formatter.
        $formatter->setContext($this->context);
      }
      // Settings and third_party_settings are rendered into settings_wrapper.
      // see the preRenderFormatterSettingsForm() method.
      // but they are created in the form array at the root level.
      // to ensure configuration structure does not add settings_wrapper.
      $settings_subform_state = SubformState::createForSubform($form["settings"], $form, $form_state);
      $form['settings'] = $formatter->settingsForm($form, $settings_subform_state);
      $form['third_party_settings'] = $this->thirdPartySettingsForm($formatter, $field_definition, $form, $form_state);
      // Should we use FormHelper::rewriteStatesSelector() ?
      // like in FieldBlock::formatterSettingsProcessCallback.
    }
    $form['#pre_render'][] = [static::class, 'preRenderFormatterSettingsForm'];
    return TRUE;
  }

  /**
   * Customize slot or prop form elements (pre-render).
   *
   * @param array $element
   *   Element to process.
   *
   * @return array
   *   Processed element
   */
  public static function preRenderFormatterSettingsForm(array $element) : array {
    $element['settings_wrapper']['settings'] = $element['settings'];
    $element['settings']["#printed"] = TRUE;
    $element['settings_wrapper']['third_party_settings'] = $element['third_party_settings'];
    $element['third_party_settings']["#printed"] = TRUE;
    return $element;
  }

  /**
   * Retrieve the settings from the configuration.
   *
   * @return array
   *   The settings.
   */
  private function getFormatterBaseSettingsFromConfiguration() : array {
    $base_container = $this->getSettingsFromConfiguration(["settings"]) ?? [];
    if (isset($base_container['settings'])) {
      // Backward compatibility.
      return [
        'settings' => is_array($base_container['settings']) ? $base_container['settings'] : [],
        'third_party_settings' => $base_container['third_party_settings'] ?? [],
      ];
    }
    return [];
  }

  /**
   * Gets the formatter configuration.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $formatter_options
   *   Array of formatters options.
   * @param string|null $formatter_type
   *   The formatter name.
   *
   * @return array
   *   The formatter configuration.
   */
  private function getFormatterConfiguration(FormStateInterface $form_state, array $formatter_options, ?string $formatter_type = ''): array {
    $settings = [];
    $third_party_settings = [];
    if (!empty($formatter_type)) {
      $formatter_configuration = $this->getFormatterBaseSettingsFromConfiguration();
      $settings = $formatter_configuration["settings"] ?? [];
      $third_party_settings = $formatter_configuration["third_party_settings"] ?? [];
    }

    // Get default formatter type.
    if (empty($formatter_type) || !isset($formatter_options[$formatter_type])) {
      $default_settings = $this->defaultSettings();
      $formatter_type = $default_settings["type"] ?? key($formatter_options);
      $settings = $default_settings["settings"];
      $third_party_settings = [];
    }
    // Reset settings if we change the formatter.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element) && $triggering_element['#value'] === $formatter_type) {
      $settings = $this->formatterPluginManager->getDefaultSettings($formatter_type);
      $third_party_settings = [];
    }
    if (empty($settings) && !empty($formatter_type)) {
      $settings = $this->formatterPluginManager->getDefaultSettings($formatter_type);
      $third_party_settings = [];
    }

    return [
      'settings' => $settings,
      'third_party_settings' => $third_party_settings,
      'type' => $formatter_type,
      'label' => '',
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultSettings(): array {
    $field_definition = $this->getFieldDefinition();
    if (!$field_definition instanceof FieldDefinitionInterface) {
      return [];
    }
    $field_storage = $field_definition->getFieldStorageDefinition();
    $formatter_type = $this->fieldTypePluginManager->getDefinition($field_storage->getType())['default_formatter'] ?? NULL;
    $settings = $this->formatterPluginManager->getDefaultSettings($field_storage->getType());
    $third_party_settings = [];
    return [
      'type' => $formatter_type,
      'settings' => is_array($settings) ? $settings : [],
      'third_party_settings' => is_array($third_party_settings) ? $third_party_settings : [],
    ];
  }

  /**
   * Adds the formatter third party settings forms.
   *
   * @param \Drupal\Core\Field\FormatterInterface $plugin
   *   The formatter.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param array $form
   *   The (entire) configuration form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The formatter third party settings form.
   */
  protected function thirdPartySettingsForm(FormatterInterface $plugin, FieldDefinitionInterface $field_definition, array $form, FormStateInterface $form_state) {
    $settings_form = [];
    // Invoke hook_field_formatter_third_party_settings_form(), keying resulting
    // subforms by module name.
    $this->moduleHandler->invokeAllWith(
      'field_formatter_third_party_settings_form',
      function (callable $hook, string $module) use (&$settings_form, $plugin, $field_definition, $form, $form_state) {
        $settings_form[$module] = $hook(
          $plugin,
          $field_definition,
          EntityDisplayBase::CUSTOM_MODE,
          $form,
          $form_state,
        );
      }
    );
    return $settings_form;
  }

  /**
   * Create an instance of field formatter.
   *
   * @param string $formatter_id
   *   The formatter id.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition of field to apply formatter.
   *
   * @return \Drupal\Core\Field\FormatterInterface
   *   The field formatter plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function createInstanceFormatter(string $formatter_id, FieldDefinitionInterface $field_definition) {
    // @todo Ensure it is right to empty all values here, see:
    // https://api.drupal.org/api/drupal/core%21lib%21Drupal%21Core%21Field%21FormatterPluginManager.php/class/FormatterPluginManager/8.2.x
    $configuration = [
      'field_definition' => $field_definition,
      'settings' => [],
      'label' => '',
      'view_mode' => '',
      'third_party_settings' => [],
    ];
    /** @var \Drupal\Core\Field\FormatterInterface $instance */
    $instance = $this->formatterPluginManager->createInstance($formatter_id, $configuration);
    return $instance;
  }

  /**
   * Get all available formatters by loading available ones and filtering out.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition
   *   The field storage definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition.
   *
   * @return string[]
   *   The field formatter labels keys by plugin ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getAvailableFormatterOptions(FieldStorageDefinitionInterface $field_storage_definition, FieldDefinitionInterface $field_definition): array {
    $formatters = $this->formatterPluginManager->getOptions($field_storage_definition->getType());
    $formatter_instances = [];
    foreach ($formatters as $formatter_id => $formatter) {
      $formatter_instances[$formatter_id] = $this->createInstanceFormatter($formatter_id, $field_definition);
    }

    $filtered_formatter_instances = $this->filterFormatter($formatter_instances, $field_definition);
    $options = array_map(
      static function (FormatterInterface $formatter) {
        $plugin_definition = $formatter->getPluginDefinition();
        return ($plugin_definition instanceof PluginDefinitionInterface) ? $plugin_definition->id() : $plugin_definition["label"];
      }, $filtered_formatter_instances);

    // Remove field_link itself.
    if (array_key_exists('field_link', $options)) {
      unset($options['field_link']);
    }
    // $options = ["" => $this->t('- Select -')] + $options;
    return $options;
  }

  /**
   * Render field item(s) with the field formatter.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   Items.
   * @param int|null $field_delta
   *   Field delta.
   *
   * @return array
   *   Render array
   */
  private function viewFieldItems(FieldItemListInterface $items, $field_delta = NULL): array {
    $returned = [];
    $configuration = $this->getConfiguration();
    $formatter_type = $configuration['settings']['type'] ?? '';
    $formatter_settings_wrapper = $this->getFormatterBaseSettingsFromConfiguration();
    if (empty($formatter_type)) {
      // No formatter has been configured.
      return $returned;
    }
    // We use third_party_settings to propagate context to the formatter.
    // Only our formatter will know how to use it.
    $formatter_config = [
      'type' => $formatter_type,
      'settings' => $formatter_settings_wrapper['settings'] ?? [],
      'third_party_settings' => array_merge($formatter_settings_wrapper['third_party_settings'] ?? [], [
        'ui_patterns' => [
          'context' => $this->context,
        ],
      ]),
    ];
    if ($field_delta === NULL) {
      $rendered_field = $items->view($formatter_config);
      for ($field_index = 0; $field_index < $items->count(); $field_index++) {
        if (!isset($rendered_field[$field_index])) {
          continue;
        }
        $returned[] = $rendered_field[$field_index];
      }
      return $returned;
    }
    try {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $item = $items->get($field_delta);
      return ($item instanceof FieldItemInterface) ? [$item->view($formatter_config)] : [];
    }
    catch (MissingDataException) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    $items = $this->getEntityFieldItemList();
    if (!$items instanceof FieldItemListInterface) {
      return [];
    }
    $field_index = (isset($this->context['ui_patterns:field:index'])) ? $this->getContextValue('ui_patterns:field:index') : NULL;
    return $this->viewFieldItems($items, $field_index);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() : array {
    $dependencies = parent::calculateDependencies();
    $configuration = $this->getConfiguration();
    $fieldDefinition = $this->getFieldDefinition();
    if (empty($configuration['settings']['type'])) {
      return $dependencies;
    }
    $formatter = NULL;
    try {
      $formatter = $this->createInstanceFormatter($configuration['settings']['type'], $fieldDefinition);
    }
    catch (\Throwable $exception) {
      // During install computeDependencies instance this plugin.
      // This can lead to unexpected configuration states. We can ignore it.
    }

    if (!$formatter) {
      return $dependencies;
    }
    SourcePluginBase::mergeConfigDependencies($dependencies, $this->getPluginDependencies($formatter));
    SourcePluginBase::mergeConfigDependencies($dependencies, ["module" => ["ui_patterns_field_formatters"]]);
    return $dependencies;
  }

}
