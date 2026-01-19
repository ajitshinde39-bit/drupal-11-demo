<?php

namespace Drupal\ui_patterns_test\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a basic block for testing schema validation.
 */
#[Block(
    id: "uip_schema_test_block",
    admin_label: new TranslatableMarkup("UI Patterns Schema Validation")
  )]
class SchemaTestBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ui_patterns' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['ui_patterns'] = [
      '#type' => 'component_form',
      '#title' => $this->t('Component'),
      '#default_value' => $this->configuration['ui_patterns'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) : void {
    $this->configuration['ui_patterns'] = $form_state->getValue('ui_patterns');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      'ui_patterns' => ['#markup' => 'sample block'],
    ];
  }

}
