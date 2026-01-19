<?php

namespace Drupal\custom_view_style\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @ViewsStyle(
 *   id = "custom_style",
 *   title = @Translation("Custom Style"),
 *   help = @Translation("A custom view style."),
 *   theme = "views_view_custom_style",
 *   display_types = {"normal"}
 * )
 */
class CustomStyle extends StylePluginBase {

  /**
   * Does this style use a row plugin?
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style use fields (as opposed to rendered entities)?
   */
  protected $usesFields = TRUE;

}
