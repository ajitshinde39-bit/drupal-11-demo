<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_ui\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\TabledragWarningCommand;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\ui_patterns\PluginWidgetSettingsInterface;
use Drupal\ui_patterns\SourcePluginManager;
use Drupal\ui_patterns_ui\Entity\ComponentFormDisplay;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Component display form.
 */
final class ComponentFormDisplayForm extends EntityForm {

  /**
   * Constructs a new ComponentFormDisplayForm.
   */
  public function __construct(
    protected ComponentPluginManager $componentPluginManager,
    protected SourcePluginManager $sourcePluginManager,
    ModuleHandlerInterface $moduleHandler,
  ) {
    $this->setModuleHandler($moduleHandler);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container):ComponentFormDisplayForm {
    return new static(
      $container->get('plugin.manager.sdc'),
      $container->get('plugin.manager.ui_patterns_source'),
      $container->get('module_handler'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    $component_id = $route_match->getParameter('component_id');
    $form_mode = $route_match->getParameter('form_mode_name');
    if ($component_id !== NULL && $form_mode !== NULL) {
      return ComponentFormDisplay::loadByFormMode($component_id, $form_mode);
    }
    else {
      return $this->entityTypeManager->getStorage($entity_type_id)->create(['component_id' => $component_id]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getRegions(): array {
    return [
      'content' => [
        'title' => $this->t('Content'),
        'invisible' => TRUE,
        'message' => $this->t('No prop/slot is displayed.'),
      ],
      'configure' => [
        'title' => $this->t('Configured'),
        'message' => $this->t('No prop/slot is displayed.'),
      ],
      'hidden' => [
        'title' => $this->t('Disabled', [], ['context' => 'Plural']),
        'message' => $this->t('No prop/slot is hidden.'),
      ],
    ];
  }

  /**
   * Returns the region to which a row in the display overview belongs.
   *
   * @param array $row
   *   The row element.
   *
   * @return string|null
   *   The region name this row belongs to.
   */
  public function getRowRegion(&$row) {
    $regions = $this->getRegions();
    if (!isset($regions[$row['region']['#value']])) {
      $row['region']['#value'] = 'hidden';
    }
    return $row['region']['#value'];
  }

  /**
   * Returns an associative array of all regions.
   *
   * @return array
   *   An array containing the region options.
   */
  public function getRegionOptions() {
    $options = [];
    foreach ($this->getRegions() as $region => $data) {
      $options[$region] = $data['title'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function getTableHeader():array {
    return [
      $this->t('Prop/Slot'),
      $this->t('Weight'),
      $this->t('Parent'),
      $this->t('Region'),
      ['data' => $this->t('Source'), 'colspan' => 3],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state): array {
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay $entity */
    $entity = $this->getEntity();
    $form = parent::form($form, $form_state);
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#required' => TRUE,
    ];

    $form['form_mode_name'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->isNew() === FALSE ? $entity->getFormModeName() : NULL,
      '#machine_name' => [
        'exists' => [ComponentFormDisplay::class, 'loadByFormMode'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' => $entity->status(),
    ];
    /* @phpstan-ignore method.notFound */
    $components = $this->componentPluginManager->getNegotiatedSortedDefinitions();
    $component_id = $entity->getComponentId();
    $component = $components[$component_id];
    $table = [
      '#type' => 'field_ui_table',
      '#header' => $this->getTableHeader(),
      '#regions' => $this->getRegions(),
      '#attributes' => [
        'class' => ['field-ui-overview'],
        'id' => 'field-display-overview',
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-weight',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-parent',
          'subgroup' => 'field-parent',
          'source' => 'field-name',
        ],
        [
          'action' => 'match',
          'relationship' => 'parent',
          'group' => 'field-region',
          'subgroup' => 'field-region',
          'source' => 'field-name',
        ],
      ],
    ];

    $groups = $this->moduleHandler->invokeAll('component_form_display_groups', [$entity]) ?? [];
    $group_ids = array_combine(array_keys($groups), array_keys($groups));
    $props = $component['props']['properties'] ?? [];
    $prop_slot_ids = [];
    // Props rows.
    foreach ($props as $prop_id => $prop_definition) {
      $table[$prop_id] = $this->buildSlotPropRow($prop_id, $prop_definition, $form, $form_state, $group_ids);
      $prop_slot_ids[] = $prop_id;
    }

    $slots = $component['slots'] ?? [];
    // Field rows.
    foreach ($slots as $slot_id => $slot_definition) {
      $table[$slot_id] = $this->buildSlotPropRow($slot_id, $slot_definition, $form, $form_state, $group_ids);
      $prop_slot_ids[] = $slot_id;
    }

    // Non-field elements.
    foreach ($groups as $group_id => $group) {
      $group_display = $entity->getPropSlotOption($group_id) ?? [];
      $table[$group_id] = $this->buildGroup($group_id, $group_display, $group_ids);
      $entity = $this->getEntity();
      $group['id'] = $group_id;
      $this->moduleHandler->alter('component_form_display_group_row', $table[$group_id], $entity, $group);
    }

    $form['fields'] = $table;
    $form['#props'] = $prop_slot_ids;
    if (count($groups) !== 0) {
      $form['#extra_groups'] = array_keys($groups);
    }

    // In overviews involving nested rows from contributed modules (i.e
    // field_group), the 'plugin type' selects can trigger a series of changes
    // in child rows. The #ajax behavior is therefore not attached directly to
    // the selects, but triggered by the client-side script through a hidden
    // #ajax 'Refresh' button. A hidden 'refresh_rows' input tracks the name of
    // affected rows.
    $form['refresh_rows'] = ['#type' => 'hidden'];
    $form['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
      '#op' => 'refresh_table',
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
        'progress' => 'none',
      ],
      '#attributes' => [
        'class' => ['visually-hidden'],
        'tabindex' => '-1',
      ],
    ];

    $form['#attached']['library'][] = 'field_ui/drupal.field_ui';
    return $form;
  }

  /**
   * Build a slot or prop row.
   *
   * @SuppressWarnings(PHPMD)
   */
  public function buildSlotPropRow(string $prop_slot_id, array $prop_definition, array $form, FormStateInterface $form_state, array $groups): array {
    $label = $prop_definition['title'] ?? $prop_slot_id;
    /** @var \Drupal\ui_patterns_ui\Entity\ComponentFormDisplay $entity */
    $entity = $this->getEntity();
    $display_options = $entity->getPropSlotOption($prop_slot_id);
    $slot_prop_row = [
      '#attributes' => ['class' => ['draggable']],
      '#row_type' => 'field',
      '#region_callback' => [$this, 'getRowRegion'],
      '#js_settings' => [
        'rowHandler' => 'field',
        'defaultPlugin' => NULL,
      ],
      'human_name' => [
        '#plain_text' => $label,
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : '0',
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => $this->t('Label display for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#options' => $groups,
          '#empty_value' => '',
          '#default_value' => $display_options ? $display_options['parent'] : '',
          '#attributes' => ['class' => ['js-field-parent', 'field-parent']],
          '#parents' => ['fields', $prop_slot_id, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $prop_slot_id,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => $this->t('Region for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $this->getRegionOptions(),
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => ['class' => ['field-region']],
      ],
    ];

    $plugin_options = [];
    if (isset($display_options['region']) && $display_options['region'] === 'content') {
      $plugin_options[''] = $this->t('Source selector');
    }
    $source_plugins = $entity->getSourcePlugins($prop_slot_id);
    /** @var \Drupal\ui_patterns\SourcePluginBase $source_plugin */
    foreach ($source_plugins as $source_plugin) {
      $plugin_options[$source_plugin->getPluginId()] = $source_plugin->label();
    }

    $slot_prop_row['plugin'] = [
      'source_id' => [
        '#type' => 'select',
        '#title' => $this->t('Plugin for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $plugin_options,
        '#default_value' => $display_options ? $display_options['source_id'] : NULL,
        '#parents' => ['fields', $prop_slot_id, 'source_id'],
        '#attributes' => ['class' => ['field-plugin-type']],
      ],
      'settings_edit_form' => [],
    ];
    $base_button = [
      '#submit' => ['::multistepSubmit'],
      '#ajax' => [
        'callback' => '::multistepAjax',
        'wrapper' => 'field-display-overview-wrapper',
        'effect' => 'fade',
      ],
      '#prop_id' => $prop_slot_id,
    ];
    $plugin = $entity->getSelectedSourcePlugin($prop_slot_id);
    if ($form_state->get('plugin_settings_edit') == $prop_slot_id) {
      $slot_prop_row['plugin']['settings_edit_form'] = [];
      $settings_form = NULL;
      if (isset($display_options['region']) &&
        $display_options['region'] === 'configure') {
        $settings_form = $plugin->settingsForm([], $form_state);
      }
      elseif ($plugin instanceof PluginWidgetSettingsInterface) {
        $settings_form = $plugin->widgetSettingsForm($form, $form_state);
      }
      if ($settings_form !== NULL) {
        $slot_prop_row['plugin']['#cell_attributes'] = ['colspan' => 3];
        $slot_prop_row['plugin']['settings_edit_form'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['field-plugin-settings-edit-form']],
          '#parents' => ['fields', $prop_slot_id, 'settings_edit_form'],
          'label' => [
            '#markup' => $this->t('Plugin settings'),
          ],
          'settings' => $settings_form,
          'actions' => [
            '#type' => 'actions',
            'save_settings' => $base_button + [
              '#type' => 'submit',
              '#button_type' => 'primary',
              '#name' => $prop_slot_id . '_plugin_settings_update',
              '#value' => $this->t('Update'),
              '#op' => 'update',
            ],
            'cancel_settings' => $base_button + [
              '#type' => 'submit',
              '#name' => $prop_slot_id . '_plugin_settings_cancel',
              '#value' => $this->t('Cancel'),
              '#op' => 'cancel',
              '#limit_validation_errors' => [['fields', $prop_slot_id, 'source_id']],
            ],
          ],
        ];
        $slot_prop_row['#attributes']['class'][] = 'field-plugin-settings-editing';
      }
      return $slot_prop_row;
    }

    $slot_prop_row['settings_summary'] = [];
    $slot_prop_row['settings_edit'] = [];
    if (isset($display_options['region']) &&
      $display_options['region'] === 'configure') {
      $slot_prop_row['settings_edit'] = $base_button + [
        '#type' => 'image_button',
        '#name' => $prop_slot_id . '_settings_edit',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#attributes' => [
          'class' => ['field-plugin-settings-edit'],
          'alt' => $this->t('Edit'),
        ],
        '#op' => 'edit',
        '#limit_validation_errors' => [['fields', $prop_slot_id, 'source_id']],
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    elseif ($plugin instanceof PluginWidgetSettingsInterface) {
      $summary = $plugin->widgetSettingsSummary();
      if (!empty($summary)) {
        $slot_prop_row['settings_summary'] = [
          '#type' => 'inline_template',
          '#template' => '<div class="field-plugin-summary">{{ summary|safe_join("<br />") }}</div>',
          '#context' => ['summary' => $summary],
          '#cell_attributes' => ['class' => ['field-plugin-summary-cell']],
        ];
      }
      $slot_prop_row['settings_edit'] = $base_button + [
        '#type' => 'image_button',
        '#name' => $prop_slot_id . '_settings_edit',
        '#src' => 'core/misc/icons/787878/cog.svg',
        '#attributes' => [
          'class' => ['field-plugin-settings-edit'],
          'alt' => $this->t('Edit'),
        ],
        '#op' => 'edit',
        '#limit_validation_errors' => [['fields', $prop_slot_id, 'source_id']],
        '#prefix' => '<div class="field-plugin-settings-edit-wrapper">',
        '#suffix' => '</div>',
      ];
    }
    return $slot_prop_row;
  }

  /**
   * Form submission handler for multistep buttons.
   */
  public function multistepSubmit(array $form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#op'];

    switch ($op) {
      case 'edit':
        // Store the field whose settings are currently being edited.
        $field_name = $trigger['#prop_id'];
        $form_state->set('plugin_settings_edit', $field_name);
        break;

      case 'update':
        // Set the field back to 'non edit' mode, and update $this->entity with
        // the new settings fro the next rebuild.
        $field_name = $trigger['#prop_id'];
        $form_state->set('plugin_settings_edit', NULL);
        $form_state->set('plugin_settings_update', $field_name);
        $this->entity = $this->buildEntity($form, $form_state);
        break;

      case 'cancel':
        // Set the field back to 'non edit' mode.
        $form_state->set('plugin_settings_edit', NULL);
        break;

      case 'refresh_table':
        $updated_rows = explode(' ', $form_state->getValue('refresh_rows'));
        $plugin_settings_edit = $form_state->get('plugin_settings_edit');
        if ($plugin_settings_edit && in_array($plugin_settings_edit, $updated_rows)) {
          $form_state->set('plugin_settings_edit', NULL);
        }
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   *
   * @SuppressWarnings(PHPMD)
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state): void {
    assert($entity instanceof ComponentFormDisplay);
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    $form_values = $form_state->getValues();

    if ($this->entity instanceof EntityWithPluginCollectionInterface) {
      // Do not manually update values represented by plugin collections.
      $form_values = array_diff_key($form_values, $this->entity->getPluginCollections());
    }
    if (isset($form['#extra_groups'])) {
      foreach ($form['#extra_groups'] as $prop_id) {
        $values = $form_values['fields'][$prop_id];

        if ($values['region'] == 'hidden') {
          $entity->removePropSlotOption($prop_id);
        }
        else {
          $options = $entity->getPropSlotOption($prop_id);
          $options['source_id'] = $values['source_id'] ?? NULL;
          $options['weight'] = $values['weight'];
          $options['region'] = $values['region'];
          $options['parent'] = $values['parent'];
          // Only formatters have configurable label visibility.
          if (isset($values['label'])) {
            $options['label'] = $values['label'];
          }
          $entity->setPropSlotOptions($prop_id, $options);
        }
      }
    }

    // Collect data for 'regular' fields.
    foreach ($form['#props'] as $prop_id) {
      $values = $form_values['fields'][$prop_id];

      if ($values['region'] == 'hidden') {
        $entity->removePropSlotOption($prop_id);
      }
      else {
        $options = $entity->getPropSlotOption($prop_id);

        // Update field settings only if the submit handler told us to.
        if ($form_state->get('plugin_settings_update') === $prop_id) {
          // Only store settings actually used by the selected plugin.
          $default_settings = [];
          $options['settings'] = isset($values['settings_edit_form']['settings']) ? array_intersect_key($values['settings_edit_form']['settings'], $default_settings) : [];
          $form_state->set('plugin_settings_update', NULL);
        }

        $options['source_id'] = $values['source_id'];
        $options['weight'] = $values['weight'];
        $options['region'] = $values['region'];
        $options['parent'] = $values['parent'];
        // Only formatters have configurable label visibility.
        if (isset($values['label'])) {
          $options['label'] = $values['label'];
        }
        if (!empty($values['settings_edit_form']['settings'])) {
          if ($values['region'] == 'configure') {
            $options['source'] = $values['settings_edit_form']['settings'] ?? [];
          }
          else {
            $options['widget_settings'] = $values['settings_edit_form']['settings'] ?? [];
          }

        }
        $entity->setPropSlotOptions($prop_id, $options);
      }
    }

  }

  /**
   * Ajax handler for multistep buttons.
   */
  public function multistepAjax(array $form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $trigger = $form_state->getTriggeringElement();
    /** @var string $op */
    $op = $trigger['#op'];

    // Pick the elements that need to receive the ajax-new-content effect.
    $updated_rows = match ($op) {
      'edit', 'update', 'cancel' => [$trigger['#prop_id']],
      'refresh_table' => explode(' ', $form_state->getValue('refresh_rows')),
      default => throw new \UnhandledMatchError($op),
    };
    $updated_columns = match ($op) {
      'edit' => ['plugin'],
      'update', 'cancel' => ['plugin', 'settings_summary', 'settings_edit'],
      'refresh_table' => ['settings_summary', 'settings_edit'],
      default => throw new \UnhandledMatchError($op),
    };

    foreach ($updated_rows as $name) {
      foreach ($updated_columns as $key) {
        $element = &$form['fields'][$name][$key];
        $element['#prefix'] = '<div class="ajax-new-content">' . ($element['#prefix'] ?? '');
        $element['#suffix'] = ($element['#suffix'] ?? '') . '</div>';
      }
    }

    // Replace the whole table.
    $response->addCommand(new ReplaceCommand('#field-display-overview-wrapper', $form['fields']));

    // Add "row updated" warning after the table has been replaced.
    if (!in_array($op, ['cancel', 'edit'])) {
      foreach ($updated_rows as $name) {
        $response->addCommand(new TabledragWarningCommand(Html::getClass($name), 'field-display-overview'));
      }
    }

    return $response;
  }

  /**
   * Builds the table row structure for a generic group.
   */
  protected function buildGroup(string $prop_slot_id, array $display_options, array $groups):array {
    $label = $display_options['label'] ?? $prop_slot_id;

    return [
      '#attributes' => ['class' => ['draggable']],
      '#row_type' => 'extra_field',
      '#region_callback' => [$this, 'getRowRegion'],
      '#js_settings' => ['rowHandler' => 'group'],
      'human_name' => [
        '#markup' => $label,
      ],
      'weight' => [
        '#type' => 'textfield',
        '#title' => $this->t('Weight for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#default_value' => $display_options ? $display_options['weight'] : 0,
        '#size' => 3,
        '#attributes' => ['class' => ['field-weight']],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#title' => $this->t('Parents for @title', ['@title' => $label]),
          '#title_display' => 'invisible',
          '#options' => $groups,
          '#empty_value' => '',
          '#default_value' => $display_options ? $display_options['parent'] : '',
          '#attributes' => ['class' => ['js-field-parent', 'field-parent']],
          '#parents' => ['fields', $prop_slot_id, 'parent'],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => $prop_slot_id,
          '#attributes' => ['class' => ['field-name']],
        ],
      ],
      'region' => [
        '#type' => 'select',
        '#title' => $this->t('Region for @title', ['@title' => $label]),
        '#title_display' => 'invisible',
        '#options' => $this->getRegionOptions(),
        '#default_value' => $display_options ? $display_options['region'] : 'hidden',
        '#attributes' => ['class' => ['field-region']],
      ],
      'plugin' => [
        'type' => [
          '#type' => 'hidden',
          '#value' => $display_options ? 'visible' : 'hidden',
          '#parents' => ['fields', $prop_slot_id, 'type'],
          '#attributes' => ['class' => ['field-plugin-type']],
        ],
      ],
      'settings_summary' => [],
      'settings_edit' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $form_state->setRedirectUrl($this->entity->toUrl('edit-form'));
    return parent::save($form, $form_state);
  }

}
