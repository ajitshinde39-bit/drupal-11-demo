<?php

namespace Drupal\product_price\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\product_price\Service\ProductApiService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Product Price block.
 */
#[Block(
  id: "product_price_block",
  admin_label: new TranslatableMarkup("Product Price"),
)]
class ProductPriceBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The product API service.
   */
  protected ProductApiService $productApi;

  /**
   * Constructs the block.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ProductApiService $product_api,
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $this->productApi = $product_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('product_price.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $product_id = 1;
    $quantity = 3;

    $product = $this->productApi->getProduct($product_id);

    if (!$product) {
      return [
        '#markup' => $this->t('Unable to load product information.'),
      ];
    }

    $price = (float) $product['price'];
    $total = $price * $quantity;

    return [
      '#theme' => 'product_price',
      '#product' => $product['title'],
      '#price' => number_format($price, 2),
      '#quantity' => $quantity,
      '#total' => number_format($total, 2),
    ];
  }

}