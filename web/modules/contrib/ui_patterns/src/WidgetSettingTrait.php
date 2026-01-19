<?php

declare(strict_types=1);

namespace Drupal\ui_patterns;

use Drupal\Core\Form\FormStateInterface;
use Drupal\ui_patterns\Plugin\UiPatterns\PropType\SlotPropType;

/**
 * Trait for widget settings.
 */
trait WidgetSettingTrait {

  /**
   * The plugin widget settings.
   *
   * @var array
   */
  protected $widgetSettings = [];

  /**
   * Whether default settings have been merged into the current $settings.
   *
   * @var bool
   */
  protected $defaultWidgetSettingsMerged = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultWidgetSettings(): array {
    return [
      'required' => FALSE,
      'title' => '',
      'title_display' => 'before',
      'description' => '',
      'description_display' => 'after',
    ];
  }

  /**
   * Merges default widget settings values into $settings.
   */
  protected function mergeWidgetDefaults() : void {
    $this->widgetSettings += $this->defaultWidgetSettings();
    $this->defaultWidgetSettingsMerged = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidgetSetting(string $key): mixed {
    if (!$this->defaultWidgetSettingsMerged && !array_key_exists($key, $this->widgetSettings)) {
      $this->mergeWidgetDefaults();
    }
    return $this->widgetSettings[$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidgetSettings(array $settings): PluginWidgetSettingsInterface {
    $this->widgetSettings = $settings;
    $this->defaultWidgetSettingsMerged = FALSE;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) : void {
    if (isset($configuration['widget_settings'])) {
      $this->setWidgetSettings($configuration['widget_settings']);
    }
    parent::setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsForm(array $form, FormStateInterface $form_state): array {
    return [
      'required' =>
        [
          '#title' => $this->t('Required'),
          '#type' => 'checkbox',
          '#default_value' => $this->getWidgetSetting('required') ?? FALSE,
        ],
      'title' =>
        [
          '#title' => $this->t('Title'),
          '#type' => 'textfield',
          '#default_value' => $this->getWidgetSetting('title') ?? '',
        ],
      'title_display' =>
        [
          '#title' => $this->t('Title display'),
          '#type' => 'select',
          '#options' => [
            'before' => $this->t('Before'),
            'after' => $this->t('After'),
            'invisible' => $this->t('Invisible'),
          ],
          '#default_value' => $this->getWidgetSetting('title_display') ?? 'after',
        ],
      'description' =>
        [
          '#title' => $this->t('Description'),
          '#type' => 'textfield',
          '#default_value' => $this->getWidgetSetting('description') ?? '',
        ],
      'description_display' =>
        [
          '#title' => $this->t('Description display'),
          '#type' => 'select',
          '#options' => [
            'before' => $this->t('Before'),
            'after' => $this->t('After'),
            'invisible' => $this->t('Invisible'),
          ],
          '#default_value' => $this->getWidgetSetting('description_display') ?? 'after',
        ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function widgetSettingsSummary(): array {
    $prop_type = $this->getPropDefinition()['ui_patterns']['type_definition'];
    $prop_definition = $this->getPropDefinition();
    $slot_or_prop = $prop_type instanceof SlotPropType ? 'Slot' : 'Prop';
    return [
      $this->t(':type: :prop_id [:prop_type]:', [
        ':prop_id' => $this->getPropId(),
        ':type' => $slot_or_prop,
        ':prop_type' => $prop_type->getPluginId(),
      ]),
      $this->t('Required: :required', [
        ':required' => $this->getWidgetSetting('required') ? 'true' : 'false',
      ]),
      $this->t('Title: :title', [
        ':title' => $this->getWidgetSetting('title') ?? $prop_definition['label'],
      ]),
      $this->t('Title display: :title_display', [
        ':title_display' => $this->getWidgetSetting('title_display') ?? 'after',
      ]),
      $this->t('Description: :description', [
        ':description' => $this->getWidgetSetting('description') ?? $prop_definition['description'] ?? '',
      ]),
      $this->t('Description display: :description_display', [
        ':description_display' => $this->getWidgetSetting('description_display') ?? 'after',
      ]),
    ];
  }

}
