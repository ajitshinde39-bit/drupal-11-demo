<?php

namespace Drupal\product_price\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

class ProductApiService {

  /**
   * The HTTP client.
   */
  protected ClientInterface $httpClient;

  /**
   * The logger.
   */
  protected LoggerInterface $logger;

  /**
   * Constructs the Product API service.
   */
  public function __construct(
    ClientInterface $http_client,
    LoggerInterface $logger,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
  }

  /**
   * Get a product by ID.
   */
  public function getProduct(int $product_id): ?array {
    try {
      $response = $this->httpClient->request(
        'GET',
        'https://fakestoreapi.com/products/' . $product_id,
        [
          'timeout' => 10,
        ]
      );

      if ($response->getStatusCode() !== 200) {
        return NULL;
      }

      $data = json_decode(
        $response->getBody()->getContents(),
        TRUE
      );

      return is_array($data) ? $data : NULL;
    }
    catch (\Exception $e) {
      $this->logger->error(
        'Product API request failed: @message',
        [
          '@message' => $e->getMessage(),
        ]
      );

      return NULL;
    }
  }

}