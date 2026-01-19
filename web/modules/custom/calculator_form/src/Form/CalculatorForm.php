<?php

namespace Drupal\calculator_form\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CalculatorForm extends FormBase {

  public function getFormId() {
    return 'calculate';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    //$form['#theme'] = 'calculate';

    $form['name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Your Name'),
        '#required' => TRUE,
    ];

    $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Your Email'),
        '#required' => TRUE,
    ];

    $form['subject'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subject'),
    ];

    $form['category'] = [
        '#type' => 'select',
        '#title' => $this->t('Your Reason'),
        '#options' => [
        'online_banking' => 'Online Banking',
        'financial_control' => 'Financial Control',
        'yearly_profit' => 'Yearly Profit',
        'crypto' => 'Crypto Investment',
        ],
    ];

    $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => $this->t('Submit Now'),
    '#attributes' => [
        'class' => ['orange-button'],
        ],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $messenger = \Drupal::messenger();
    $messenger->addStatus($this->t('Form submitted successfully.'));
    $form_state->setRedirect('<current>');
  }
}
