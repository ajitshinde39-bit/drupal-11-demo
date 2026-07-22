<?php

namespace Drupal\salesforce_demo\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SalesforceSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return ['salesforce_demo.settings'];
  }

  public function getFormId(): string {
    return 'salesforce_demo_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('salesforce_demo.settings');

    $form['consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Key'),
      '#default_value' => $config->get('consumer_key'),
      '#required' => TRUE,
    ];

    $form['consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Consumer Secret'),
      '#default_value' => $config->get('consumer_secret'),
      '#required' => TRUE,
    ];

    $form['login_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login URL'),
      '#default_value' => $config->get('login_url') ?? 'https://login.salesforce.com',
      '#required' => TRUE,
    ];

    $form['api_version'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Version'),
      '#default_value' => $config->get('api_version') ?? 'v65.0',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->configFactory()
      ->getEditable('salesforce_demo.settings')
      ->set('consumer_key', $form_state->getValue('consumer_key'))
      ->set('consumer_secret', $form_state->getValue('consumer_secret'))
      ->set('login_url', $form_state->getValue('login_url'))
      ->set('api_version', $form_state->getValue('api_version'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}