<?php

namespace Drupal\salesforce_demo\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\salesforce_demo\Service\SalesforceApi;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SalesforceTestController extends ControllerBase {

    protected SalesforceApi $api;

    public function __construct(SalesforceApi $api) {
        $this->api = $api;
    }

    public static function create(ContainerInterface $container): self {
        return new static(
        $container->get('salesforce_demo.api')
        );
    }

    public function test() {
        $leadData = [
            'FirstName' => 'Ajit',
            'LastName' => 'Shinde',
            'Company' => 'Drupal Demo ' . rand(1000,9999),
            'Email' => 'ajit' . time() . '@example.com',
            'Phone' => '7777777777',
            'Description' => 'Lead created from Drupal 11 dem site',
        ];

        try {
            $result = $this->api->createRecord('Lead', $leadData);

            return [
            '#markup' => '<pre>' . print_r($result, TRUE) . '</pre>',
            ];

        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $body = $e->getResponse()->getBody()->getContents();
            $this->logger->error($body);
            throw new \Exception($body);
        }

    }
}