<?php

namespace Drupal\calculator_form\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * Provides Calculator block.
 *
 * @Block(
 *   id = "calculator_block",
 *   admin_label = @Translation("Calculator Form Block")
 * )
 */
class CalculatorBlock extends BlockBase {

  public function build() {
    $messenger = \Drupal::messenger();
    // return [
    //   '#type' => 'component',
    //   '#component' => 'mexant_theme:calculator',
    //   '#props' => [
    //     'image' => '/themes/custom/mexant_theme/images/calculator-image.png',
    //     'heading_small' => 'Your Freedom',
    //     'heading_large' => 'Get a Financial Plan',
    //     //'form' => \Drupal::formBuilder()->getForm('Drupal\\calculator_form\\Form\\CalculatorForm'),
    //     //'messages' => $messenger->all(),
    //   ],
    //   // Render elements go OUTSIDE props
    //   'calculator_form' => \Drupal::formBuilder()->getForm('Drupal\\calculator_form\\Form\\CalculatorForm'),
    // ];

      // return [
      //   '#theme' => 'calculator_block_wrapper',
      //   '#form' => \Drupal::formBuilder()
      //     ->getForm('Drupal\\calculator_form\\Form\\CalculatorForm'),
      // ];

      return [
        '#theme' => 'calculator_block_wrapper',
        '#image' => '/themes/custom/mexant_theme/images/calculator-image.png',
        '#heading_small' => 'Your Freedom',
        '#heading_large' => 'Get a Financial Plan',
        '#form' => \Drupal::formBuilder()
          ->getForm('Drupal\\calculator_form\\Form\\CalculatorForm'),
      ];
  }
}
