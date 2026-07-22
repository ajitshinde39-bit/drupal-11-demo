<?php
namespace Drupal\salesforce_demo\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\salesforce_demo\Service\SalesforceApi;

/**
 * Salesforce Webform Handler.
 *
 * @WebformHandler(
 *   id = "salesforce_handler",
 *   label = @Translation("Salesforce Integration"),
 *   category = @Translation("CRM"),
 *   description = @Translation("Create Salesforce Lead on webform submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL
 * )
 */
class SalesforceWebformHandler extends WebformHandlerBase {
  /**
   * Salesforce API service.
   *
   * @var \Drupal\salesforce_demo\Service\SalesforceApi
   */
  protected SalesforceApi $salesforceApi;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {

    /** @var static $instance */
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->salesforceApi = $container->get('salesforce_demo.api');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'enabled' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildConfigurationForm($form, $form_state);

    $form['settings']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Salesforce Integration'),
      '#default_value' => $this->configuration['settings']['enabled'] ?? TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {

    parent::submitConfigurationForm($form, $form_state);

    $this->configuration['settings']['enabled'] =
      $form_state->getValue(['settings', 'enabled']);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {

    parent::postSave($webform_submission, $update);

    // Only process new submissions.
    if ($update) {
      return;
    }

    if (empty($this->configuration['settings']['enabled'])) {
      return;
    }

    $values = $webform_submission->getData();

    $lead = [
      'FirstName' => $values['first_name'] ?? '',
      'LastName'  => $values['last_name'] ?? '',
      'Company'   => $values['company'] ?? 'Drupal Demo ' . rand(1000,9999),
      'Email'     => $values['email'] ?? '',
      'Phone'     => $values['phone'] ?? rand(1000000000,9999999999),
      'Description' => $values['message'] ?? 'Created from Drupal Webform Handler',
    ];
    

    try {
      $response = $this->salesforceApi->createRecord('Lead', $lead);

      $this->loggerFactory
        ->get('salesforce_demo')
        ->notice(
          'Lead created successfully. <pre>@response</pre>',
          ['@response' => print_r($response, TRUE)]
        );
    }
    catch (\Exception $e) {
      $this->loggerFactory
        ->get('salesforce_demo')
        ->error($e->getMessage());
    }
  }
}