<?php

namespace Drupal\chatgpt_integration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ChatGptForm extends FormBase {

  public function getFormId() {
    return 'chatgpt_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ask ChatGPT'),
      '#attributes' => [
        'id' => 'chat-message',
        'placeholder' => 'Ask something...',
      ],
    ];

    $form['send'] = [
      '#type' => 'button',
      '#value' => $this->t('Send'),
      '#attributes' => [
        'id' => 'chat-send',
        'type' => 'button',
      ],
    ];

    $form['response'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'chat-response'],
    ];

    $form['#attributes']['onsubmit'] = 'return false;';
    // 🔑 THIS IS CRITICAL
    $form['#attached']['library'][] = 'chatgpt_integration/chatgpt';

    // Prevent caching
    $form['#cache']['max-age'] = 0;

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
