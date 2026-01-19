<?php

declare(strict_types=1);

namespace Drupal\ui_patterns_views\Plugin\UiPatterns\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ui_patterns\Attribute\Source;
use Drupal\views\ViewExecutable;

/**
 * Plugin base source to handle the view field.
 */
#[Source(
  id: 'view_field',
  label: new TranslatableMarkup('[View row] Field'),
  description: new TranslatableMarkup('View field.'),
  prop_types: ['slot'],
  tags: ['views'],
  context_requirements: ['views:row'],
  context_definitions: [
    'ui_patterns_views:view_entity' => new EntityContextDefinition('entity:view', label: new TranslatableMarkup('View')),
  ]
)]
class ViewFieldSource extends ViewsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getPropValue(): mixed {
    // Get field name inside the configuration.
    $field_name = $this->getSetting('ui_patterns_views_field') ?? "";
    $view = $this->getView();
    $options = self::getViewsFieldOptions($view);
    // Get row index inside the configuration.
    $row_index = isset($this->context["ui_patterns_views:row:index"]) ? $this->getContextValue("ui_patterns_views:row:index") : 0;
    if (empty($field_name) || !($view instanceof ViewExecutable) || !is_array($options) || !array_key_exists($field_name, $options)) {
      return ['#markup' => ''];
    }
    // Get the output of the field.
    $view->build();
    $field_output = $view->getStyle()->getField($row_index, $field_name);
    // Remove field if need it.
    if ($this->isViewFieldExcluded($field_name, $view) || $this->isViewFieldHidden($field_name, $field_output, $view)) {
      $field_output = NULL;
    }
    return self::renderOutput($field_output);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state): array {
    $form = parent::settingsForm($form, $form_state);
    $options = self::getViewsFieldOptions($this->getView());
    if (!is_array($options) || count($options) < 1) {
      return $form;
    }
    $form['ui_patterns_views_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#description' => $this->t('Select view field to insert in this slot.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('ui_patterns_views_field') ?? "",
      '#required' => TRUE,
    ];
    return $form;
  }

}
