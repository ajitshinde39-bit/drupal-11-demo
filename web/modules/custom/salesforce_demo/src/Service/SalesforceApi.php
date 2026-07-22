<?php
namespace Drupal\salesforce_demo\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class SalesforceApi {

  protected ClientInterface $httpClient;
  protected ConfigFactoryInterface $configFactory;
  protected LoggerInterface $logger;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    LoggerInterface $logger
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
  }

  /**
   * Get OAuth Access Token.
   */
    public function getAccessToken(): array {
        $config = $this->configFactory->get('salesforce_demo.settings');
        $consumerKey = $config->get('consumer_key');
        $consumerSecret = $config->get('consumer_secret');
        $loginUrl = rtrim($config->get('login_url'), '/');

        $url = $loginUrl . '/services/oauth2/token';

        try {
            $response = $this->httpClient->post($url, [
                'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $consumerKey,
                'client_secret' => $consumerSecret,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), TRUE);
            $this->logger->info('Salesforce token generated.');

            return $body;
        }
        catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }

    public function createRecord(string $objectName, array $data): array {
        $token = $this->getAccessToken();
        $config = $this->configFactory->get('salesforce_demo.settings');

        $url = rtrim($token['instance_url'], '/')
            . '/services/data/'
            . $config->get('api_version')
            . '/sobjects/'
            . $objectName . '/';

        $response = $this->httpClient->post($url, [
            'headers' => [
            'Authorization' => 'Bearer ' . $token['access_token'],
            'Content-Type' => 'application/json',
            ],
            'json' => $data,
        ]);

        return json_decode($response->getBody()->getContents(), TRUE);
    }
}