<?php
namespace Drupal\custom_view_style\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a Hello Block.
 *
 * @Block(
 *   id = "hello_block",
 *   admin_label = @Translation("Hello Block"),
 *   category = @Translation("Custom")
 * )
 */
class HelloBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'message' => 'Hello, Drupal!',
    ] + parent::defaultConfiguration();
  }


  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => $this->configuration['message'],
    ];
  }


  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);


    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
    ];
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['message'] = $form_state->getValue('message');
  }
}


