<?php
namespace Drupal\custom_view_style\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class MyTwigFilter extends AbstractExtension {

  public function getFilters() {
    return [
      new TwigFilter('uppercase_custom', [$this, 'uppercaseCustom']),
    ];
  }

  public function uppercaseCustom($text) {
    return strtoupper($text);
  }

}