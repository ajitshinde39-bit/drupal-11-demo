<?php
namespace Drupal\custom_view_style\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class MyTwigFunction extends AbstractExtension {

  public function getFunctions() {
    return [
      new TwigFunction('current_user_name', [$this, 'getCurrentUserName']),
    ];
  }

  public function getCurrentUserName() {
    //$uid = \Drupal::currentUser()->id();
    return \Drupal::currentUser()->getDisplayName();
  }

}