<?php

namespace Drupal\custom_view_style\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @ViewsStyle(
 *   id = "custom_testimonials_style",
 *   title = @Translation("Custom Testimonials Style"),
 *   help = @Translation("A custom view style."),
 *   theme = "views_view_custom_testimonials_style",
 *   display_types = {"normal"}
 * )
 */
class CustomTestimonialsStyle extends StylePluginBase {

  /**
   * Does this style use a row plugin?
   */
  protected $usesRowPlugin = TRUE;

  /**
   * Does the style use fields (as opposed to rendered entities)?
   */
  protected $usesFields = TRUE;

}
